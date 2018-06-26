<?php

class WC_Connect_TaxJar_Integration {

	/**
	 * @var WC_Connect_API_Client
	 */
	public $api_client;

	/**
	 * @var WC_Connect_Logger
	 */
	public $logger;

	private $expected_options = array(
		// If automated taxes are enabled and user disables taxes we re-enable them
		'woocommerce_calc_taxes' => 'yes',
		// Users can set either billing or shipping address for tax rates but not shop
		'woocommerce_tax_based_on' => 'shipping',
		// Rate calculations assume tax not included
		'woocommerce_prices_include_tax' => 'no',
		// Use no special handling on shipping taxes, our API handles that
		'woocommerce_shipping_tax_class' => '',
		// API handles rounding precision
		'woocommerce_tax_round_at_subtotal' => 'no',
		// Rates are calculated in the cart assuming tax not included
		'woocommerce_tax_display_shop' => 'excl',
		// TaxJar returns one total amount, not line item amounts
		'woocommerce_tax_display_cart' => 'excl',
		// TaxJar returns one total amount, not line item amounts
		'woocommerce_tax_total_display' => 'single',
	);

	const PROXY_PATH               = 'taxjar/v2';
	const OPTION_NAME              = 'wc_connect_taxes_enabled';
	const SETUP_WIZARD_OPTION_NAME = 'woocommerce_setup_automated_taxes';

	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Logger $logger
	) {
		$this->api_client = $api_client;
		$this->logger = $logger;

		// Cache rates for 1 hour.
		$this->cache_time = HOUR_IN_SECONDS;
	}

	public function init() {
		// Only enable WCS TaxJar integration if the official TaxJar plugin isn't active.
		if ( class_exists( 'WC_Taxjar' ) ) {
			return;
		}

		$store_settings = $this->get_store_settings();
		$store_country  = $store_settings['store_country_setting'];

		// TaxJar supports USA, Canada, Australia, and the European Union
		if ( ! $this->is_supported_country( $store_country ) ) {
			return;
		}

		// Add toggle for automated taxes to the core settings page
		add_filter( 'woocommerce_tax_settings', array( $this, 'add_tax_settings' ) );

		// Settings values filter to handle the hardcoded settings
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_tax_option' ), 10, 2 );

		// Settings Page
		add_action( 'woocommerce_sections_tax', array( $this, 'output_sections_before' ),  9 );
		add_action( 'woocommerce_sections_tax', array( $this, 'output_sections_after' ),  11 );

		// Bow out if we're not wanted
		if ( ! $this->is_enabled() ) {
			return;
		}

		$this->configure_tax_settings();

		// Calculate Taxes at Cart / Checkout
		if ( class_exists( 'WC_Cart_Totals' ) ) { // Woo 3.2+
			add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_calculate_totals' ), 20 );
		} else {
			add_action( 'woocommerce_calculate_totals', array( $this, 'maybe_calculate_totals' ), 20 );
		}

		// Calculate Taxes for Backend Orders (Woo 2.6+)
		add_action( 'woocommerce_before_save_order_items', array( $this, 'calculate_backend_totals' ), 20 );

		// Set customer taxable location for local pickup
		add_filter( 'woocommerce_customer_taxable_address', array( $this, 'append_base_address_to_customer_taxable_address' ), 10, 1 );

		add_filter( 'woocommerce_calc_tax', array( $this, 'override_woocommerce_tax_rates' ), 10, 3 );
	}

	/**
	 * Are automated taxes enabled?
	 *
	 * @return bool
	 */
	public function is_enabled() {
		// Migrate automated taxes selection from the setup wizard
		if ( get_option( self::SETUP_WIZARD_OPTION_NAME ) ) {
			update_option( self::OPTION_NAME, 'yes' );
			delete_option( self::SETUP_WIZARD_OPTION_NAME );

			return true;
		}

		return ( wc_tax_enabled() && 'yes' === get_option( self::OPTION_NAME ) );
	}

	/**
	 * Add our "automated taxes" setting to the core group.
	 *
	 * @param array $tax_settings WooCommerce Tax Settings
	 *
	 * @return array
	 */
	public function add_tax_settings( $tax_settings ) {
		$enabled = $this->is_enabled();

		$automated_taxes = array(
			'title'    => __( 'Automated taxes', 'woocommerce-services' ),
			'id'       => self::OPTION_NAME, // TODO: save in `wc_connect_options`?
			'desc_tip' => __( 'Automate your sales tax calculations with WooCommerce Services, powered by Jetpack.', 'woocommerce-services' ),
			'desc'     => $enabled ? '<p>' . __( 'Powered by WooCommerce Services ― Your tax rates and settings are automatically configured.', 'woocommerce-services' ) . '</p>' : '',
			'default'  => 'no',
			'type'     => 'select',
			'class'    => 'wc-enhanced-select',
			'options'  => array(
				'no'  => __( 'Disable automated taxes', 'woocommerce-services' ),
				'yes' => __( 'Enable automated taxes', 'woocommerce-services' ),
			),
		);

		// Insert the "automated taxes" setting at the top (under the section title)
		array_splice( $tax_settings, 1, 0, array( $automated_taxes ) );

		if ( $enabled ) {
			// If the automated taxes are enabled, disable the settings that would be reverted in the original plugin
			foreach ( $tax_settings as $index => $tax_setting ) {
				if ( ! array_key_exists( $tax_setting['id'], $this->expected_options ) ) {
					continue;
				}
				$tax_settings[$index]['custom_attributes'] = array( 'disabled' => true );
			}
		}

		return $tax_settings;
	}

	/**
	 * When automated taxes are enabled, overwrite core tax settings that might break the API integration
	 * This is similar to the original plugin functionality where these options were reverted on page load
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/82bf7c58/includes/class-wc-taxjar-integration.php#L66-L91
	 *
	 * @param mixed $value - option value
	 * @param array $option - option metadata
	 * @return string new option value, based on the automated taxes state or $value
	 */
	public function sanitize_tax_option( $value, $option ) {
		if (
			//skip unrecognized option format
			! is_array( $option )
			//skip if unexpected option format
			|| ! isset( $option['id'] )
			//skip if not enabled or not being enabled in the current request
			|| ! $this->is_enabled() && ( ! isset( $_POST[self::OPTION_NAME] ) || 'yes' != $_POST[self::OPTION_NAME] ) ) {
			return $value;
		}

		//the option is currently being enabled - backup the rates and flush the rates table
		if ( ! $this->is_enabled() && self::OPTION_NAME === $option['id'] && 'yes' === $value ) {
			$this->backup_existing_tax_rates();
			return $value;
		}

		//skip if unexpected option
		if ( ! array_key_exists( $option['id'], $this->expected_options ) ) {
			return $value;
		}

		return $this->expected_options[ $option['id'] ];
	}

	/**
	 * Overwrite WooCommerce core tax settings if they are different than expected
	 *
	 * Ported from TaxJar's plugin and modified to support $this->expected_options
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/82bf7c58/includes/class-wc-taxjar-integration.php#L66-L91
	 */
	public function configure_tax_settings() {
		foreach( $this->expected_options as $option => $value ) {
			//first check the option value - with default memory caching this should help to avoid unnecessary DB operations
			if ( get_option( $option ) !== $value ) {
				update_option( $option, $value );
			}
		}
	}

	/**
	 * Hack to hide the tax sections for additional tax class rate tables.
	 */
	public function output_sections_before() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		?>
		<div style="display: none">
		<?php
	}

	/**
	 * Hack to hide the tax sections for additional tax class rate tables.
	 */
	public function output_sections_after() {
		if ( ! $this->is_enabled() ) {
			return;
		}
		?></div><?php
	}

	/**
	 * TaxJar supports USA, Canada, Australia, and the European Union
	 * See: https://developers.taxjar.com/api/reference/#countries
	 *
	 * @return array Countries supported by TaxJar.
	 */
	public function get_supported_countries() {
		$supported_countries = array_merge(
			array(
				'US',
				'CA',
				'AU',
			),
			WC()->countries->get_european_union_countries()
		);

		return $supported_countries;
	}

	/**
	 * Check if a given country is supported by TaxJar.
	 *
	 * @param $country Two character country code.
	 *
	 * @return bool Whether or not the country is supported by TaxJar.
	 */
	public function is_supported_country( $country ) {
		return in_array( $country, $this->get_supported_countries() );
	}

	/**
	 * Gets the store's location settings.
	 *
	 * Modified version of TaxJar's plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L910
	 *
	 * @return array
	 */
	public function get_store_settings() {
		$store_settings = array(
			'taxjar_zip_code_setting' => WC()->countries->get_base_postcode(),
			'store_state_setting'     => WC()->countries->get_base_state(),
			'store_country_setting'   => WC()->countries->get_base_country(),
			'taxjar_city_setting'     => WC()->countries->get_base_city(),
		);

		return apply_filters( 'taxjar_store_settings', $store_settings, array() );
	}

	/**
	 * @param $message
	 */
	public function _log( $message ) {
		$formatted_message = is_scalar( $message ) ? $message : json_encode( $message );

		$this->logger->log( $formatted_message, 'WCS Tax' );
	}

	/**
	 * @param $message
	 */
	public function _error( $message ) {
		$formatted_message = is_scalar( $message ) ? $message : json_encode( $message );

		$this->logger->error( $formatted_message, 'WCS Tax' );
	}

	/**
	 * Wrapper to avoid calling calculate_totals() for admin carts.
	 *
	 * @param $wc_cart_object
	 */
	public function maybe_calculate_totals( $wc_cart_object ) {
		if ( ! WC_Connect_Functions::should_send_cart_api_request() ) {
			return;
		}

		$this->calculate_totals( $wc_cart_object );
	}
	/**
	 * Calculate tax / totals using TaxJar at checkout
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L471
	 *
	 * @return void
	 */
	public function calculate_totals( $wc_cart_object ) {
		// If outside of cart and checkout page or within mini-cart, skip calculations
		if ( ( ! is_cart() && ! is_checkout() ) || ( is_cart() && is_ajax() ) ) {
			return;
		}

		$cart_taxes = array();
		$cart_tax_total = 0;

		foreach ( $wc_cart_object->coupons as $coupon ) {
			if ( method_exists( $coupon, 'get_id' ) ) { // Woo 3.0+
				$limit_usage_qty = get_post_meta( $coupon->get_id(), 'limit_usage_to_x_items', true );

				if ( $limit_usage_qty ) {
					$coupon->set_limit_usage_to_x_items( $limit_usage_qty );
				}
			}
		}

		$address = $this->get_address( $wc_cart_object );
		$line_items = $this->get_line_items( $wc_cart_object );

		$taxes = $this->calculate_tax( array(
			'to_city' => $address['to_city'],
			'to_state' => $address['to_state'],
			'to_country' => $address['to_country'],
			'to_zip' => $address['to_zip'],
			'shipping_amount' => WC()->shipping->shipping_total,
			'line_items' => $line_items,
		) );

		$this->response_rate_ids = $taxes['rate_ids'];
		$this->response_line_items = $taxes['line_items'];

		foreach ( $wc_cart_object->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			$line_item_key = $product->get_id() . '-' . $cart_item_key;
			if ( isset( $taxes['line_items'][ $line_item_key ] ) && ! $taxes['line_items'][ $line_item_key ]->combined_tax_rate ) {
				if ( method_exists( $product, 'set_tax_status' ) ) {
					$product->set_tax_status( 'none' ); // Woo 3.0+
				} else {
					$product->tax_status = 'none'; // Woo 2.6
				}
			}
		}

		// Recalculate shipping package rates
		foreach ( $wc_cart_object->get_shipping_packages() as $package_key => $package ) {
			WC()->session->set( 'shipping_for_package_' . $package_key, null );
		}

		if ( class_exists( 'WC_Cart_Totals' ) ) { // Woo 3.2+
			do_action( 'woocommerce_cart_reset', $wc_cart_object, false );
			do_action( 'woocommerce_before_calculate_totals', $wc_cart_object );
			new WC_Cart_Totals( $wc_cart_object );
		} else {
			remove_action( 'woocommerce_calculate_totals', array( $this, 'calculate_totals' ), 20 );
			$wc_cart_object->calculate_totals();
			add_action( 'woocommerce_calculate_totals', array( $this, 'calculate_totals' ), 20 );
		}
	}

	/**
	 * Calculate tax / totals using TaxJar for backend orders
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L538
	 *
	 * @return void
	 */
	public function calculate_backend_totals( $order_id ) {
		$order = wc_get_order( $order_id );
		$address = $this->get_backend_address();
		$line_items = $this->get_backend_line_items( $order );

		if ( method_exists( $order, 'get_shipping_total' ) ) {
			$shipping = $order->get_shipping_total(); // Woo 3.0+
		} else {
			$shipping = $order->get_total_shipping(); // Woo 2.6
		}

		$taxes = $this->calculate_tax( array(
			'to_city' => $address['to_city'],
			'to_state' => $address['to_state'],
			'to_country' => $address['to_country'],
			'to_zip' => $address['to_zip'],
			'shipping_amount' => $shipping,
			'line_items' => $line_items,
		) );

		// Add tax rates manually for Woo 3.0+
		// Woo 2.6 adds the rates automatically
		foreach ( $order->get_items() as $item_key => $item ) {
			if ( is_object( $item ) ) { // Woo 3.0+
				$product_id = $item->get_product_id();
			}

			$line_item_key = $product_id . '-' . $item_key;

			if ( isset( $taxes['rate_ids'][ $line_item_key ] ) ) {
				$rate_id = $taxes['rate_ids'][ $line_item_key ];

				if ( class_exists( 'WC_Order_Item_Tax' ) ) { // Woo 3.0+
					$item_tax = new WC_Order_Item_Tax();
					$item_tax->set_rate( $rate_id );
					$item_tax->set_order_id( $order_id );
					$item_tax->save();
				}
			}
		}
	}

	/**
	 * Get address details of customer at checkout
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L585
	 *
	 * @return array
	 */
	protected function get_address() {
		$taxable_address = WC()->customer->get_taxable_address();
		$taxable_address = is_array( $taxable_address ) ? $taxable_address : array();

		$to_country = isset( $taxable_address[0] ) && ! empty( $taxable_address[0] ) ? $taxable_address[0] : false;
		$to_state = isset( $taxable_address[1] ) && ! empty( $taxable_address[1] ) ? $taxable_address[1] : false;
		$to_zip = isset( $taxable_address[2] ) && ! empty( $taxable_address[2] ) ? $taxable_address[2] : false;
		$to_city = isset( $taxable_address[3] ) && ! empty( $taxable_address[3] ) ? $taxable_address[3] : false;

		return array(
			'to_country' => $to_country,
			'to_state' => $to_state,
			'to_zip' => $to_zip,
			'to_city' => $to_city,
		);
	}

	/**
	 * Get address details of customer for backend orders
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L607
	 *
	 * @return array
	 */
	protected function get_backend_address() {
		$to_country = isset( $_POST['country'] ) ? strtoupper( wc_clean( $_POST['country'] ) ) : false;
		$to_state = isset( $_POST['state'] ) ? strtoupper( wc_clean( $_POST['state'] ) ) : false;
		$to_zip = isset( $_POST['postcode'] ) ? strtoupper( wc_clean( $_POST['postcode'] ) ) : false;
		$to_city = isset( $_POST['city'] ) ? strtoupper( wc_clean( $_POST['city'] ) ) : false;

		return array(
			'to_country' => $to_country,
			'to_state' => $to_state,
			'to_zip' => $to_zip,
			'to_city' => $to_city,
		);
	}

	/**
	 * Get line items at checkout
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L626
	 *
	 * @return array
	 */
	protected function get_line_items( $wc_cart_object ) {
		$line_items = array();

		foreach ( $wc_cart_object->get_cart() as $cart_item_key => $cart_item ) {
			$product = $cart_item['data'];
			$id = $product->get_id();
			$quantity = $cart_item['quantity'];
			$unit_price = wc_format_decimal( $product->get_price() );
			$line_subtotal = wc_format_decimal( $cart_item['line_subtotal'] );
			$discount = wc_format_decimal( $cart_item['line_subtotal'] - $cart_item['line_total'] );
			$tax_class = explode( '-', $product->get_tax_class() );
			$tax_code = '';

			if ( ! $product->is_taxable() || 'zero-rate' == sanitize_title( $product->get_tax_class() ) ) {
				$tax_code = '99999';
			}

			if ( isset( $tax_class ) && is_numeric( end( $tax_class ) ) ) {
				$tax_code = end( $tax_class );
			}

			// Get WC Subscription sign-up fees for calculations
			if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
				if ( 'none' == WC_Subscriptions_Cart::get_calculation_type() ) {
					if ( class_exists( 'WC_Subscriptions_Synchroniser' ) ) {
						WC_Subscriptions_Synchroniser::maybe_set_free_trial();
					}
					$unit_price = WC_Subscriptions_Cart::set_subscription_prices_for_calculation( $unit_price, $product );
				}
			}

			if ( $unit_price && $line_subtotal ) {
				array_push($line_items, array(
					'id' => $id . '-' . $cart_item_key,
					'quantity' => $quantity,
					'product_tax_code' => $tax_code,
					'unit_price' => $unit_price,
					'discount' => $discount,
				));
			}
		}

		return $line_items;
	}

	/**
	 * Get line items for backend orders
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L676
	 *
	 * @return array
	 */
	protected function get_backend_line_items( $order ) {
		$line_items = array();

		foreach ( $order->get_items() as $item_key => $item ) {
			if ( is_object( $item ) ) { // Woo 3.0+
				$id = $item->get_product_id();
				$quantity = $item->get_quantity();
				$discount = wc_format_decimal( $item->get_subtotal() - $item->get_total() );
				$tax_class = explode( '-', $item->get_tax_class() );
			} else { // Woo 2.6
				$id = $item['product_id'];
				$quantity = $item['qty'];
				$discount = wc_format_decimal( $item['line_subtotal'] - $item['line_total'] );
				$tax_class = explode( '-', $item['tax_class'] );
			}

			$product = wc_get_product( $id );

			if ( ! $product ) {
				continue;
			}

			$unit_price = $product->get_price();
			$tax_code = '';

			if ( ! $product->is_taxable() || 'zero-rate' == sanitize_title( $product->get_tax_class() ) ) {
				$tax_code = '99999';
			}

			if ( isset( $tax_class[1] ) && is_numeric( $tax_class[1] ) ) {
				$tax_code = $tax_class[1];
			}

			if ( $unit_price ) {
				array_push($line_items, array(
					'id' => $id . '-' . $item_key,
					'quantity' => $quantity,
					'product_tax_code' => $tax_code,
					'unit_price' => $unit_price,
					'discount' => $discount,
				));
			}
		}

		return $line_items;
	}

	/**
	 * Override Woo's native tax rates to handle multiple line items with the same tax rate
	 * within the same tax class with different rates due to exemption thresholds
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L729
	 *
	 * @return array
	 */
	public function override_woocommerce_tax_rates( $taxes, $price, $rates ) {
		if ( isset( $this->response_line_items ) && array_values( $rates ) ) {
			// Get tax rate ID for current item
			$keys = array_keys( $taxes );
			$tax_rate_id = $keys[0];
			$line_items = array();

			// Map line items using rate ID
			foreach ( $this->response_rate_ids as $line_item_key => $rate_id ) {
				if ( $rate_id == $tax_rate_id ) {
					$line_items[] = $line_item_key;
				}
			}

			// Remove number precision if Woo 3.2+
			if ( function_exists( 'wc_remove_number_precision' ) ) {
				$price = wc_remove_number_precision( $price );
			}

			foreach ( $this->response_line_items as $line_item_key => $line_item ) {
				// If line item belongs to rate and matches the price, manually set the tax
				if ( in_array( $line_item_key, $line_items ) && $price == $line_item->taxable_amount ) {
					if ( function_exists( 'wc_add_number_precision' ) ) {
						$taxes[ $tax_rate_id ] = wc_add_number_precision( $line_item->tax_collectable );
					} else {
						$taxes[ $tax_rate_id ] = $line_item->tax_collectable;
					}
				}
			}
		}

		return $taxes;
	}

	/**
	 * Set customer zip code and state to store if local shipping option set
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/82bf7c587/includes/class-wc-taxjar-integration.php#L653
	 *
	 * @return array
	 */
	public function append_base_address_to_customer_taxable_address( $address ) {
		$store_settings = $this->get_store_settings();
		$tax_based_on = '';

		list( $country, $state, $postcode, $city ) = $address;

		// See WC_Customer get_taxable_address()
		// wc_get_chosen_shipping_method_ids() available since Woo 2.6.2+
		if ( function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
			if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
				$tax_based_on = 'base';
			}
		} else {
			if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array() ), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
				$tax_based_on = 'base';
			}
		}

		if ( 'base' == $tax_based_on ) {
			$postcode = $store_settings['taxjar_zip_code_setting'];
			$city = strtoupper( $store_settings['taxjar_city_setting'] );
		}

		return array( $country, $state, $postcode, $city );
	}

	/**
	 * Calculate sales tax using SmartCalcs
	 *
	 * Direct from the TaxJar plugin, without Nexus check.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/96b5d57/includes/class-wc-taxjar-integration.php#L247
	 *
	 *
	 * @return void
	 */
	public function calculate_tax( $options = array() ) {
		$this->_log( ':::: TaxJar Plugin requested ::::' );

		// Process $options array and turn them into variables
		$options = is_array( $options ) ? $options : array();

		extract( array_replace_recursive(array(
			'to_country' => null,
			'to_state' => null,
			'to_zip' => null,
			'to_city' => null,
			'shipping_amount' => null, // WC()->shipping->shipping_total
			'line_items' => null
		), $options) );

		$taxes = array(
			'freight_taxable' => 1,
			'has_nexus' => 0,
			'line_items' => array(),
			'rate_ids' => array(),
			'tax_rate' => 0,
		);

 		// Strict conditions to be met before API call can be conducted
		if (
			empty( $to_country ) ||
			empty( $to_zip ) ||
			( empty( $line_items ) && ( 0 == $shipping_amount ) ) ||
			WC()->customer->is_vat_exempt()
		) {
			return false;
		}

		$to_zip           = explode( ',' , $to_zip );
		$to_zip           = array_shift( $to_zip );

		$store_settings   = $this->get_store_settings();
		$from_country     = $store_settings['store_country_setting'];
		$from_state       = $store_settings['store_state_setting'];
		$from_zip         = $store_settings['taxjar_zip_code_setting'];
		$from_city        = $store_settings['taxjar_city_setting'];
		$shipping_amount  = is_null( $shipping_amount ) ? 0.0 : $shipping_amount;

		$this->_log( ':::: TaxJar API called ::::' );

		$body = array(
			'from_country' => $from_country,
			'from_state' => $from_state,
			'from_city' => $from_city,
			'from_zip' => $from_zip,
			'to_country' => $to_country,
			'to_state' => $to_state,
			'to_city' => $to_city,
			'to_zip' => $to_zip,
			'shipping' => $shipping_amount,
			'plugin' => 'woo',
		);

		// Either `amount` or `line_items` parameters are required to perform tax calculations.
		if ( empty( $line_items ) ) {
			$body['amount'] = 0.0;
		} else {
			$body['line_items'] = $line_items;
		}

		$response = $this->smartcalcs_cache_request( wp_json_encode( $body ) );

		if ( isset( $response ) ) {
			// Log the response
			$this->_log( 'Received: ' . $response['body'] );

			// Decode Response
			$taxjar_response          = json_decode( $response['body'] );
			$taxjar_response          = $taxjar_response->tax;

			// Update Properties based on Response
			$taxes['freight_taxable']    = (int) $taxjar_response->freight_taxable;
			$taxes['has_nexus']          = (int) $taxjar_response->has_nexus;
			$taxes['tax_rate']           = $taxjar_response->rate;

			if ( ! empty( $taxjar_response->breakdown ) ) {
				if ( ! empty( $taxjar_response->breakdown->line_items ) ) {
					$line_items = array();
					foreach ( $taxjar_response->breakdown->line_items as $line_item ) {
						$line_items[ $line_item->id ] = $line_item;
					}
					$taxes['line_items'] = $line_items;
				}
			}
		}

		// Remove taxes if they are set somehow and customer is exempt
		if ( WC()->customer->is_vat_exempt() ) {
			$wc_cart_object->remove_taxes();
		} elseif ( $taxes['has_nexus'] ) {
			// Use Woo core to find matching rates for taxable address
			$location = array(
				'to_country' => $to_country,
				'to_state' => $to_state,
				'to_zip' => $to_zip,
				'to_city' => $to_city,
			);

			// Add line item tax rates
			foreach ( $taxes['line_items'] as $line_item_key => $line_item ) {
				$line_item_key_chunks = explode( '-', $line_item_key );
				$product_id = $line_item_key_chunks[0];
				$product = wc_get_product( $product_id );
				$tax_class = $product->get_tax_class();

				if ( $line_item->combined_tax_rate ) {
					$taxes['rate_ids'][ $line_item_key ] = $this->create_or_update_tax_rate(
						$location,
						$line_item->combined_tax_rate * 100,
						$tax_class,
						$taxes['freight_taxable']
					);
				}
			}

			// Add shipping tax rate
			if ( $taxes['tax_rate'] ) {
				$taxes['rate_ids']['shipping'] = $this->create_or_update_tax_rate(
					$location,
					$taxes['tax_rate'] * 100,
					'',
					$taxes['freight_taxable']
				);
			}
		} // End if().
	} // End calculate_tax().

	/**
	 * Add or update a native WooCommerce tax rate
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/9d8e725/includes/class-wc-taxjar-integration.php#L396
	 *
	 * @return void
	 */
	public function create_or_update_tax_rate( $location, $rate, $tax_class = '', $freight_taxable = 1 ) {
		$tax_rate = array(
			'tax_rate_country' => $location['to_country'],
			'tax_rate_state' => $location['to_state'],
			'tax_rate_name' => sprintf( "%s Tax", $location['to_state'] ),
			'tax_rate_priority' => 1,
			'tax_rate_compound' => false,
			'tax_rate_shipping' => $freight_taxable,
			'tax_rate' => $rate,
			'tax_rate_class' => $tax_class,
		);

		$wc_rate = WC_Tax::find_rates( array(
			'country' => $location['to_country'],
			'state' => $location['to_state'],
			'postcode' => $location['to_zip'],
			'city' => $location['to_city'],
			'tax_class' => $tax_class,
		) );

		if ( ! empty( $wc_rate ) ) {
			$this->_log( ':: Tax Rate Found ::' );
			$this->_log( $wc_rate );

			// Get the existing ID
			$rate_id = key( $wc_rate );

			// Update Tax Rates with TaxJar rates ( rates might be coming from a cached taxjar rate )
			$this->_log( ':: Updating Tax Rate To ::' );
			$this->_log( $tax_rate );

			WC_Tax::_update_tax_rate( $rate_id, $tax_rate );
		} else {
			// Insert a rate if we did not find one
			$this->_log( ':: Adding New Tax Rate ::' );
			$this->_log( $tax_rate );
			$rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
			WC_Tax::_update_tax_rate_postcodes( $rate_id, wc_clean( $location['to_zip'] ) );
			WC_Tax::_update_tax_rate_cities( $rate_id, wc_clean( $location['to_city'] ) );
		}

		$this->_log( 'Tax Rate ID Set for ' . $rate_id );
		return $rate_id;
	}

	/**
	 * Wrap SmartCalcs API requests in a transient-based caching layer.
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L451
	 *
	 * @param $json
	 *
	 * @return mixed|WP_Error
	 */
	public function smartcalcs_cache_request( $json ) {
		$cache_key = 'tj_tax_' . hash( 'md5', $json );
		$response  = get_transient( $cache_key );

		if ( false === $response ) {
			$response = $this->smartcalcs_request( $json );

			if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
				set_transient( $cache_key, $response, $this->cache_time );
			}
		}

		return $response;
	}

	/**
	 * Make a TaxJar SmartCalcs API request through the WCS proxy.
	 *
	 * Modified from TaxJar's plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/82bf7c58/includes/class-wc-taxjar-integration.php#L440
	 *
	 * @param $json
	 *
	 * @return array|WP_Error
	 */
	public function smartcalcs_request( $json ) {
		$path = trailingslashit( self::PROXY_PATH ) . 'taxes';

		$this->_log( 'Requesting: ' . $path . ' - ' . $json );

		$response = $this->api_client->proxy_request( $path, array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => $json,
		) );

		if ( is_wp_error( $response ) ) {
			$this->_error( 'Error retrieving the tax rates. Received (' . $response->get_error_code() . '): ' . $response->get_error_message() );
		} elseif ( 200 == $response['response']['code'] ) {
			return $response;
		} else {
			$this->_error( 'Error retrieving the tax rates. Received (' . $response['response']['code'] . '): ' . $response['body'] );
		}
	}

	/**
	 * Exports existing tax rates to a CSV and clears the table.
	 *
	 * Ported from TaxJar's plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/42cd4cd0/taxjar-woocommerce.php#L75
	 */
	public function backup_existing_tax_rates() {
		global $wpdb;

		// Export Tax Rates
		$rates = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
			ORDER BY tax_rate_order
			LIMIT %d, %d
			",
			0,
			10000
		) );

		ob_start();
		$header =
			__( 'Country Code', 'woocommerce' ) . ',' .
			__( 'State Code', 'woocommerce' ) . ',' .
			__( 'ZIP/Postcode', 'woocommerce' ) . ',' .
			__( 'City', 'woocommerce' ) . ',' .
			__( 'Rate %', 'woocommerce' ) . ',' .
			__( 'Tax Name', 'woocommerce' ) . ',' .
			__( 'Priority', 'woocommerce' ) . ',' .
			__( 'Compound', 'woocommerce' ) . ',' .
			__( 'Shipping', 'woocommerce' ) . ',' .
			__( 'Tax Class', 'woocommerce' ) . "\n";

		echo $header;

		foreach ( $rates as $rate ) {
			if ( $rate->tax_rate_country ) {
				echo esc_attr( $rate->tax_rate_country );
			} else {
				echo '*';
			}

			echo ',';

			if ( $rate->tax_rate_state ) {
				echo esc_attr( $rate->tax_rate_state );
			} else {
				echo '*';
			}

			echo ',';

			$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='postcode' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );

			if ( $locations ) {
				echo esc_attr( implode( '; ', $locations ) );
			} else {
				echo '*';
			}

			echo ',';

			$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='city' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );
			if ( $locations ) {
				echo esc_attr( implode( '; ', $locations ) );
			} else {
				echo '*';
			}

			echo ',';

			if ( $rate->tax_rate ) {
				echo esc_attr( $rate->tax_rate );
			} else {
				echo '0';
			}

			echo ',';

			if ( $rate->tax_rate_name ) {
				echo esc_attr( $rate->tax_rate_name );
			} else {
				echo '*';
			}

			echo ',';

			if ( $rate->tax_rate_priority ) {
				echo esc_attr( $rate->tax_rate_priority );
			} else {
				echo '1';
			}

			echo ',';

			if ( $rate->tax_rate_compound ) {
				echo esc_attr( $rate->tax_rate_compound );
			} else {
				echo '0';
			}

			echo ',';

			if ( $rate->tax_rate_shipping ) {
				echo esc_attr( $rate->tax_rate_shipping );
			} else {
				echo '0';
			}

			echo ',';

			echo "\n";
		} // End foreach().

		$csv = ob_get_contents();
		ob_end_clean();
		$upload_dir = wp_upload_dir();
		file_put_contents( $upload_dir['basedir'] . '/taxjar-wc_tax_rates-' . date( 'm-d-Y' ) . '-' . time() . '.csv', $csv );

		// Delete all tax rates
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'woocommerce_tax_rates' );
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'woocommerce_tax_rate_locations' );
	}
}
