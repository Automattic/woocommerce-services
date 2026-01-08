<?php

use Automattic\WCServices\StoreNotices\StoreNoticesNotifier;

class WC_Connect_TaxJar_Integration {

	/**
	 * @var WC_Connect_API_Client
	 */
	public $api_client;

	/**
	 * @var WC_Connect_Logger
	 */
	public $logger;

	/**
	 * @var StoreNoticesNotifier
	 */
	private $notifier;

	public $wc_connect_base_url;

	private $expected_options = array(
		// Users can set either billing or shipping address for tax rates but not shop
		'woocommerce_tax_based_on'       => 'shipping',
		// Rate calculations assume tax not included
		'woocommerce_prices_include_tax' => 'no',
		// Use no special handling on shipping taxes, our API handles that
		'woocommerce_shipping_tax_class' => '',
		// Rates are calculated in the cart assuming tax not included
		'woocommerce_tax_display_shop'   => 'excl',
		// TaxJar returns one total amount, not line item amounts
		'woocommerce_tax_display_cart'   => 'excl',
	);

	/**
	 * Cache time.
	 *
	 * @var int
	 */
	private $cache_time;

	/**
	 * Address Validation Cache time.
	 *
	 * @var int
	 */
	private $address_cache_time;

	/**
	 * Error cache time.
	 *
	 * @var int
	 */
	private $error_cache_time;

	/**
	 * @var array
	 */
	private $response_rate_ids;

	/**
	 * @var array
	 */
	private $response_line_items;

	/**
	 * @var bool
	 */
	private $is_itemized_tax_display;

	/**
	 * Backend tax classes.
	 *
	 * @var array
	 */
	private $backend_tax_classes;

	/**
	 * Tracks instance.
	 *
	 * @var WC_Connect_Tracks
	 */
	protected $tracks;

	const PROXY_PATH               = 'taxjar/v2';
	const OPTION_NAME              = 'wc_connect_taxes_enabled';
	const SETUP_WIZARD_OPTION_NAME = 'woocommerce_setup_automated_taxes';

	/**
	 * WCS TaxJar integration constructor.
	 *
	 * @param WC_Connect_API_Client     $api_client          TaxJar API client.
	 * @param WC_Connect_Logger         $logger              Logger.
	 * @param string                    $wc_connect_base_url WC Connect base URL.
	 * @param WC_Connect_Tracks         $tracks              Tracks.
	 * @param StoreNoticesNotifier|null $notifier            Notifier.
	 */
	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Logger $logger,
		$wc_connect_base_url,
		WC_Connect_Tracks $tracks,
		?StoreNoticesNotifier $notifier = null
	) {
		$this->api_client          = $api_client;
		$this->logger              = $logger;
		$this->wc_connect_base_url = $wc_connect_base_url;
		$this->notifier            = $notifier;
		$this->tracks              = $tracks;

		// Cache rates for 1 hour.
		$this->cache_time = HOUR_IN_SECONDS;

		// Cache address validation errors for 1 year.
		$this->address_cache_time = YEAR_IN_SECONDS;

		// Cache error response for 5 minutes.
		$this->error_cache_time = MINUTE_IN_SECONDS * 5;

		$this->is_itemized_tax_display = ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) );
	}

	/**
	 * Generates an itemized tax rate name based on the provided tax rate and country.
	 *
	 * @param string $taxjar_rate_name The tax rate name from TaxJar, typically including '_tax_rate'.
	 * @param string $to_country       The destination country for the tax calculation.
	 * @param array  $jurisdictions    Tax jurisdictions.
	 *
	 * @return string The formatted and localized tax rate name.
	 */
	private static function generate_itemized_tax_rate_name( string $taxjar_rate_name, string $to_country, array $jurisdictions ) {
		// Normalize the base key by stripping the trailing "_tax_rate" and converting underscores to spaces.
		$base_key   = str_replace( '_tax_rate', '', $taxjar_rate_name );
		$label_core = ucwords( str_replace( '_', ' ', $base_key ) );
		$rate_name  = $label_core . ' ' . __( 'Tax', 'woocommerce-services' );

		// Handle VAT countries where country-level tax should be labeled as VAT.
		$is_vat_country = false;
		if ( function_exists( 'WC' ) && WC() && isset( WC()->countries ) && method_exists( WC()->countries, 'get_vat_countries' ) ) {
			$is_vat_country = in_array( $to_country, WC()->countries->get_vat_countries(), true );
		}

		if ( 'country' === $base_key && $is_vat_country ) {
			return 'VAT';
		}

		// Default, country-agnostic formatting for non‑US destinations.
		if ( 'US' !== $to_country ) {
			// Preserve previous behavior of uppercasing for non‑US.
			return strtoupper( $rate_name );
		}

		// United States specific naming enhancements.
		$county = isset( $jurisdictions['county'] ) ? trim( (string) $jurisdictions['county'] ) : '';
		$city   = isset( $jurisdictions['city'] ) ? trim( (string) $jurisdictions['city'] ) : '';

		switch ( $taxjar_rate_name ) {
			case 'city_tax_rate':
			case 'special_tax_rate':
				// Example: "Some County Some City : City Tax".
				$prefix = trim( $county . ' ' . $city );
				return ( '' !== $prefix ? $prefix . ' : ' : '' ) . $rate_name;

			case 'county_tax_rate':
				// Example: "Some County : County Tax".
				return ( '' !== $county ? $county . ' : ' : '' ) . $rate_name;

			case 'state_sales_tax_rate':
				// Example: "State Sales Tax" (no jurisdiction prefix).
				return $rate_name;

			default:
				// Fallback for any other US rate types.
				return $rate_name;
		}
	}

	public function init() {
		// Only enable WCS TaxJar integration if the official TaxJar plugin isn't active.
		if ( class_exists( 'WC_Taxjar' ) ) {
			return;
		}

		$store_settings = $this->get_store_settings();
		$store_country  = $store_settings['country'];

		// TaxJar supports USA, Canada, Australia, and the European Union
		if ( ! $this->is_supported_country( $store_country ) ) {
			return;
		}

		// Add toggle for automated taxes to the core settings page
		add_filter( 'woocommerce_tax_settings', array( $this, 'add_tax_settings' ) );

		// Fix tooltip with link on older WC.
		if ( version_compare( WOOCOMMERCE_VERSION, '4.4.0', '<' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'fix_tooltip_keepalive' ), 11 );
		}

		// Settings values filter to handle the hardcoded settings
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $this, 'sanitize_tax_option' ), 10, 2 );

		// Bow out if we're not wanted
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Scripts / Stylesheets
		add_action( 'admin_enqueue_scripts', array( $this, 'load_taxjar_admin_new_order_assets' ) );

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
		add_filter( 'woocommerce_matched_rates', array( $this, 'allow_street_address_for_matched_rates' ), 10, 2 );

		add_filter( 'woocommerce_rate_label', array( $this, 'cleanup_tax_label' ) );

		WC_Connect_Custom_Surcharge::init();
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
		$enabled                = $this->is_enabled();
		$backedup_tax_rates_url = admin_url( '/admin.php?page=wc-status&tab=connect#tax-rate-backups' );

		$powered_by_wct_notice = '<p>' . sprintf( __( 'Automated taxes take over from the WooCommerce core tax settings. This means that "Display prices" will be set to Excluding tax and tax will be Calculated using Customer shipping address. %1$sLearn more about Automated taxes here.%2$s', 'woocommerce-services' ), '<a href="https://woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-tax/#setup-and-configuration">', '</a>' ) . '</p>';

		$backup_notice = ( ! empty( WC_Connect_Functions::get_backed_up_tax_rate_files() ) ) ? '<p>' . sprintf( __( 'Your previous tax rates were backed up and can be downloaded %1$shere%2$s.', 'woocommerce-services' ), '<a href="' . esc_url( $backedup_tax_rates_url ) . '">', '</a>' ) . '</p>' : '';

		$desctructive_action_notice = '<p>' . __( 'Enabling this option overrides any tax rates you have manually added.', 'woocommerce-services' ) . '</p>';
		$desctructive_backup_notice = '<p>' . sprintf( __( 'Your existing tax rates will be backed-up to a CSV that you can download %1$shere%2$s.', 'woocommerce-services' ), '<a href="' . esc_url( $backedup_tax_rates_url ) . '">', '</a>' ) . '</p>';

		$tax_nexus_notice = '<p>' . $this->get_tax_tooltip() . '</p>';

		$automated_taxes_description = join(
			'',
			$enabled ? array(
				$powered_by_wct_notice,
				$backup_notice,
				$tax_nexus_notice,
			) : array( $desctructive_action_notice, $desctructive_backup_notice, $tax_nexus_notice )
		);
		$automated_taxes             = array(
			'title'    => __( 'Automated taxes', 'woocommerce-services' ),
			'id'       => self::OPTION_NAME, // TODO: save in `wc_connect_options`?
			'desc_tip' => $this->get_tax_tooltip(),
			'desc'     => $automated_taxes_description,
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
				if ( empty( $tax_setting['id'] ) || ! array_key_exists( $tax_setting['id'], $this->expected_options ) ) {
					continue;
				}
				$tax_settings[ $index ]['custom_attributes'] = array( 'disabled' => true );
			}
		}

		return $tax_settings;
	}

	/**
	 * Get the text to show in the tooltip next to automated tax settings.
	 */
	private function get_tax_tooltip() {
		$store_settings = $this->get_store_settings();
		$all_states     = WC()->countries->get_states( $store_settings['country'] );
		$all_countries  = WC()->countries->get_countries();
		$full_country   = $all_countries[ $store_settings['country'] ];
		$full_state     = isset( $all_states[ $store_settings['state'] ] ) ? $all_states[ $store_settings['state'] ] : '';

		$country_state = ( $full_state ) ? $full_state . ', ' . $full_country : $full_country;

		if ( ! $this->is_enabled() ) {
			/* translators: 1: full state and country name, 2: anchor opening with tax rate link, 3: anchor closer, 4: anchor opening with tax nexus link, 5: anchor closer */
			return sprintf( __( 'Your tax rates and settings will be automatically configured for %1$s. Automated taxes uses your store address as your "tax nexus". If you want to charge tax for any other state, you can add a %2$stax rate%3$s for that state in addition to using automated taxes. %4$sLearn more about Tax Nexus here%5$s.', 'woocommerce-services' ), $country_state, '<a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#setting-up-tax-rates">', '</a>', '<a href="https://woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-tax/#section-14">', '</a>' );
		}

		/* translators: 1: full state and country name, 2: anchor opening with tax rate link, 3: anchor closer, 4: anchor opening with tax nexus link, 5: anchor closer */
		return sprintf( __( 'Your tax rates are now automatically calculated for %1$s. Automated taxes uses your store address as your "tax nexus". If you want to charge tax for any other state, you can add a %2$stax rate%3$s for that state in addition to using automated taxes. %4$sLearn more about Tax Nexus here%5$s.', 'woocommerce-services' ), $country_state, '<a href="https://woocommerce.com/document/setting-up-taxes-in-woocommerce/#setting-up-tax-rates">', '</a>', '<a href="https://woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-tax/#section-14">', '</a>' );
	}

	/**
	 * Hack to force keepAlive: true on tax setting tooltip.
	 */
	public function fix_tooltip_keepalive() {
		global $pagenow;
		if ( 'admin.php' !== $pagenow || ! isset( $_GET['page'] ) || 'wc-settings' !== $_GET['page'] || ! isset( $_GET['tab'] ) || 'tax' !== $_GET['tab'] || ! empty( $_GET['section'] ) ) {
			return;
		}

		$tooltip = $this->get_tax_tooltip();
		// Links in tooltips will not work unless keepAlive is true.
		wp_add_inline_script(
			'woocommerce_admin',
			"jQuery( function () {
					jQuery( 'label[for=wc_connect_taxes_enabled] .woocommerce-help-tip')
						.off( 'mouseenter mouseleave' )
						.tipTip( {
							'fadeIn': 50,
							'fadeOut': 50,
							'delay': 200,
							keepAlive: true,
							content: '" . $tooltip . "'
						} );
				} );"
		);
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
    // phpcs:disable WordPress.Security.NonceVerification.Missing --- Security is taken care of by WooCommerce
		if (
			// skip unrecognized option format
			! is_array( $option )
			// skip if unexpected option format
			|| ! isset( $option['id'] )
			// skip if not enabled or not being enabled in the current request
			|| ! $this->is_enabled() && ( ! isset( $_POST[ self::OPTION_NAME ] ) || 'yes' != $_POST[ self::OPTION_NAME ] ) ) {
			return $value;
		}

		// the option is currently being enabled - backup the rates and flush the rates table
		if ( ! $this->is_enabled() && self::OPTION_NAME === $option['id'] && 'yes' === $value ) {
			$this->backup_existing_tax_rates();
			return $value;
		}

		// skip if unexpected option
		if ( ! array_key_exists( $option['id'], $this->expected_options ) ) {
			return $value;
		}
    // phpcs:enable WordPress.Security.NonceVerification.Missing

		return $this->expected_options[ $option['id'] ];
	}

	/**
	 * Overwrite WooCommerce core tax settings if they are different than expected
	 *
	 * Ported from TaxJar's plugin and modified to support $this->expected_options
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/82bf7c58/includes/class-wc-taxjar-integration.php#L66-L91
	 */
	public function configure_tax_settings() {
		foreach ( $this->expected_options as $option => $value ) {
			// first check the option value - with default memory caching this should help to avoid unnecessary DB operations
			if ( get_option( $option ) !== $value ) {
				update_option( $option, $value );
			}
		}
	}

	/**
	 * TaxJar supports USA, Canada, Australia, and the European Union + Great Britain
	 * See: https://developers.taxjar.com/api/reference/#countries
	 *
	 * @return array Countries supported by TaxJar.
	 */
	public function get_supported_countries() {
		// Hard code list instead of using `WC()->countries->get_european_union_countries()` just in case anyone else decides to leave the EU.
		return array( 'US', 'CA', 'AU', 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'HR', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK' );
	}

	/**
	 * Check if a given country is supported by TaxJar.
	 *
	 * @param string $country Two character country code.
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
			'street'   => WC()->countries->get_base_address(),
			'city'     => WC()->countries->get_base_city(),
			'state'    => WC()->countries->get_base_state(),
			'country'  => WC()->countries->get_base_country(),
			'postcode' => WC()->countries->get_base_postcode(),
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

		// ignore error messages caused by customer input
		$state_zip_mismatch = false !== strpos( $formatted_message, 'to_zip' ) && false !== strpos( $formatted_message, 'is not used within to_state' );
		$invalid_postcode   = false !== strpos( $formatted_message, 'isn\'t a valid postal code for' );
		$malformed_postcode = false !== strpos( $formatted_message, 'zip code has incorrect format' );
		if ( ! is_admin() && ( $state_zip_mismatch || $invalid_postcode || $malformed_postcode ) ) {
			$fields              = WC()->countries->get_address_fields();
			$postcode_field_name = __( 'ZIP/Postal code', 'woocommerce-services' );
			if ( isset( $fields['billing_postcode'] ) && isset( $fields['billing_postcode']['label'] ) ) {
				$postcode_field_name = $fields['billing_postcode']['label'];
			}

			if ( $state_zip_mismatch ) {
				$message = sprintf( _x( '%s does not match the selected state.', '%s - ZIP/Postal code checkout field label', 'woocommerce-services' ), $postcode_field_name );
			} elseif ( $malformed_postcode ) {
				$message = sprintf( _x( '%s is not formatted correctly.', '%s - ZIP/Postal code checkout field label', 'woocommerce-services' ), $postcode_field_name );
			} else {
				$message = sprintf( _x( 'Invalid %s entered.', '%s - ZIP/Postal code checkout field label', 'woocommerce-services' ), $postcode_field_name );
			}

			$this->notifier->error( $message, array(), 'taxjar' );

			return;
		}

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
	 * @param WC_Cart $wc_cart_object
	 * @return void
	 */
	public function calculate_totals( $wc_cart_object ) {
		/*
		 * Don't calculate if we are outside cart and checkout page, or pages with WooCommerce Cart and Checkout blocks.
		 * Don't calculate if we are inside mini-cart.
		 * If this is an API call don't calculate unless this is store/cart request.
		 */
		if (
			! WC_Connect_Functions::has_cart_or_checkout_block() &&
			! WC_Connect_Functions::is_store_api_call() &&
			(
				( ! is_cart() && ! is_checkout() ) ||
				( is_cart() && is_ajax() )
			)
		) {
			return;
		}

		$cart_taxes     = array();
		$cart_tax_total = 0;

		/**
		 * WC Coupon object.
		 *
		 * @var WC_Coupon $coupon
		*/
		foreach ( $wc_cart_object->coupons as $coupon ) {
			if ( method_exists( $coupon, 'get_limit_usage_to_x_items' ) ) { // Woo 3.0+.
				$limit_usage_qty = $coupon->get_limit_usage_to_x_items();

				if ( $limit_usage_qty ) {
					$coupon->set_limit_usage_to_x_items( $limit_usage_qty );
				}
			}
		}

		$address    = $this->get_address( $wc_cart_object );
		$line_items = $this->get_line_items( $wc_cart_object );

		$taxes = $this->calculate_tax(
			array(
				'to_country'      => $address['to_country'],
				'to_zip'          => $address['to_zip'],
				'to_state'        => $address['to_state'],
				'to_city'         => $address['to_city'],
				'to_street'       => $address['to_street'],
				'shipping_amount' => method_exists( $wc_cart_object, 'get_shipping_total' ) ?
					$wc_cart_object->get_shipping_total() : WC()->shipping->shipping_total,
				'line_items'      => $line_items,
			)
		);

		// Return if taxes could not be calculated.
		if ( false === $taxes ) {
			return;
		}

		$this->response_rate_ids   = $taxes['rate_ids'];
		$this->response_line_items = $taxes['line_items'];

		if ( isset( $this->response_line_items ) ) {
			foreach ( $this->response_line_items as $response_line_item_key => $response_line_item ) {
				$line_item = $this->get_line_item( $response_line_item_key, $line_items );
				if ( isset( $line_item ) ) {
					$this->response_line_items[ $response_line_item_key ]->line_total = ( $line_item['unit_price'] * $line_item['quantity'] ) - $line_item['discount'];
				}
			}
		}

		foreach ( $wc_cart_object->get_cart() as $cart_item_key => $cart_item ) {
			$product       = $cart_item['data'];
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
			remove_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_calculate_totals' ), 20 );
			do_action( 'woocommerce_after_calculate_totals', $wc_cart_object );
			add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_calculate_totals' ), 20 );
		} else {
			remove_action( 'woocommerce_calculate_totals', array( $this, 'maybe_calculate_totals' ), 20 );
			$wc_cart_object->calculate_totals();
			add_action( 'woocommerce_calculate_totals', array( $this, 'maybe_calculate_totals' ), 20 );
		}
	}

	/**
	 * Calculate tax / totals using TaxJar for backend orders
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/96b5d57/includes/class-wc-taxjar-integration.php#L557
	 *
	 * @return void
	 */
	public function calculate_backend_totals( $order_id ) {
		$order      = wc_get_order( $order_id );
		$address    = $this->get_backend_address();
		$line_items = $this->get_backend_line_items( $order );
		if ( method_exists( $order, 'get_shipping_total' ) ) {
			$shipping = $order->get_shipping_total(); // Woo 3.0+
		} else {
			$shipping = $order->get_total_shipping(); // Woo 2.6
		}
		$taxes = $this->calculate_tax(
			array(
				'to_country'      => $address['to_country'],
				'to_state'        => $address['to_state'],
				'to_zip'          => $address['to_zip'],
				'to_city'         => $address['to_city'],
				'to_street'       => $address['to_street'],
				'shipping_amount' => $shipping,
				'line_items'      => $line_items,
			)
		);
		if ( class_exists( 'WC_Order_Item_Tax' ) ) { // Add tax rates manually for Woo 3.0+
			/**
			 * @var WC_Order_Item_Product $item Product Order Item.
			 */
			foreach ( $order->get_items() as $item_key => $item ) {
				$product_id    = $item->get_product_id();
				$line_item_key = $product_id . '-' . $item_key;
				if ( isset( $taxes['rate_ids'][ $line_item_key ] ) ) {
					$rate_id  = $taxes['rate_ids'][ $line_item_key ];
					$item_tax = new WC_Order_Item_Tax();
					$item_tax->set_rate( $rate_id );
					$item_tax->set_order_id( $order_id );
					$item_tax->save();
				}
			}
		} elseif ( class_exists( 'WC_AJAX' ) ) { // Recalculate tax for Woo 2.6 to apply new tax rates
				remove_action( 'woocommerce_before_save_order_items', array( $this, 'calculate_backend_totals' ), 20 );
			if ( check_ajax_referer( 'calc-totals', 'security', false ) ) {
				WC_AJAX::calc_line_taxes();
			}
				add_action( 'woocommerce_before_save_order_items', array( $this, 'calculate_backend_totals' ), 20 );
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
		$taxable_address = $this->get_taxable_address();
		$taxable_address = is_array( $taxable_address ) ? $taxable_address : array();

		$to_country = isset( $taxable_address[0] ) && ! empty( $taxable_address[0] ) ? strtoupper( $taxable_address[0] ) : false;
		$to_state   = isset( $taxable_address[1] ) && ! empty( $taxable_address[1] ) ? strtoupper( $taxable_address[1] ) : false;
		$to_zip     = isset( $taxable_address[2] ) && ! empty( $taxable_address[2] ) ? $taxable_address[2] : false;
		$to_city    = isset( $taxable_address[3] ) && ! empty( $taxable_address[3] ) ? $taxable_address[3] : false;
		$to_street  = isset( $taxable_address[4] ) && ! empty( $taxable_address[4] ) ? $taxable_address[4] : false;

		return array(
			'to_country' => $to_country,
			'to_state'   => $to_state,
			'to_zip'     => $to_zip,
			'to_city'    => $to_city,
			'to_street'  => $to_street,
		);
	}

	/**
	 * Allow street address to be passed when finding rates
	 *
	 * @param array  $matched_tax_rates
	 * @param string $tax_class
	 * @return array
	 */
	public function allow_street_address_for_matched_rates( $matched_tax_rates, $tax_class = '' ) {
		$tax_class         = sanitize_title( $tax_class );
		$location          = WC_Tax::get_tax_location( $tax_class );
		$matched_tax_rates = array();
		if ( sizeof( $location ) >= 4 ) {
			list( $country, $state, $postcode, $city, $street ) = array_pad( $location, 5, '' );
			$matched_tax_rates                                  = WC_Tax::find_rates(
				array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => strtoupper( $city ),
					'tax_class' => $tax_class,
				)
			);
		}
		return $matched_tax_rates;
	}

	public function cleanup_tax_label( $rate_name ) {

		if ( ! $this->is_itemized_tax_display ) {
			return $rate_name;
		}

		$label_parts = explode( ' : ', $rate_name );
		$clean_label = ! empty( $label_parts[1] ) ? $label_parts[1] : $label_parts[0];

		return $clean_label;
	}

	/**
	 * Get taxable address.
	 *
	 * @return array
	 */
	public function get_taxable_address() {
		$tax_based_on = get_option( 'woocommerce_tax_based_on' );
		// Check shipping method at this point to see if we need special handling
		// See WC_Customer get_taxable_address()
		// wc_get_chosen_shipping_method_ids() available since Woo 2.6.2+
		if ( function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
			if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
				$tax_based_on = 'base';
			}
		} elseif ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array() ), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
				$tax_based_on = 'base';
		}

		if ( 'base' === $tax_based_on ) {
			$store_settings = $this->get_store_settings();
			$country        = $store_settings['country'];
			$state          = $store_settings['state'];
			$postcode       = $store_settings['postcode'];
			$city           = $store_settings['city'];
			$street         = $store_settings['street'];
		} elseif ( 'billing' === $tax_based_on ) {
			$country  = WC()->customer->get_billing_country();
			$state    = WC()->customer->get_billing_state();
			$postcode = WC()->customer->get_billing_postcode();
			$city     = WC()->customer->get_billing_city();
			$street   = WC()->customer->get_billing_address();
		} else {
			$country  = WC()->customer->get_shipping_country();
			$state    = WC()->customer->get_shipping_state();
			$postcode = WC()->customer->get_shipping_postcode();
			$city     = WC()->customer->get_shipping_city();
			$street   = WC()->customer->get_shipping_address();
		}

		return apply_filters( 'woocommerce_customer_taxable_address', array( $country, $state, $postcode, $city, $street ) );
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
    // phpcs:disable WordPress.Security.NonceVerification.Missing --- Security handled by WooCommerce
		$to_country = isset( $_POST['country'] ) ? strtoupper( wc_clean( $_POST['country'] ) ) : false;
		$to_state   = isset( $_POST['state'] ) ? strtoupper( wc_clean( $_POST['state'] ) ) : false;
		$to_zip     = isset( $_POST['postcode'] ) ? strtoupper( wc_clean( $_POST['postcode'] ) ) : false;
		$to_city    = isset( $_POST['city'] ) ? strtoupper( wc_clean( $_POST['city'] ) ) : false;
		$to_street  = isset( $_POST['street'] ) ? strtoupper( wc_clean( $_POST['street'] ) ) : false;
    // phpcs:enable WordPress.Security.NonceVerification.Missing

		return array(
			'to_country' => $to_country,
			'to_state'   => $to_state,
			'to_zip'     => $to_zip,
			'to_city'    => $to_city,
			'to_street'  => $to_street,
		);
	}

	/**
	 * Get line items at checkout
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/96b5d57/includes/class-wc-taxjar-integration.php#L645
	 *
	 * @return array
	 */
	protected function get_line_items( $wc_cart_object ) {
		$line_items = array();

		foreach ( $wc_cart_object->get_cart() as $cart_item_key => $cart_item ) {
			$product       = $cart_item['data'];
			$id            = $product->get_id();
			$quantity      = $cart_item['quantity'];
			$unit_price    = wc_format_decimal( $product->get_price() );
			$line_subtotal = wc_format_decimal( $cart_item['line_subtotal'] );
			$discount      = wc_format_decimal( $cart_item['line_subtotal'] - $cart_item['line_total'] );
			$tax_class     = explode( '-', $product->get_tax_class() );
			$tax_code      = '';

			if ( isset( $tax_class ) && is_numeric( end( $tax_class ) ) ) {
				$tax_code = end( $tax_class );
			}

			if ( 'shipping' !== $product->get_tax_status() && ( ! $product->is_taxable() || 'zero-rate' == sanitize_title( $product->get_tax_class() ) ) ) {
				$tax_code = '99999';
			}

			// Get WC Subscription sign-up fees for calculations
			if ( class_exists( 'WC_Subscriptions_Cart' ) ) {
				if ( 'none' == WC_Subscriptions_Cart::get_calculation_type() ) {
					if ( class_exists( 'WC_Subscriptions_Synchroniser' ) ) {
						WC_Subscriptions_Synchroniser::maybe_set_free_trial();
					}
					$unit_price = WC_Subscriptions_Cart::set_subscription_prices_for_calculation( $unit_price, $product );
					if ( class_exists( 'WC_Subscriptions_Synchroniser' ) ) {
						WC_Subscriptions_Synchroniser::maybe_unset_free_trial();
					}
				}
			}

			array_push(
				$line_items,
				array(
					'id'               => $id . '-' . $cart_item_key,
					'quantity'         => $quantity,
					'product_tax_code' => $tax_code,
					'unit_price'       => $unit_price,
					'discount'         => $discount,
				)
			);
		}

		return $line_items;
	}

	/**
	 * Get line items for backend orders
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/96b5d57/includes/class-wc-taxjar-integration.php#L695
	 *
	 * @return array
	 */
	protected function get_backend_line_items( $order ) {
		$line_items                = array();
		$this->backend_tax_classes = array();
		foreach ( $order->get_items() as $item_key => $item ) {
			if ( is_object( $item ) ) { // Woo 3.0+
				$id             = $item->get_product_id();
				$quantity       = $item->get_quantity();
				$unit_price     = empty( $quantity ) ? $item->get_subtotal() : wc_format_decimal( $item->get_subtotal() / $quantity );
				$discount       = wc_format_decimal( $item->get_subtotal() - $item->get_total() );
				$tax_class_name = $item->get_tax_class();
				$tax_status     = $item->get_tax_status();
			} else { // Woo 2.6
				$id             = $item['product_id'];
				$quantity       = $item['qty'];
				$unit_price     = empty( $quantity ) ? $item['line_subtotal'] : wc_format_decimal( $item['line_subtotal'] / $quantity );
				$discount       = wc_format_decimal( $item['line_subtotal'] - $item['line_total'] );
				$tax_class_name = $item['tax_class'];
				$product        = $order->get_product_from_item( $item );
				$tax_status     = $product ? $product->get_tax_status() : 'taxable';
			}
			$this->backend_tax_classes[ $id ] = $tax_class_name;
			$tax_class                        = explode( '-', $tax_class_name );
			$tax_code                         = '';
			if ( isset( $tax_class[1] ) && is_numeric( $tax_class[1] ) ) {
				$tax_code = $tax_class[1];
			}
			if ( 'taxable' !== $tax_status ) {
				$tax_code = '99999';
			}
			if ( $unit_price ) {
				array_push(
					$line_items,
					array(
						'id'               => $id . '-' . $item_key,
						'quantity'         => $quantity,
						'product_tax_code' => $tax_code,
						'unit_price'       => $unit_price,
						'discount'         => $discount,
					)
				);
			}
		}
		return $line_items;
	}

	protected function get_line_item( $id, $line_items ) {
		foreach ( $line_items as $line_item ) {
			if ( $line_item['id'] === $id ) {
				return $line_item;
			}
		}
		return null;
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
		if (
			! isset( $this->response_line_items )
			|| empty( $rates )
			|| ! is_array( $rates )
			|| ! is_array( $taxes )
		) {
				return $taxes;
		}

		// Get tax rate ID for current item
		$keys        = array_keys( $taxes );
		$tax_rate_id = $keys[0];
		$line_items  = array();

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
			if ( in_array( $line_item_key, $line_items ) && $price == $line_item->line_total ) {
				if ( function_exists( 'wc_add_number_precision' ) ) {
					$taxes[ $tax_rate_id ] = wc_add_number_precision( $line_item->tax_collectable );
				} else {
					$taxes[ $tax_rate_id ] = $line_item->tax_collectable;
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
		$tax_based_on = '';

		list( $country, $state, $postcode, $city, $street ) = array_pad( $address, 5, '' );

		// See WC_Customer get_taxable_address()
		// wc_get_chosen_shipping_method_ids() available since Woo 2.6.2+
		if ( function_exists( 'wc_get_chosen_shipping_method_ids' ) ) {
			if ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
				$tax_based_on = 'base';
			}
		} elseif ( true === apply_filters( 'woocommerce_apply_base_tax_for_local_pickup', true ) && sizeof( array_intersect( WC()->session->get( 'chosen_shipping_methods', array() ), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) > 0 ) {
				$tax_based_on = 'base';
		}

		if ( 'base' == $tax_based_on ) {
			$store_settings = $this->get_store_settings();
			$postcode       = $store_settings['postcode'];
			$city           = strtoupper( $store_settings['city'] );
			$street         = $store_settings['street'];
		}

		if ( '' != $street ) {
			return array( $country, $state, $postcode, $city, $street );
		}

		return array( $country, $state, $postcode, $city );
	}

	/**
	 * This method is used to override the TaxJar result.
	 *
	 * @param object $taxjar_resp_tax TaxJar response object.
	 * @param array  $body            Body of TaxJar request.
	 *
	 * @return object
	 */
	public function maybe_override_taxjar_tax( $taxjar_resp_tax, $body ) {
		if ( ! isset( $taxjar_resp_tax ) ) {
			return;
		}

		$new_tax_rate = floatval( apply_filters( 'woocommerce_services_override_tax_rate', $taxjar_resp_tax->rate, $taxjar_resp_tax, $body ) );

		if ( $new_tax_rate === floatval( $taxjar_resp_tax->rate ) ) {
			return $taxjar_resp_tax;
		}

		if ( ! empty( $taxjar_resp_tax->breakdown->line_items ) ) {
			$taxjar_resp_tax->breakdown->line_items = array_map(
				function ( $line_item ) use ( $new_tax_rate ) {
					$line_item->combined_tax_rate       = $new_tax_rate;
					$line_item->country_tax_rate        = $new_tax_rate;
					$line_item->country_tax_collectable = $line_item->country_taxable_amount * $new_tax_rate;
					$line_item->tax_collectable         = $line_item->taxable_amount * $new_tax_rate;

					return $line_item;
				},
				$taxjar_resp_tax->breakdown->line_items
			);
		}

		$taxjar_resp_tax->breakdown->combined_tax_rate           = $new_tax_rate;
		$taxjar_resp_tax->breakdown->country_tax_rate            = $new_tax_rate;
		$taxjar_resp_tax->breakdown->shipping->combined_tax_rate = $new_tax_rate;
		$taxjar_resp_tax->breakdown->shipping->country_tax_rate  = $new_tax_rate;

		$taxjar_resp_tax->rate = $new_tax_rate;

		return $taxjar_resp_tax;
	}

	/**
	 * Maybe apply a temporary workaround for the TaxJar API to get the correct rates for
	 * specific edge cases.
	 *
	 * For these specific edge cases a "nexus_addresses" element needs to be added to the
	 * TaxJar request body and the "from" address needs to be removed from it in order to
	 * get the correct rates. This is due to a limitation/miscalculation at the TaxJar API.
	 *
	 * This method returns a nexus address to be used as a workaround
	 * if the workaround is enabled and an address case is matched.
	 *
	 * New edge cases can be added to the $cases array as needed.
	 *
	 * @param array $body Request body.
	 *
	 * @return array
	 */
	private function maybe_apply_taxjar_nexus_addresses_workaround( $body ) {
		$workaround_nexus_addresses = array();
		if ( true !== apply_filters( 'woocommerce_apply_taxjar_nexus_addresses_workaround', true ) ) {
			return $workaround_nexus_addresses;
		}

		$cases = array(
			'CA-QC' => array(
				'to_country'   => 'CA',
				'to_state'     => 'QC',
				'from_country' => 'CA',
			),
			'US-CO' => array(
				'to_country'   => 'US',
				'to_state'     => 'CO',
				'from_country' => 'US',
				'from_state'   => 'CO',
			),
			'US-AZ' => array(
				'to_country'   => 'US',
				'to_state'     => 'AZ',
				'from_country' => 'US',
				'from_state'   => 'AZ',
			),
			'US-OH' => array(
				'to_country'   => 'US',
				'to_state'     => 'OH',
				'from_country' => 'US',
				'from_state'   => 'OH',
			),
		);

		foreach ( $cases as $case ) {

			/**
			 * Ensure the body has all the required address keys, and that the body address
			 * values match the case address values before applying the workaround.
			 */
			$address_keys = array_keys( $case );
			foreach ( $address_keys as $address_key ) {
				if ( ! isset( $body[ $address_key ] ) || $body[ $address_key ] !== $case[ $address_key ] ) {
					continue 2;
				}
			}

			$workaround_nexus_addresses = array(
				'country' => $body['to_country'],
				'zip'     => $body['to_zip'],
				'state'   => $body['to_state'],
				'city'    => $body['to_city'],
				'street'  => $body['to_street'],
			);

			break;
		}

		return $workaround_nexus_addresses;
	}

	/**
	 * Validates TaxJar nexus address.
	 *
	 * @param  array $address
	 *
	 * @return bool
	 */
	private function is_nexus_address_valid( $address ): bool {
		$errors = array();
		$schema = array(
			'id'      => array(
				'type'        => 'string',
				'required'    => false,
				'description' => 'Unique identifier for the nexus address (optional).',
				'max_length'  => 255,
			),
			'country' => array(
				'type'        => 'string',
				'required'    => true,
				'pattern'     => '/^[A-Z]{2}$/', // two-letter ISO alpha-2 (upper-case)
				'description' => 'Two-letter ISO country code (e.g. "US").',
				'max_length'  => 2,
			),
			'zip'     => array(
				'type'        => 'string',
				'required'    => false,
				'description' => 'Postal code (format varies by country).',
				'max_length'  => 20,
			),
			'state'   => array(
				'type'        => 'string',
				'required'    => true,
				'pattern'     => '/^[A-Z0-9\-]{1,100}$/', // typical short code like "NY", "CA", "NSW"
				'description' => 'Two-letter (or short) ISO state/province code where applicable.',
				'max_length'  => 100,
			),
			'city'    => array(
				'type'        => 'string',
				'required'    => false,
				'description' => 'City name.',
				'max_length'  => 100,
			),
			'street'  => array(
				'type'        => 'string',
				'required'    => false,
				'description' => 'Street address (line).',
				'max_length'  => 255,
			),
		);

		/**
		 * Return without logging as empty array() or false
		 * might be return on purpose from filter to remove nexus address.
		 */
		if ( empty( $address ) ) {
			return false;
		}

		if ( ! is_array( $address ) ) {
			$this->logger->error( 'Nexus Address ERRORS: Nexus addresses has invalid format' . PHP_EOL . 'Nexus address removed from request body.' . PHP_EOL . print_r( $address, true ), 'WCS Tax' );

			return false;
		}

		foreach ( $schema as $field => $rules ) {
			$exists = array_key_exists( $field, $address );
			$value  = $exists ? $address[ $field ] : null;

			if ( ! empty( $rules['required'] ) && ! $exists ) {
				$errors[] = "[$field] field is required";
				continue;
			}

			if ( ! $exists || $value === '' || $value === null ) {
				continue;
			}

			if ( isset( $rules['type'] ) ) {
				if ( $rules['type'] === 'string' && ! is_string( $value ) ) {
					$errors[] = "[$field] field must be a string";
					continue;
				}
			}

			if ( isset( $rules['max_length'] ) && is_string( $value ) ) {
				if ( strlen( $value ) > $rules['max_length'] ) {
					$errors[] = "[$field] field exceeds maximum length of {$rules['max_length']}";
				}
			}

			if ( isset( $rules['pattern'] ) && is_string( $value ) ) {
				if ( ! preg_match( $rules['pattern'], $value ) ) {
					$errors[] = "[$field] field format is invalid";
				}
			}
		}

		if ( ! empty( $errors ) ) {
			$this->logger->error( 'Nexus Address ERRORS: ' . implode( ', ', $errors ) . PHP_EOL . 'Nexus address removed from request body.' . PHP_EOL . print_r( $address, true ), 'WCS Tax' );

			return false;
		}

		return true;
	}

	/**
	 * Calculate sales tax using SmartCalcs
	 *
	 * Direct from the TaxJar plugin, without Nexus check.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/96b5d57/includes/class-wc-taxjar-integration.php#L247
	 *
	 * @return array|boolean
	 */
	public function calculate_tax( $options = array() ) {
		$this->_log( ':::: TaxJar Plugin requested ::::' );

		// Normalize options to an array and safely map to local variables.
		$options = is_array( $options ) ? $options : array();

		$to_country      = isset( $options['to_country'] ) ? strtoupper( $options['to_country'] ) : null;
		$to_state        = isset( $options['to_state'] ) ? strtoupper( $options['to_state'] ) : null;
		$to_zip          = $options['to_zip'] ?? null;
		$to_city         = $options['to_city'] ?? null;
		$to_street       = $options['to_street'] ?? null;
		$shipping_amount = $options['shipping_amount'] ?? 0;
		$line_items      = $options['line_items'] ?? null;

		$taxes = array(
			'freight_taxable' => 1,
			'has_nexus'       => 0,
			'line_items'      => array(),
			'rate_ids'        => array(),
			'tax_rate'        => 0,
		);

		// Strict conditions to be met before API call can be conducted.
		if (
			empty( $to_country ) ||
			( empty( $to_zip ) && ! in_array( $to_country, WC()->countries->get_vat_countries() ) ) ||
			( empty( $line_items ) && ( empty( $shipping_amount ) ) ) ||
			WC()->customer->is_vat_exempt()
		) {
			return false;
		}

		$to_zip = explode( ',', $to_zip );
		$to_zip = array_shift( $to_zip );

		$store_settings = $this->get_store_settings();
		$from_country   = strtoupper( $store_settings['country'] );
		$from_state     = strtoupper( $store_settings['state'] );
		$from_zip       = $store_settings['postcode'];
		$from_city      = $store_settings['city'];
		$from_street    = $store_settings['street'];

		$this->_log( ':::: TaxJar API called ::::' );

		$body = array(
			'from_country' => $from_country,
			'from_state'   => $from_state,
			'from_zip'     => $from_zip,
			'from_city'    => $from_city,
			'from_street'  => $from_street,
			'to_country'   => $to_country,
			'to_state'     => $to_state,
			'to_zip'       => $to_zip,
			'to_city'      => $to_city,
			'to_street'    => $to_street,
			'shipping'     => $shipping_amount,
			'plugin'       => 'woo',
		);

		$nexus_address = $this->maybe_apply_taxjar_nexus_addresses_workaround( $body );

		// If workaround empty use default store address as nexus address.
		if ( empty( $nexus_address ) ) {
			$nexus_address = array(
				'country' => $body['from_country'],
				'zip'     => $body['from_zip'],
				'state'   => $body['from_state'],
				'city'    => $body['from_city'],
				'street'  => $body['from_street'],
			);
		}

		/**
		 * Filter to modify or disable the nexus address sent to TaxJar API.
		 *
		 * This filter allows modification of the nexus address that will be sent
		 * in the TaxJar API request. The nexus address replaces the standard from_*
		 * address fields when provided.
		 *
		 * Return false or an empty array to disable sending nexus addresses entirely,
		 * which will cause the request to use the standard from_* address fields instead.
		 *
		 * The nexus address array should contain the following keys:
		 * - country: Two-letter country code (required).
		 * - state: Two-letter state/province code (required for US/CA).
		 * - zip: Postal/ZIP code (required).
		 * - city: City name (required).
		 * - street: Street address (optional).
		 *
		 * @since 3.3.0
		 *
		 * @param array $nexus_address The nexus address array to be sent to TaxJar.
		 * @param array $body          The complete TaxJar API request body.
		 *
		 * @return array|false Modified nexus address array, or false to disable nexus addresses.
		 *
		 * @example
		 * // Disable nexus addresses entirely.
		 * add_filter( 'woocommerce_taxjar_nexus_address', '__return_false' );
		 *
		 * @example
		 * // Modify the nexus address.
		 * add_filter( 'woocommerce_taxjar_nexus_address', function( $nexus_address, $body ) {
		 *     $nexus_address['street'] = '123 Custom Street';
		 *     return $nexus_address;
		 * }, 10, 2 );
		 */
		$nexus_address = apply_filters( 'woocommerce_taxjar_nexus_address', $nexus_address, $body );

		if ( is_array( $nexus_address ) && ! empty( $nexus_address ) && $this->is_nexus_address_valid( $nexus_address ) ) {
			$params_to_unset = array(
				'from_country',
				'from_state',
				'from_zip',
				'from_city',
				'from_street',
			);

			foreach ( $params_to_unset as $param ) {
				unset( $body[ $param ] );
			}
			$body['nexus_addresses'] = array( $nexus_address );
		}

		// Either `amount` or `line_items` parameters are required to perform tax calculations.
		if ( empty( $line_items ) ) {
			$body['amount'] = 0.01;
		} else {
			$body['line_items'] = $line_items;
		}

		$response = $this->smartcalcs_cache_request( wp_json_encode( $body ), $from_state );

		// if no response, no need to keep going - bail early.
		if ( ! isset( $response ) || ! $response ) {
			$this->_log( 'Received: none.' );

			return false;
		}

		// Log the response.
		$this->_log( 'Received: ' . $response['body'] );

		// Decode Response.
		$taxjar_response = json_decode( $response['body'] );
		if ( empty( $taxjar_response->tax ) ) {
			return false;
		}
		$taxjar_taxes = $this->maybe_override_taxjar_tax( $taxjar_response->tax, $body );
		$taxes        = $this->get_itemized_tax_rates( $taxes, $taxjar_taxes, $options );

		return $taxes;
	} // End calculate_tax().

	/**
	 * Get itemized tax rates from TaxJar response.
	 *
	 * @param array  $taxes        The tax data array that will be modified and returned.
	 * @param object $taxjar_taxes TaxJar response object.
	 * @param array  $options      Cart data used for tax calculation.
	 *
	 * @return array
	 */
	private function get_itemized_tax_rates( $taxes, $taxjar_taxes, $options ): array {

		// Normalize options and safely map to local variables.
		$options = is_array( $options ) ? $options : array();

		$to_country = isset( $options['to_country'] ) ? strtoupper( $options['to_country'] ) : null;
		$to_state   = isset( $options['to_state'] ) ? strtoupper( $options['to_state'] ) : null;
		$to_zip     = $options['to_zip'] ?? null;
		$to_city    = $options['to_city'] ?? null;

		$store_settings = $this->get_store_settings();
		$from_country   = strtoupper( $store_settings['country'] );
		$from_state     = strtoupper( $store_settings['state'] );

		// Update Properties based on Response.
		$taxes['freight_taxable'] = (int) $taxjar_taxes->freight_taxable;
		$taxes['has_nexus']       = (int) $taxjar_taxes->has_nexus;
		$taxes['tax_rate']        = $taxjar_taxes->rate;

		if ( ! empty( $taxjar_taxes->breakdown ) ) {
			if ( ! empty( $taxjar_taxes->breakdown->line_items ) ) {
				$line_items = array();
				foreach ( $taxjar_taxes->breakdown->line_items as $line_item ) {
					$line_items[ $line_item->id ] = $line_item;
				}
				$taxes['line_items'] = $line_items;
			}
		}

		if ( $taxes['has_nexus'] ) {

			$this->tracks->record_user_event(
				'tax_calculation_nexus_detected',
				array(
					'from_country'      => $from_country,
					'from_state'        => $from_state,
					'to_country'        => $to_country,
					'to_state'          => $to_state,
					'to_zip'            => $to_zip,
					'to_city'           => $to_city,
					'freight_taxable'   => $taxes['freight_taxable'],
					'combined_tax_rate' => $taxes['tax_rate'],
				)
			);

			// Use Woo core to find matching rates for taxable address.
			$jurisdictions = array(
				'county' => $taxjar_taxes->jurisdictions->county ?? null,
				'city'   => $taxjar_taxes->jurisdictions->city ?? null,
			);
			$location      = array(
				'from_country' => $from_country,
				'from_state'   => $from_state,
				'to_country'   => $to_country,
				'to_state'     => $to_state,
				'to_zip'       => $to_zip,
				'to_city'      => $to_city,
			);

			// Add line item tax rates.
			foreach ( $taxes['line_items'] as $line_item_key => $line_item ) {
				$line_item_key_chunks = explode( '-', $line_item_key );
				$product_id           = $line_item_key_chunks[0];
				$product              = wc_get_product( $product_id );

				$tax_class = '';
				if ( $product ) {
					$tax_class = $product->get_tax_class();
				} elseif ( isset( $this->backend_tax_classes[ $product_id ] ) ) {
					$tax_class = $this->backend_tax_classes[ $product_id ];
				}

				$_tax_rates = (array) $line_item;
				$priority   = 1;
				foreach ( $_tax_rates as $tax_rate_name => $tax_rate ) {
					if ( 'combined_tax_rate' === $tax_rate_name || false === strpos( $tax_rate_name, '_tax_rate' ) ) {
						continue;
					}
					$taxes['rate_ids'][ $line_item_key ][] = $this->create_or_update_tax_rate(
						$location,
						round( $tax_rate * 100, 4 ),
						$tax_class,
						$taxes['freight_taxable'],
						$priority,
						self::generate_itemized_tax_rate_name( $tax_rate_name, $to_country, $jurisdictions )
					);

					++$priority;
				}
			}

			// Add shipping tax rate.
			$_tax_rates = isset( $taxjar_taxes->breakdown->shipping ) ? (array) $taxjar_taxes->breakdown->shipping : array();
			$priority   = 1;
			foreach ( $_tax_rates as $tax_rate_name => $tax_rate ) {
				if ( 'combined_tax_rate' === $tax_rate_name || false === strpos( $tax_rate_name, '_tax_rate' ) ) {
					continue;
				}
				$taxes['rate_ids']['shipping'][] = $this->create_or_update_tax_rate(
					$location,
					round( $tax_rate * 100, 4 ),
					'',
					$taxes['freight_taxable'],
					$priority,
					self::generate_itemized_tax_rate_name( $tax_rate_name, $to_country, $jurisdictions )
				);

				++$priority;
			}
		}

		return $taxes;
	}

	/**
	 * Add or update WooCommerce tax rate.
	 *
	 * @param  array     $location
	 * @param  int|float $rate
	 * @param  string    $tax_class
	 * @param  int       $freight_taxable
	 * @param  int       $rate_priority
	 * @param  string    $tax_rate_name
	 *
	 * @return int
	 */
	public function create_or_update_tax_rate( $location, $rate, $tax_class = '', $freight_taxable = 1, $rate_priority = 1, $tax_rate_name = 'Tax' ) {
		// Prevent filling "State code" column for countries with VAT tax.
		// VAT tax is country wide.
		$to_state      = 'VAT' === $tax_rate_name ? '' : strtoupper( $location['to_state'] );
		$rate_priority = absint( $rate_priority );

		/**
		 * @see https://github.com/Automattic/woocommerce-services/issues/2531
		 * @see https://floridarevenue.com/faq/Pages/FAQDetails.aspx?FAQID=1277&IsDlg=1
		 *
		 * According to the Florida Department of Revenue, sales tax must be charged on
		 * shipping costs if the customer does not have an option to avoid paying the
		 * merchant for shipping costs by either picking up the merchandise themselves
		 * or arranging for a third party to pick up the merchandise and deliver it to
		 * them.
		 *
		 * Normally TaxJar enables taxes on shipping by default for Florida to
		 * Florida shipping, but because WooCommerce uses a single account, a nexus
		 * cannot be added for Florida (or any state) which means the shipping tax
		 * is not enabled. So, we will enable it here by default and give merchants
		 * the option to disable it if needed via filter.
		 *
		 * @since 1.26.0
		 */
		if ( true === apply_filters( 'woocommerce_taxjar_enable_florida_shipping_tax', true ) && 'US' === $location['to_country'] && 'FL' === $location['from_state'] && 'FL' === $location['to_state'] ) {
			$freight_taxable = 1;
		}

		$tax_rate = array(
			'tax_rate_country'  => $location['to_country'],
			'tax_rate_state'    => $to_state,
			// For the US, we're going to modify the name of the tax rate to simplify the reporting and distinguish between the tax rates at the counties level.
			// I would love to do this for other locations, but it looks like that would create issues.
			// For example, for the UK it would continuously rename the rate name with an updated `state` "piece", each time a request is made
			'tax_rate_name'     => $tax_rate_name,
			'tax_rate_priority' => $rate_priority,
			'tax_rate_compound' => false,
			'tax_rate_shipping' => $freight_taxable,
			'tax_rate'          => $rate,
			'tax_rate_class'    => $tax_class,
		);

		$wc_rates = WC_Tax::find_rates(
			array(
				'country'   => $location['to_country'],
				'state'     => str_replace( ' ', '', $to_state ),
				'postcode'  => $location['to_zip'],
				'city'      => strtoupper( $location['to_city'] ),
				'tax_class' => $tax_class,
			)
		);

		$wc_rates_ids = is_array( $wc_rates ) ? array_keys( $wc_rates ) : array();
		if ( isset( $wc_rates_ids[ $rate_priority - 1 ] ) ) {
			$wc_rate[ $wc_rates_ids[ $rate_priority - 1 ] ] = $wc_rates[ $wc_rates_ids[ $rate_priority - 1 ] ];
		} else {
			$wc_rate = array();
		}

		if ( ! empty( $wc_rate ) ) {
			$this->_log( ':: Tax Rate Found ::' );
			$this->_log( $wc_rate );

			// Get the existing ID
			$rate_id = key( $wc_rate );

			// Update Tax Rates with TaxJar rates ( rates might be coming from a cached taxjar rate )
			$this->_log( ':: Updating Tax Rate To ::' );
			$this->_log( $tax_rate );
			if ( $wc_rate[ $rate_id ]['label'] !== $tax_rate_name || (float) $wc_rate[ $rate_id ]['rate'] !== (float) $rate ) {
				// Allow to manually change is Shipping taxable, won't be overwritten automatically.
				$tax_rate['tax_rate_shipping'] = wc_string_to_bool( $wc_rate[ $rate_id ]['shipping'] );
				WC_Tax::_update_tax_rate( $rate_id, $tax_rate );
			}
		} else {
			// Insert a rate if we did not find one
			$this->_log( ':: Adding New Tax Rate ::' );
			$this->_log( $tax_rate );
			$rate_id = WC_Tax::_insert_tax_rate( $tax_rate );
			// VAT is always country wide, no need to create separate entires for each zip and city.
			if ( 'VAT' !== $tax_rate_name ) {
				WC_Tax::_update_tax_rate_postcodes( $rate_id, wc_normalize_postcode( wc_clean( $location['to_zip'] ) ) );
				WC_Tax::_update_tax_rate_cities( $rate_id, wc_clean( $location['to_city'] ) );
			}
		}

		$this->_log( 'Tax Rate ID Set for ' . $rate_id );

		return $rate_id;
	}

	/**
	 * Validate TaxJar API request json value and add the error to log.
	 *
	 * @param $json
	 *
	 * @return bool
	 */
	public function validate_taxjar_request( $json ) {
		$this->_log( ':::: TaxJar API request validation ::::' );

		$json = json_decode( $json, true );

		if ( empty( $json['to_country'] ) ) {
			$this->_error( 'API request is stopped. Empty country destination.' );

			return false;
		}

		if ( ( 'US' === $json['to_country'] || 'CA' === $json['to_country'] ) && empty( $json['to_state'] ) ) {
			$this->_error( 'API request is stopped. Country destination is set to US or CA but the state is empty.' );

			return false;
		}

		if ( 'US' === $json['to_country'] && empty( $json['to_zip'] ) ) {
			$this->_error( 'API request is stopped. Country destination is set to US but the zip code is empty.' );

			return false;
		}

		// Apply this validation only if the destination country is the US and the zip code is 5 or 10 digits long.
		if ( 'US' === $json['to_country'] && ! empty( $json['to_zip'] ) && in_array( strlen( $json['to_zip'] ), array( 5, 10 ) ) && ! WC_Validation::is_postcode( $json['to_zip'], $json['to_country'] ) ) {
			$this->_error( 'API request is stopped. Country destination is set to US but the zip code has incorrect format.' );

			return false;
		}

		if ( ! empty( $json['from_country'] ) && ! empty( $json['from_zip'] ) && 'US' === $json['from_country'] && ! WC_Validation::is_postcode( $json['from_zip'], $json['from_country'] ) ) {
			$this->_error( 'API request is stopped. Country store is set to US but the zip code has incorrect format.' );

			return false;
		}

		$this->_log( 'API request is in good format.' );

		return true;
	}

	/**
	 * Wrap SmartCalcs API requests in a transient-based caching layer.
	 *
	 * Unchanged from the TaxJar plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/4b481f5/includes/class-wc-taxjar-integration.php#L451
	 *
	 * @param $json
	 * @param $from_state
	 *
	 * @return mixed|WP_Error
	 */
	public function smartcalcs_cache_request( $json, $from_state ) {
		$cache_key           = 'tj_tax_' . hash( 'md5', $json );
		$zip_state_cache_key = false;
		$request             = json_decode( $json );
		$to_zip              = isset( $request->to_zip ) ? (string) $request->to_zip : false;
		$to_state            = isset( $request->to_state ) ? strtoupper( (string) $request->to_state ) : false;
		if ( $to_zip && $to_state ) {
			$zip_state_cache_key = strtolower( 'tj_tax_' . $to_zip . '_' . $to_state );
			$response            = get_transient( $zip_state_cache_key );
		}
		$response = ! empty( $response ) ? $response : get_transient( $cache_key );
		if ( $response && 'CA' !== $from_state ) {
			// If $from_state is not California, we need to check for incorrect California tax nexus.
			try {
				$this->check_for_incorrect_california_tax_nexus( $response['body'], true, $from_state );
			} catch ( Exception $e ) {
				$this->_log( 'Error checking for incorrect California tax nexus: ' . $e->getMessage() );
			}
		}
		$response_code    = wp_remote_retrieve_response_code( $response );
		$save_error_codes = array( 404, 400 );

		// Clear the taxjar notices before calculating taxes or using cached response.
		$this->notifier->clear_notices( 'taxjar' );

		if ( false === $response ) {
			$response      = $this->smartcalcs_request( $json );
			$response_code = wp_remote_retrieve_response_code( $response );
			$body          = json_decode( wp_remote_retrieve_body( $response ) );
			if ( 'CA' !== $from_state ) {
				// If $from_state is not California, we need to check for incorrect California tax nexus.
				try {
					$this->check_for_incorrect_california_tax_nexus( $body, false, $from_state );
				} catch ( Exception $e ) {
					$this->_log( 'Error checking for incorrect California tax nexus: ' . $e->getMessage() );
				}
			}
			$is_zip_to_state_mismatch = (
				isset( $body->detail )
				&& is_string( $body->detail )
				&& $to_zip
				&& $to_state
				&& false !== strpos( $body->detail, 'to_zip ' . $to_zip )
				&& false !== strpos( $body->detail, 'to_state ' . $to_state )
			);
			$transient_set            = false;

			if ( 200 == $response_code ) {
				set_transient( $cache_key, $response, $this->cache_time );
			} elseif ( in_array( $response_code, $save_error_codes ) ) {
				if ( 400 == $response_code
					&& $is_zip_to_state_mismatch
					&& $zip_state_cache_key
				) {
					$transient_set = set_transient( $zip_state_cache_key, $response, $this->address_cache_time );
				}

				if ( ! $transient_set ) {
					set_transient( $cache_key, $response, $this->error_cache_time );
				}
			}
		}

		if ( in_array( $response_code, $save_error_codes ) ) {
			$this->_log( 'Retrieved the error from the cache. Received (' . $response['response']['code'] . '): ' . $response['body'] );
			$this->_error( 'Error retrieving the tax rates. Received (' . $response['response']['code'] . '): ' . $response['body'] );
			return false;
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

		// Validate the request before sending a request.
		if ( ! $this->validate_taxjar_request( $json ) ) {
			return false;
		}

		$this->_log( 'Requesting: ' . $path . ' - ' . $json );

		$response = $this->api_client->proxy_request(
			$path,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => $json,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->_error( 'Error retrieving the tax rates. Received (' . $response->get_error_code() . '): ' . $response->get_error_message() );
		} elseif ( 200 == $response['response']['code'] ) {
			return $response;
		} elseif ( 404 == $response['response']['code'] || 400 == $response['response']['code'] ) {
			$this->_error( 'Error retrieving the tax rates. Received (' . $response['response']['code'] . '): ' . $response['body'] );

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

		// Back up all tax rates to a csv file
		$backed_up = WC_Connect_Functions::backup_existing_tax_rates();

		if ( ! $backed_up ) {
			return;
		}

		global $wpdb;

		// Delete all tax rates
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'woocommerce_tax_rates' );
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'woocommerce_tax_rate_locations' );
	}

	/**
	 * Checks if currently on the WooCommerce order page.
	 *
	 * @return boolean
	 */
	public function on_order_page() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! isset( $screen->id ) ) {
			return false;
		}

		if ( ! function_exists( 'wc_get_page_screen_id' ) ) {
			return false;
		}

		$wc_order_screen_id = wc_get_page_screen_id( 'shop_order' );
		if ( ! $wc_order_screen_id ) {
			return false;
		}

		// If HPOS is enabled, and we're on the Orders list page, return false.
		if ( 'woocommerce_page_wc-orders' === $wc_order_screen_id && ! isset( $_GET['action'] ) ) {
			return false;
		}

		return $screen->id === $wc_order_screen_id;
	}

	/**
	 * Admin New Order Assets
	 */
	public function load_taxjar_admin_new_order_assets() {
		if ( ! $this->on_order_page() ) {
			return;
		}
		// Load Javascript for WooCommerce new order page
		wp_enqueue_script( 'wc-taxjar-order', $this->wc_connect_base_url . 'woocommerce-services-new-order-taxjar-' . WC_Connect_Loader::get_wcs_version() . '.js', array( 'jquery' ), null, true );
	}

	/**
	 * Check for incorrect California tax nexus in the TaxJar API response or cached response.
	 *
	 * @param $response_body
	 * @param $cached
	 *
	 * @return void
	 */
	private function check_for_incorrect_california_tax_nexus( $response_body, $cached, $from_state ): void {
		$log_suffix = 'in TaxJar API response.';

		if ( $cached ) {
			$response_body = json_decode( $response_body );
			$log_suffix    = 'in cached response.';
		}

		$to_state   = isset( $response_body->tax->jurisdictions->state ) ? strtoupper( $response_body->tax->jurisdictions->state ) : 'not set';
		$to_country = isset( $response_body->tax->jurisdictions->country ) ? strtoupper( $response_body->tax->jurisdictions->country ) : 'not set';
		$has_nexus  = isset( $response_body->tax->has_nexus ) ? $response_body->tax->has_nexus : null;

		if ( 'CA' === $to_state && 'US' === $to_country && true === $has_nexus ) {
			$this->_log(
				sprintf(
					'Incorrect California tax nexus detected %1$s (from_state: %2$s, to_state: %3$s, to_country: %4$s, has_nexus: %5$s).',
					$log_suffix,
					$from_state ?: 'unknown',
					$to_state,
					$to_country,
					json_encode( $has_nexus ),
				)
			);
		}

		if ( 'not set' === $to_state || 'not set' === $to_country || null === $has_nexus ) {
			throw new Exception( sprintf( 'One or more values are not set : to_state=>%1$s, to_country=>%2$s, has_nexus=>%3$s', $to_state, $to_country, json_encode( $has_nexus ) ) );
		}
	}
}
