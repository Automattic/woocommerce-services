<?php
/**
 * Shipping tax lines after a TaxJar lookup, end to end.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Shipping_Tax_Lines
 *
 * Drives the cart, classic and block checkout order creation, and the admin
 * Recalculate through the same hooks init() registers, with TaxJar mocked the way
 * it answers live: a `breakdown.shipping` whenever freight is taxable (even when
 * the request carried no shipping amount), none when it is not.
 */
class WP_Test_WC_Connect_TaxJar_Shipping_Tax_Lines extends WC_Unit_Test_Case {

	/**
	 * Store and destination addresses per state, and TaxJar's answer there.
	 *
	 * Component order is the order TaxJar's breakdown lists them in, so it is the
	 * priority order the rows are written at.
	 *
	 * @var array
	 */
	private static $places = array(
		'MI' => array(
			'store'           => array( 'MI', '49855', 'Marquette', '1 Main St' ),
			'to'              => array( 'MI', '49841', 'Gwinn' ),
			'county'          => 'MARQUETTE',
			'components'      => array(
				'city_tax_rate'        => 0.0,
				'county_tax_rate'      => 0.0,
				'special_tax_rate'     => 0.0,
				'state_sales_tax_rate' => 0.06,
			),
			'freight_taxable' => false,
		),
		'TX' => array(
			'store'           => array( 'TX', '78701', 'Austin', '100 Congress Ave' ),
			'to'              => array( 'TX', '78664', 'Round Rock' ),
			'county'          => 'WILLIAMSON',
			'components'      => array(
				'city_tax_rate'        => 0.01,
				'county_tax_rate'      => 0.0,
				'special_tax_rate'     => 0.01,
				'state_sales_tax_rate' => 0.0625,
			),
			'freight_taxable' => true,
		),
		'FL' => array(
			'store'           => array( 'FL', '33101', 'Miami', '1 Main St' ),
			'to'              => array( 'FL', '32801', 'Orlando' ),
			'county'          => 'ORANGE',
			'components'      => array(
				'city_tax_rate'        => 0.0,
				'county_tax_rate'      => 0.005,
				'special_tax_rate'     => 0.0,
				'state_sales_tax_rate' => 0.06,
			),
			'freight_taxable' => false,
		),
		'NY' => array(
			'store'           => array( 'NY', '10001', 'New York', '350 5th Ave' ),
			'to'              => array( 'NY', '10001', 'New York' ),
			'county'          => 'NEW YORK',
			'components'      => array(
				'city_tax_rate'        => 0.04875,
				'county_tax_rate'      => 0.0,
				'special_tax_rate'     => 0.0,
				'state_sales_tax_rate' => 0.04,
			),
			'freight_taxable' => true,
		),
	);

	/**
	 * Integrations hooked by hook(), unhooked in tear_down().
	 *
	 * @var WC_Connect_TaxJar_Integration[]
	 */
	private $hooked = array();

	/**
	 * Products created by a test.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-custom-surcharge.php';
	}

	/**
	 * A cart with flat rate shipping, taxes on, an empty rate table.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		WC_Helper_Shipping::create_simple_flat_rate( 10 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		WC()->customer->set_is_vat_exempt( false );

		// Look like a Store API checkout request, so calculate_totals() runs.
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/v1/checkout';
	}

	/**
	 * Undo set_up() and every hook a test added.
	 */
	public function tear_down() {
		foreach ( $this->hooked as $integration ) {
			$this->unhook( $integration );
		}
		$this->hooked = array();

		unset( $GLOBALS['wp']->query_vars['rest_route'] );
		remove_all_filters( 'woocommerce_tax_line_item_location' );
		remove_all_filters( 'woocommerce_cart_shipping_packages' );
		remove_all_filters( 'wp_doing_ajax' );
		remove_all_filters( 'woocommerce_taxjar_enable_florida_shipping_tax' );
		remove_all_filters( 'woocommerce_shipping_prices_include_tax' );
		remove_all_filters( 'woocommerce_local_pickup_methods' );

		WC()->cart->empty_cart();
		WC()->session->set( 'chosen_shipping_methods', array() );
		foreach ( $this->products as $product ) {
			WC_Helper_Product::delete_product( $product->get_id() );
		}
		$this->products = array();

		delete_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		parent::tear_down();
	}

	/**
	 * An integration for a store in `$state`, with TaxJar answering as it does there.
	 *
	 * @param string $state   Key of self::$places.
	 * @param bool   $answers False to have every TaxJar request fail.
	 * @param array  $item_components Components for line items with a product tax
	 *                                code, when they differ from the general ones.
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function taxjar( $state, $answers = true, array $item_components = array() ) {
		$place       = self::$places[ $state ];
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'smartcalcs_cache_request', 'get_store_settings', '_log' ) )
			->getMock();

		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => $place['store'][0],
				'postcode' => $place['store'][1],
				'city'     => $place['store'][2],
				'street'   => $place['store'][3],
			)
		);

		$integration->method( 'smartcalcs_cache_request' )->willReturnCallback(
			function ( $json ) use ( $place, $answers, $item_components ) {
				if ( ! $answers ) {
					return false;
				}

				$body  = json_decode( $json, true );
				$lines = array();
				foreach ( $body['line_items'] ?? array() as $line_item ) {
					$components = ( '' !== (string) $line_item['product_tax_code'] && $item_components ) ? $item_components : $place['components'];
					$amount     = $line_item['unit_price'] * $line_item['quantity'] - $line_item['discount'];
					$lines[]    = array_merge(
						array(
							'id'                => $line_item['id'],
							'tax_collectable'   => round( $amount * array_sum( $components ), 2 ),
							'combined_tax_rate' => array_sum( $components ),
						),
						$components
					);
				}

				$breakdown = array( 'line_items' => $lines );

				// Live TaxJar returns this whenever freight is taxable, also for shipping 0.
				if ( $place['freight_taxable'] ) {
					$breakdown['shipping'] = array_merge( array( 'combined_tax_rate' => array_sum( $place['components'] ) ), $place['components'] );
				}

				return array(
					'body' => wp_json_encode(
						array(
							'tax' => array(
								'rate'            => array_sum( $place['components'] ),
								'freight_taxable' => $place['freight_taxable'],
								'has_nexus'       => true,
								'jurisdictions'   => array(
									'county' => $place['county'],
									'city'   => strtoupper( (string) ( $body['to_city'] ?? '' ) ),
								),
								'breakdown'       => $breakdown,
							),
						)
					),
				);
			}
		);

		return $integration;
	}

	/**
	 * The hooks init() registers for the cart, checkout and admin paths.
	 *
	 * @return array[] [ hook, method, priority, accepted args ]
	 */
	private function hooks() {
		return array(
			array( 'woocommerce_after_calculate_totals', 'maybe_calculate_totals', 20, 1 ),
			array( 'woocommerce_before_save_order_items', 'calculate_backend_totals', 20, 1 ),
			array( 'woocommerce_order_before_calculate_taxes', 'preserve_order_taxes_on_recalculation', 10, 2 ),
			array( 'woocommerce_customer_taxable_address', 'append_base_address_to_customer_taxable_address', 10, 1 ),
			array( 'woocommerce_calc_tax', 'override_woocommerce_tax_rates', 10, 3 ),
			array( 'woocommerce_matched_rates', 'allow_street_address_for_matched_rates', 10, 2 ),
			array( 'woocommerce_cart_totals_get_item_tax_rates', 'override_cart_item_tax_rates', 10, 3 ),
			array( 'woocommerce_order_item_after_calculate_taxes', 'override_order_item_taxes', 10, 2 ),
			// Registered by some versions of the fix only.
			array( 'woocommerce_order_item_shipping_after_calculate_taxes', 'override_order_shipping_taxes', 10, 2 ),
			array( 'woocommerce_calc_shipping_tax', 'override_cart_shipping_taxes', 10, 2 ),
		);
	}

	/**
	 * Register an integration's hooks the way init() does.
	 *
	 * @param WC_Connect_TaxJar_Integration $integration Integration.
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function hook( $integration ) {
		foreach ( $this->hooks() as $hook ) {
			if ( method_exists( $integration, $hook[1] ) ) {
				add_filter( $hook[0], array( $integration, $hook[1] ), $hook[2], $hook[3] );
			}
		}
		$this->hooked[] = $integration;

		return $integration;
	}

	/**
	 * Remove an integration's hooks.
	 *
	 * @param WC_Connect_TaxJar_Integration $integration Integration.
	 */
	private function unhook( $integration ) {
		foreach ( $this->hooks() as $hook ) {
			remove_filter( $hook[0], array( $integration, $hook[1] ), $hook[2] );
		}
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
	}

	/**
	 * A merchant row with no postcode or city for the whole state.
	 *
	 * @param string $state    State.
	 * @param float  $rate     Percent.
	 * @param int    $priority Priority.
	 * @return int Rate id.
	 */
	private function merchant_state_rate( $state, $rate, $priority ) {
		return (int) WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => $state,
				'tax_rate_name'     => 'Tax',
				'tax_rate_priority' => $priority,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate'          => (string) $rate,
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * Point the customer's billing and shipping address at a place.
	 *
	 * @param array $to [ state, postcode, city ].
	 */
	private function ship_to( array $to ) {
		list( $state, $postcode, $city ) = $to;

		WC()->customer->set_billing_location( 'US', $state, $postcode, $city );
		WC()->customer->set_shipping_location( 'US', $state, $postcode, $city );
		WC()->customer->set_billing_address_1( '1 Test St' );
		WC()->customer->set_shipping_address_1( '1 Test St' );
	}

	/**
	 * A $100 simple product.
	 *
	 * @param string $tax_class Tax class slug.
	 * @return WC_Product
	 */
	private function product( $tax_class = '' ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->set_tax_class( $tax_class );
		$product->save();
		$this->products[] = $product;

		return $product;
	}

	/**
	 * Put products in the cart and calculate it, as the Store API does.
	 *
	 * @param WC_Product[] $products Products.
	 */
	private function calculate_cart( array $products ) {
		WC()->cart->empty_cart();
		foreach ( $products as $product ) {
			WC()->cart->add_to_cart( $product->get_id(), 1 );
		}
		WC()->cart->calculate_totals();
	}

	/**
	 * Create the order from the cart, as classic checkout does.
	 *
	 * @return WC_Order
	 */
	private function classic_checkout_order() {
		$customer = WC()->customer;
		$order_id = WC()->checkout()->create_order(
			array(
				'billing_email'     => 'a@b.com',
				'payment_method'    => 'bacs',
				'billing_country'   => $customer->get_billing_country(),
				'billing_state'     => $customer->get_billing_state(),
				'billing_postcode'  => $customer->get_billing_postcode(),
				'billing_city'      => $customer->get_billing_city(),
				'shipping_country'  => $customer->get_shipping_country(),
				'shipping_state'    => $customer->get_shipping_state(),
				'shipping_postcode' => $customer->get_shipping_postcode(),
				'shipping_city'     => $customer->get_shipping_city(),
			)
		);
		$this->assertIsInt( $order_id, 'The order was not created.' );

		return wc_get_order( $order_id );
	}

	/**
	 * Press Recalculate on the order in a later request, as the admin screen does.
	 *
	 * @param WC_Order $order Order.
	 * @param string   $state Store state, for a fresh integration.
	 * @param bool     $answers Whether TaxJar answers.
	 * @param callable $integration Builds the integration, when `$state`'s is not the one wanted.
	 * @return WC_Order
	 */
	private function admin_recalculate( $order, $state, $answers = true, $integration = null ) {
		foreach ( $this->hooked as $hooked ) {
			$this->unhook( $hooked );
		}
		$this->hooked = array();
		$this->hook( $integration ? $integration() : $this->taxjar( $state, $answers ) );

		$post = array(
			'order_id' => $order->get_id(),
			'country'  => $order->get_shipping_country(),
			'state'    => $order->get_shipping_state(),
			'postcode' => $order->get_shipping_postcode(),
			'city'     => $order->get_shipping_city(),
			'street'   => '1 Test St',
			'items'    => '',
		);

		$saved_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$_POST      = $post;
		add_filter( 'wp_doing_ajax', '__return_true' );
		try {
			wc_get_container()->get( \Automattic\WooCommerce\Internal\Orders\TaxesController::class )->calc_line_taxes( $post );
		} finally {
			$_POST = $saved_post;
			remove_filter( 'wp_doing_ajax', '__return_true' );
		}

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Rate ids of an order's tax items.
	 *
	 * @param WC_Order $order Order.
	 * @return int[]
	 */
	private function tax_item_rate_ids( $order ) {
		$ids = array();
		foreach ( $order->get_taxes() as $tax ) {
			$ids[] = (int) $tax->get_rate_id();
		}
		sort( $ids );

		return $ids;
	}

	/**
	 * Rate ids on the order's shipping lines.
	 *
	 * @param WC_Order $order Order.
	 * @return int[]
	 */
	private function shipping_line_rate_ids( $order ) {
		$ids = array();
		foreach ( $order->get_shipping_methods() as $shipping ) {
			$ids = array_merge( $ids, array_map( 'intval', array_keys( $shipping->get_taxes()['total'] ?? array() ) ) );
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Ids of rows named "... : Manual Rate Nullified (Automated Taxes)", the 0% rows a lookup adds beside a
	 * state-wide rate.
	 *
	 * @return int[]
	 */
	private function nullified_rate_ids() {
		global $wpdb;

		return array_map( 'intval', $wpdb->get_col( "SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_name LIKE '%Manual Rate Nullified (Automated Taxes)'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Assert an order carries no tax item or shipping tax for the 0% "Manual Rate Nullified"
	 * rows and none for the merchant's row.
	 *
	 * @param WC_Order $order    Order.
	 * @param int      $merchant Merchant rate id.
	 * @param string   $when     Where the order came from.
	 */
	private function assert_no_extra_tax_items( $order, $merchant, $when ) {
		$shadow = $this->nullified_rate_ids();

		$this->assertEmpty( array_intersect( $shadow, $this->tax_item_rate_ids( $order ) ), "$when: a Manual Rate Nullified tax item is on the order." );
		$this->assertEmpty( array_intersect( $shadow, $this->shipping_line_rate_ids( $order ) ), "$when: the shipping line references a Manual Rate Nullified row." );
		$this->assertNotContains( $merchant, $this->tax_item_rate_ids( $order ), "$when: the merchant's state-wide row was charged." );
	}

	/**
	 * Michigan does not tax shipping. A state-wide 6% at priority 5 gets a 0% row
	 * for Gwinn beside it. $100 + $10 shipping comes to $6.00 tax, and no order
	 * made from the cart, re-taxed at order creation, or recalculated from the admin
	 * carries a tax item for that 0% row.
	 */
	public function test_michigan_orders_carry_no_tax_item_for_the_zero_rate_row() {
		$merchant = $this->merchant_state_rate( 'MI', 6, 5 );
		$this->ship_to( self::$places['MI']['to'] );
		$this->hook( $this->taxjar( 'MI' ) );

		$this->calculate_cart( array( $this->product() ) );

		$this->assertCount( 1, $this->nullified_rate_ids(), 'The lookup should add one 0% row beside the state-wide rate.' );
		$this->assertEqualsWithDelta( 6.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
		$this->assertEmpty( array_intersect( $this->nullified_rate_ids(), array_keys( WC()->cart->get_shipping_taxes() ) ), 'Cart shipping taxes include the 0% row.' );

		$order = $this->classic_checkout_order();
		$this->assert_no_extra_tax_items( $order, $merchant, 'Classic checkout' );
		$this->assertEqualsWithDelta( 116.0, (float) $order->get_total(), 0.001 );

		// Block checkout re-taxes the order it builds from the cart.
		$order->calculate_totals();
		$order = wc_get_order( $order->get_id() );
		$this->assert_no_extra_tax_items( $order, $merchant, 'Block checkout' );
		$this->assertEqualsWithDelta( 116.0, (float) $order->get_total(), 0.001 );

		$order = $this->admin_recalculate( $order, 'MI' );
		$this->assert_no_extra_tax_items( $order, $merchant, 'Recalculate' );
		$this->assertEqualsWithDelta( 6.0, (float) $order->get_total_tax(), 0.001 );
	}

	/**
	 * Before any 0% row existed, a Michigan order already carried a $0 tax item for
	 * each of TaxJar's 0% components (City, County, Special District): products are
	 * taxed at every rate id TaxJar returned, and WooCommerce makes a tax item per id.
	 */
	public function test_michigan_orders_carry_zero_tax_items_for_taxjar_zero_rate_components() {
		$this->ship_to( self::$places['MI']['to'] );
		$this->hook( $this->taxjar( 'MI' ) );

		$this->calculate_cart( array( $this->product() ) );
		$order = $this->classic_checkout_order();

		$amounts = array();
		foreach ( $order->get_taxes() as $tax ) {
			$amounts[ $tax->get_label() ] = (float) $tax->get_tax_total();
		}
		ksort( $amounts );

		$this->assertSame(
			array(
				'MARQUETTE GWINN : City Tax'        => 0.0,
				'MARQUETTE GWINN : County Tax'      => 0.0,
				'MARQUETTE GWINN : Special Tax'     => 0.0,
				'MARQUETTE GWINN : State Sales Tax' => 6.0,
			),
			$amounts
		);
	}

	/**
	 * Texas taxes shipping. $100 + $10 at 8.25% is $8.25 + $0.83; the merchant's
	 * 6.25% at priority 5 is not added and no tax item is made for the 0% row.
	 */
	public function test_texas_taxes_shipping_at_the_looked_up_rate_only() {
		$merchant = $this->merchant_state_rate( 'TX', 6.25, 5 );
		$this->ship_to( self::$places['TX']['to'] );
		$this->hook( $this->taxjar( 'TX' ) );

		$this->calculate_cart( array( $this->product() ) );

		$this->assertEqualsWithDelta( 8.25, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.83, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );

		$order = $this->classic_checkout_order();
		$this->assert_no_extra_tax_items( $order, $merchant, 'Classic checkout' );

		$order->calculate_totals();
		$order = wc_get_order( $order->get_id() );
		$this->assert_no_extra_tax_items( $order, $merchant, 'Block checkout' );
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.011, 'Block checkout shipping tax' );

		$order = $this->admin_recalculate( $order, 'TX' );
		$this->assert_no_extra_tax_items( $order, $merchant, 'Recalculate' );
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.011, 'Recalculate shipping tax' );
		$this->assertEqualsWithDelta( 8.25, (float) $order->get_cart_tax(), 0.011, 'Recalculate item tax' );
	}

	/**
	 * Florida to Florida: TaxJar says freight is not taxable (no shipping breakdown),
	 * but the plugin writes the rows as shipping-taxable on purpose (the
	 * `woocommerce_taxjar_enable_florida_shipping_tax` filter). Shipping stays taxed at
	 * the rows' 6.5%: $0.65 on $10, at checkout and on Recalculate.
	 */
	public function test_florida_shipping_stays_taxed() {
		$this->ship_to( self::$places['FL']['to'] );
		$this->hook( $this->taxjar( 'FL' ) );

		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 6.5, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.65, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );

		$order = $this->classic_checkout_order();
		$order->calculate_totals();
		$this->assertEqualsWithDelta( 0.65, (float) wc_get_order( $order->get_id() )->get_shipping_tax(), 0.001, 'Block checkout shipping tax' );

		$order = $this->admin_recalculate( wc_get_order( $order->get_id() ), 'FL' );
		$this->assertEqualsWithDelta( 0.65, (float) $order->get_shipping_tax(), 0.001, 'Recalculate shipping tax' );
	}

	/**
	 * A store taxing at the billing address: checkout sends TaxJar no shipping
	 * amount, but TaxJar still returns the shipping breakdown where freight is
	 * taxable. $10 shipping in New York City at 8.875% is $0.89.
	 */
	public function test_billing_based_store_still_taxes_shipping() {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		$this->ship_to( self::$places['NY']['to'] );
		$this->hook( $this->taxjar( 'NY' ) );

		$this->calculate_cart( array( $this->product() ) );

		$this->assertEqualsWithDelta( 8.88, (float) WC()->cart->get_cart_contents_tax(), 0.011, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.89, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );
	}

	/**
	 * A product a plugin taxes at the store (a booking, say) beside one shipped out
	 * of state. The parcel leaves Texas, so shipping is not taxed; the store's
	 * shipping components must not be applied to it.
	 */
	public function test_shipping_out_of_state_is_not_taxed_at_the_store_rate() {
		$this->ship_to( array( 'OK', '73102', 'Oklahoma City' ) );
		$this->hook( $this->taxjar( 'TX' ) );

		$booking = $this->product();
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) use ( $booking ) {
				return $product->get_id() === $booking->get_id() ? 'base' : $location;
			},
			10,
			2
		);

		$this->calculate_cart( array( $booking, $this->product() ) );

		$this->assertEqualsWithDelta( 8.25, (float) WC()->cart->get_cart_contents_tax(), 0.011, 'Only the booking is taxed, at the store.' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
	}

	/**
	 * The plugin leaves a row's Shipping checkbox as the merchant set it
	 * (create_or_update_tax_rate()). With shipping unticked on the Round Rock rows,
	 * shipping is not taxed.
	 */
	public function test_merchant_unticked_shipping_on_looked_up_rows_is_kept() {
		$this->ship_to( self::$places['TX']['to'] );
		$this->hook( $this->taxjar( 'TX' ) );
		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 0.83, (float) WC()->cart->get_shipping_tax(), 0.011, 'Shipping is taxed before the change.' );

		global $wpdb;
		$wpdb->query( "UPDATE {$wpdb->prefix}woocommerce_tax_rates SET tax_rate_shipping = 0" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
	}

	/**
	 * A shipping tax class the merchant chose keeps applying: shipping is taxed from
	 * that class's rows (here 5% for products with tax code 20010), not from the
	 * standard-rate shipping components.
	 */
	public function test_shipping_tax_class_setting_is_kept() {
		$created = WC_Tax::create_tax_class( 'Clothing 20010' );
		$slug    = is_wp_error( $created ) ? 'clothing-20010' : $created['slug'];
		update_option( 'woocommerce_shipping_tax_class', $slug );

		$this->ship_to( self::$places['TX']['to'] );
		$this->hook(
			$this->taxjar(
				'TX',
				true,
				array(
					'city_tax_rate'        => 0.0,
					'county_tax_rate'      => 0.0,
					'special_tax_rate'     => 0.0,
					'state_sales_tax_rate' => 0.05,
				)
			)
		);

		$this->calculate_cart( array( $this->product( $slug ) ) );

		$this->assertEqualsWithDelta( 5.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.5, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );

		WC_Tax::delete_tax_class_by( 'slug', $slug );
	}

	/**
	 * TaxJar unreachable for a town looked up before: WooCommerce taxes from the
	 * table, which gives the looked-up rate, and shipping carries no 0% row.
	 */
	public function test_unreachable_taxjar_leaves_the_table_result_without_the_zero_rate_row_on_shipping() {
		$merchant = $this->merchant_state_rate( 'MI', 6, 5 );
		$this->ship_to( self::$places['MI']['to'] );

		$this->hook( $this->taxjar( 'MI' ) );
		$this->calculate_cart( array( $this->product() ) );
		$this->unhook( $this->hooked[0] );
		$this->hooked = array();

		$this->hook( $this->taxjar( 'MI', false ) );
		$this->calculate_cart( array( $this->product() ) );

		$this->assertEqualsWithDelta( 6.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
		$this->assertEmpty( array_intersect( $this->nullified_rate_ids(), array_keys( WC()->cart->get_shipping_taxes() ) ), 'Cart shipping taxes include the 0% row.' );
		$this->assertArrayNotHasKey( $merchant, WC()->cart->get_cart_contents_taxes() );
	}

	/**
	 * A merchant row at priority 1 is outranked by the City component, so nothing
	 * is added beside it and the order is taxed as before.
	 */
	public function test_merchant_rate_at_priority_one_is_unchanged() {
		$merchant = $this->merchant_state_rate( 'MI', 6, 1 );
		$this->ship_to( self::$places['MI']['to'] );
		$this->hook( $this->taxjar( 'MI' ) );

		$this->calculate_cart( array( $this->product() ) );

		$this->assertSame( array(), $this->nullified_rate_ids() );
		$this->assertEqualsWithDelta( 6.0, (float) WC()->cart->get_cart_contents_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001 );

		$order = $this->classic_checkout_order();
		$this->assertNotContains( $merchant, $this->tax_item_rate_ids( $order ) );
		$this->assertCount( 4, $order->get_taxes() );
	}

	/**
	 * Two shipping packages are each taxed at the looked-up rate, with no 0% row.
	 *
	 * @testWith ["TX", 1.65]
	 *           ["MI", 0.0]
	 *
	 * @param string $state    Store state.
	 * @param float  $expected Shipping tax on two $10 packages.
	 */
	public function test_each_shipping_package_is_taxed_at_the_looked_up_rate( $state, $expected ) {
		$this->merchant_state_rate( $state, 6, 5 );
		$this->ship_to( self::$places[ $state ]['to'] );
		$this->hook( $this->taxjar( $state ) );

		add_filter(
			'woocommerce_cart_shipping_packages',
			function ( $packages ) {
				$split = array();
				foreach ( $packages[0]['contents'] as $key => $item ) {
					$split[] = array_merge(
						$packages[0],
						array(
							'contents'      => array( $key => $item ),
							'contents_cost' => $item['line_total'],
						)
					);
				}

				return $split;
			}
		);
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate', 'flat_rate' ) );

		$this->calculate_cart( array( $this->product(), $this->product() ) );

		$this->assertEqualsWithDelta( 20.0, (float) WC()->cart->get_shipping_total(), 0.001, 'Two packages at $10.' );
		$this->assertEqualsWithDelta( $expected, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );
		$this->assertEmpty( array_intersect( $this->nullified_rate_ids(), array_keys( WC()->cart->get_shipping_taxes() ) ), 'Cart shipping taxes include the 0% row.' );
	}

	/**
	 * A merchant row limited to places, with Shipping ticked.
	 *
	 * @param string $state     State.
	 * @param string $postcodes Postcodes, `;` separated.
	 * @param string $cities    Cities, `;` separated.
	 * @param float  $rate      Percent.
	 * @return int Rate id.
	 */
	private function merchant_local_rate( $state, $postcodes, $cities, $rate ) {
		$id = (int) WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => $state,
				'tax_rate_name'     => 'Merchant Local',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate'          => (string) $rate,
				'tax_rate_class'    => '',
			)
		);
		WC_Tax::_update_tax_rate_postcodes( $id, $postcodes );
		WC_Tax::_update_tax_rate_cities( $id, $cities );

		return $id;
	}

	/**
	 * An integration for a New York store, with TaxJar answering Brooklyn as it does live
	 * (read 2026-11-03): clothing under $110 is exempt, and shipping is only taxable when
	 * something in the request is.
	 *
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function taxjar_new_york_clothing() {
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'smartcalcs_cache_request', 'get_store_settings', '_log' ) )
			->getMock();

		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'NY',
				'postcode' => '10001',
				'city'     => 'New York',
				'street'   => '350 5th Ave',
			)
		);

		$integration->method( 'smartcalcs_cache_request' )->willReturnCallback(
			function ( $json ) {
				$body       = json_decode( $json, true );
				$taxed      = array(
					'city_tax_rate'        => 0.04875,
					'county_tax_rate'      => 0.0,
					'special_tax_rate'     => 0.0,
					'state_sales_tax_rate' => 0.04,
				);
				$lines      = array();
				$is_taxable = false;
				foreach ( $body['line_items'] as $line_item ) {
					$exempt     = in_array( (string) $line_item['product_tax_code'], array( '20010', '99999' ), true );
					$components = $exempt ? array_map( '__return_zero', $taxed ) : $taxed;
					$is_taxable = $is_taxable || ! $exempt;
					$lines[]    = array_merge(
						array(
							'id'                => $line_item['id'],
							'tax_collectable'   => round( $line_item['unit_price'] * $line_item['quantity'] * array_sum( $components ), 2 ),
							'combined_tax_rate' => array_sum( $components ),
						),
						$components
					);
				}

				$breakdown = array( 'line_items' => $lines );
				if ( $is_taxable ) {
					$breakdown['shipping'] = array_merge( array( 'combined_tax_rate' => array_sum( $taxed ) ), $taxed );
				}

				return array(
					'body' => wp_json_encode(
						array(
							'tax' => array(
								'rate'            => $is_taxable ? array_sum( $taxed ) : 0,
								'freight_taxable' => $is_taxable,
								'has_nexus'       => true,
								'jurisdictions'   => array(
									'county' => 'KINGS',
									'city'   => 'BROOKLYN',
								),
								'breakdown'       => $breakdown,
							),
						)
					),
				);
			}
		);

		return $integration;
	}

	/**
	 * The rows a merchant can limit to a place, the way WOOTAX-396's three kinds rank.
	 *
	 * @return array[] [ postcodes, cities ]
	 */
	public function merchant_local_rows() {
		return array(
			'ZIP list'     => array( '49866;49849', '' ),
			'ZIP wildcard' => array( '49*', '' ),
			'city'         => array( '', 'NEGAUNEE' ),
		);
	}

	/**
	 * The steps on the ticket: $100 to Negaunee, MI, with a merchant's 6% row covering the
	 * town. Michigan does not tax shipping that is listed separately, so tax is $6.00 on
	 * the item and nothing on $10 shipping: $116.00, at checkout and on Recalculate.
	 *
	 * @dataProvider merchant_local_rows
	 *
	 * @param string $postcodes Postcodes of the merchant's row.
	 * @param string $cities    Cities of the merchant's row.
	 */
	public function test_merchant_local_row_does_not_tax_shipping_where_taxjar_does_not( $postcodes, $cities ) {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$merchant = $this->merchant_local_rate( 'MI', $postcodes, $cities, 6 );
		$this->ship_to( array( 'MI', '49866', 'Negaunee' ) );
		$this->hook( $this->taxjar( 'MI' ) );

		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 6.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
		$this->assertEqualsWithDelta( 116.0, (float) WC()->cart->get_total( 'edit' ), 0.001, 'Cart total' );

		$order = $this->classic_checkout_order();
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001, 'Classic checkout shipping tax' );
		$this->assertSame( array(), $this->shipping_line_rate_ids( $order ), 'Classic checkout shipping line' );
		$this->assertNotContains( $merchant, $this->tax_item_rate_ids( $order ), 'Classic checkout' );

		// Block checkout re-taxes the order it builds from the cart.
		$order->calculate_totals();
		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001, 'Block checkout shipping tax' );
		$this->assertEqualsWithDelta( 116.0, (float) $order->get_total(), 0.001, 'Block checkout total' );

		$order = $this->admin_recalculate( $order, 'MI' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001, 'Recalculate shipping tax' );
		$this->assertEqualsWithDelta( 6.0, (float) $order->get_cart_tax(), 0.001, 'Recalculate item tax' );
		$this->assertEqualsWithDelta( 116.0, (float) $order->get_total(), 0.001, 'Recalculate total' );
		$this->assertNotContains( $merchant, $this->tax_item_rate_ids( $order ), 'Recalculate' );
	}

	/**
	 * The merchant's row is not changed, and keeps taxing shipping for what no lookup
	 * covered: its other ZIP code when read from the table, and that ZIP code at checkout
	 * when TaxJar does not answer.
	 */
	public function test_merchant_local_row_still_taxes_shipping_where_no_lookup_answered() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$merchant = $this->merchant_local_rate( 'MI', '49866;49849', '', 6 );
		$this->ship_to( array( 'MI', '49866', 'Negaunee' ) );
		$this->hook( $this->taxjar( 'MI' ) );
		$this->calculate_cart( array( $this->product() ) );

		$row = WC_Tax::_get_tax_rate( $merchant );
		$this->assertSame( 'Merchant Local', $row['tax_rate_name'] );
		$this->assertEqualsWithDelta( 6.0, (float) $row['tax_rate'], 0.001 );
		$this->assertSame(
			array( $merchant ),
			array_keys(
				WC_Tax::find_shipping_rates(
					array(
						'country'  => 'US',
						'state'    => 'MI',
						'postcode' => '49849',
						'city'     => 'ISHPEMING',
					)
				)
			)
		);

		$this->unhook( $this->hooked[0] );
		$this->hooked = array();
		$this->ship_to( array( 'MI', '49849', 'Ishpeming' ) );
		$this->hook( $this->taxjar( 'MI', false ) );
		$this->calculate_cart( array( $this->product() ) );

		$this->assertEqualsWithDelta( 0.6, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
		$this->assertSame( array( $merchant ), array_keys( WC()->cart->get_shipping_taxes() ) );
	}

	/**
	 * Where TaxJar taxes shipping, it is TaxJar's rate that is charged, and not the
	 * merchant's 5% row covering the town: $10 at 8.25% is $0.83, at checkout and on
	 * Recalculate.
	 */
	public function test_merchant_local_row_does_not_replace_the_shipping_rate_taxjar_returned() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$merchant = $this->merchant_local_rate( 'TX', '78664;78665', '', 5 );
		$this->ship_to( self::$places['TX']['to'] );
		$this->hook( $this->taxjar( 'TX' ) );

		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 0.83, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );
		$this->assertArrayNotHasKey( $merchant, WC()->cart->get_shipping_taxes() );

		$order = $this->classic_checkout_order();
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.011, 'Classic checkout shipping tax' );
		$this->assertNotContains( $merchant, $this->shipping_line_rate_ids( $order ), 'Classic checkout' );

		$order->calculate_totals();
		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.011, 'Block checkout shipping tax' );
		$this->assertNotContains( $merchant, $this->shipping_line_rate_ids( $order ), 'Block checkout' );

		$order = $this->admin_recalculate( $order, 'TX' );
		$this->assertEqualsWithDelta( 0.83, (float) $order->get_shipping_tax(), 0.011, 'Recalculate shipping tax' );
		$this->assertNotContains( $merchant, $this->shipping_line_rate_ids( $order ), 'Recalculate' );
	}

	/**
	 * The report on the ticket: an order of a standard item to Brooklyn leaves the town's
	 * standard rows marked for shipping, and shipping is always read from those. Clothing
	 * under $110 is exempt there and TaxJar says its shipping is not taxable, so a
	 * clothing-only order pays no tax at all.
	 */
	public function test_exempt_clothing_order_pays_no_shipping_tax_after_a_taxable_order_to_the_same_town() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$created = WC_Tax::create_tax_class( 'Clothing - 20010' );
		$slug    = is_wp_error( $created ) ? 'clothing-20010' : $created['slug'];

		$this->ship_to( array( 'NY', '11201', 'Brooklyn' ) );
		$this->hook( $this->taxjar_new_york_clothing() );

		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 0.89, (float) WC()->cart->get_shipping_tax(), 0.011, 'A standard item: shipping is taxed.' );

		$clothing = $this->product( $slug );
		$this->calculate_cart( array( $clothing ) );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );

		$order = $this->classic_checkout_order();
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001, 'Classic checkout tax' );

		$order->calculate_totals();
		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001, 'Block checkout tax' );

		$order = $this->admin_recalculate( $order, 'NY', true, array( $this, 'taxjar_new_york_clothing' ) );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001, 'Recalculate tax' );
		$this->assertEqualsWithDelta( 110.0, (float) $order->get_total(), 0.001, 'Recalculate total' );

		WC_Tax::delete_tax_class_by( 'slug', $slug );
	}

	/**
	 * Clothing beside a standard item: TaxJar taxes the standard item and the shipping.
	 * $100 and $10 at 8.875% are $8.88 and $0.89.
	 */
	public function test_clothing_beside_a_taxable_item_keeps_shipping_taxed() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$created = WC_Tax::create_tax_class( 'Clothing - 20010' );
		$slug    = is_wp_error( $created ) ? 'clothing-20010' : $created['slug'];

		$this->ship_to( array( 'NY', '11201', 'Brooklyn' ) );
		$this->hook( $this->taxjar_new_york_clothing() );

		$this->calculate_cart( array( $this->product( $slug ), $this->product() ) );

		$this->assertEqualsWithDelta( 8.88, (float) WC()->cart->get_cart_contents_tax(), 0.011, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.89, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );

		WC_Tax::delete_tax_class_by( 'slug', $slug );
	}

	/**
	 * Turning Florida's rule off means TaxJar's "not taxable" counts, also for rows the
	 * rule marked for shipping earlier.
	 */
	public function test_florida_rule_turned_off_follows_taxjar() {
		$this->ship_to( self::$places['FL']['to'] );
		$this->hook( $this->taxjar( 'FL' ) );
		$this->calculate_cart( array( $this->product() ) );
		$this->assertEqualsWithDelta( 0.65, (float) WC()->cart->get_shipping_tax(), 0.001, 'With the rule' );

		add_filter( 'woocommerce_taxjar_enable_florida_shipping_tax', '__return_false' );
		$this->calculate_cart( array( $this->product() ) );

		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Without the rule' );
	}

	/**
	 * A lookup that fails after one that answered must not reuse that answer: the customer
	 * moved to another town, so the table decides again.
	 */
	public function test_failed_lookup_does_not_reuse_the_earlier_shipping_answer() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$this->merchant_local_rate( 'MI', '49866;49849', '', 6 );
		$this->ship_to( array( 'MI', '49866', 'Negaunee' ) );

		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_taxes_by_location', 'get_store_settings', '_log' ) )
			->getMock();
		$integration->method( 'calculate_taxes_by_location' )->willReturn( false );

		$property = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'response_shipping_answer' );
		$property->setAccessible( true );
		$property->setValue(
			$integration,
			array(
				'address' => array( 'US', 'MI', '49866', 'NEGAUNEE' ),
				'rates'   => array(),
			)
		);

		$this->hook( $integration );
		$this->calculate_cart( array( $this->product() ) );

		$this->assertNull( $property->getValue( $integration ) );
		$this->assertEqualsWithDelta( 0.6, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
	}

	/**
	 * Another order in the checkout's request, going to another town, is not taxed from
	 * the cart's answer: its shipping stays what the table gives that town.
	 */
	public function test_cart_answer_is_not_applied_to_an_order_going_elsewhere() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$merchant = $this->merchant_local_rate( 'MI', '49866;49849', '', 6 );
		$this->ship_to( array( 'MI', '49866', 'Negaunee' ) );
		$this->hook( $this->taxjar( 'MI' ) );
		$this->calculate_cart( array( $this->product() ) );

		$order = $this->classic_checkout_order();
		$order->set_shipping_postcode( '49849' );
		$order->set_shipping_city( 'Ishpeming' );
		$order->save();
		$order->calculate_taxes();

		$this->assertSame( array( $merchant ), $this->shipping_line_rate_ids( $order ) );
		$this->assertEqualsWithDelta( 0.6, (float) $order->get_shipping_tax(), 0.001 );
	}

	/**
	 * Only the answer about the parcel counts. A booking taxed at the store and a clothing
	 * parcel going to the store's own town are two requests to the same address: shipping
	 * follows the parcel's, whichever comes first.
	 *
	 * @testWith [true]
	 *           [false]
	 *
	 * @param bool $booking_first Whether the booking is first in the cart.
	 */
	public function test_shipping_follows_the_answer_for_the_shipped_items_at_the_same_address( $booking_first ) {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$created = WC_Tax::create_tax_class( 'Clothing - 20010' );
		$slug    = is_wp_error( $created ) ? 'clothing-20010' : $created['slug'];

		$this->ship_to( array( 'NY', '10001', 'New York' ) );
		$this->hook( $this->taxjar_new_york_clothing() );

		$booking = $this->product();
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) use ( $booking ) {
				return $product->get_id() === $booking->get_id() ? 'base' : $location;
			},
			10,
			2
		);

		$clothing = $this->product( $slug );
		$this->calculate_cart( $booking_first ? array( $booking, $clothing ) : array( $clothing, $booking ) );

		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );

		WC_Tax::delete_tax_class_by( 'slug', $slug );
	}

	/**
	 * Local pickup is taxed at the store, so the answer for the store decides a pickup fee.
	 * $10 at Austin's looked-up 8.25%, not at the merchant's 5% row: $0.83.
	 */
	public function test_local_pickup_fee_is_taxed_from_the_answer_for_the_store() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		update_option( 'woocommerce_default_country', 'US:TX' );
		update_option( 'woocommerce_store_postcode', '78701' );
		update_option( 'woocommerce_store_city', 'Austin' );
		add_filter(
			'woocommerce_local_pickup_methods',
			function () {
				return array( 'legacy_flat_rate' );
			}
		);

		$merchant = $this->merchant_local_rate( 'TX', '78701;78702', '', 5 );
		$this->ship_to( self::$places['TX']['to'] );
		$this->hook( $this->taxjar( 'TX' ) );

		$this->calculate_cart( array( $this->product() ) );

		$this->assertSame( '78701', WC_Tax::get_tax_location()[2], 'The fee is taxed at the store.' );
		$this->assertEqualsWithDelta( 0.83, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );
		$this->assertArrayNotHasKey( $merchant, WC()->cart->get_shipping_taxes() );
	}

	/**
	 * With shipping prices that include tax (WooCommerce 10.6+), the tax is taken out of
	 * the price: $10 at 8.25% holds $0.76, leaving $9.24.
	 */
	public function test_shipping_prices_that_include_tax_are_taxed_inclusively() {
		add_filter( 'woocommerce_shipping_prices_include_tax', '__return_true' );
		update_option( 'woocommerce_shipping_tax_class', '' );
		$this->merchant_local_rate( 'TX', '78664;78665', '', 5 );
		$this->ship_to( self::$places['TX']['to'] );
		$this->hook( $this->taxjar( 'TX' ) );

		$this->calculate_cart( array( $this->product() ) );

		$this->assertEqualsWithDelta( 0.76, (float) WC()->cart->get_shipping_tax(), 0.011, 'Cart shipping tax' );
		$this->assertEqualsWithDelta( 9.24, (float) WC()->cart->get_shipping_total(), 0.011, 'Cart shipping total' );
	}

	/**
	 * Two methods offered: each is taxed from the answer at its own price, also one that
	 * is not the method the cart's lookup was asked about.
	 */
	public function test_every_offered_shipping_method_is_taxed_from_the_answer() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$this->merchant_local_rate( 'TX', '78664;78665', '', 5 );
		$this->ship_to( self::$places['TX']['to'] );

		$integration = $this->hook( $this->taxjar( 'TX' ) );
		$this->calculate_cart( array( $this->product() ) );

		$taxes = $integration->override_cart_shipping_taxes( array( 1 => 99.0 ), 25.0 );

		$this->assertEqualsWithDelta( 2.0625, array_sum( $taxes ), 0.001 );
	}

	/**
	 * Nothing changes before a lookup has answered, or with an input a callback broke.
	 */
	public function test_cart_shipping_taxes_are_left_alone_without_an_answer() {
		$integration = $this->taxjar( 'TX' );

		$this->assertSame( array( 7 => 1.5 ), $integration->override_cart_shipping_taxes( array( 7 => 1.5 ), 10.0 ) );
		$this->assertSame( 'not an array', $integration->override_cart_shipping_taxes( 'not an array', 10.0 ) );
	}

	/**
	 * init() registers the two shipping hooks, with the arguments they need.
	 */
	public function test_init_registers_the_shipping_hooks() {
		update_option( 'wc_connect_taxes_enabled', 'yes' );

		$integration = new WC_Connect_TaxJar_Integration(
			$this->createMock( WC_Connect_API_Client::class ),
			$this->createMock( WC_Connect_Logger::class ),
			'https://example.com',
			$this->createMock( WC_Connect_Tracks::class )
		);
		$integration->init();

		$this->assertSame( 10, has_filter( 'woocommerce_calc_shipping_tax', array( $integration, 'override_cart_shipping_taxes' ) ) );
		$this->assertSame( 10, has_action( 'woocommerce_order_item_shipping_after_calculate_taxes', array( $integration, 'override_order_shipping_taxes' ) ) );

		global $wp_filter;
		$callback = $wp_filter['woocommerce_order_item_shipping_after_calculate_taxes']->callbacks[10][ _wp_filter_build_unique_id( '', array( $integration, 'override_order_shipping_taxes' ), 10 ) ];
		$this->assertSame( 2, $callback['accepted_args'] );
	}

	/**
	 * A lookup that says shipping is taxable without saying at what rate has nothing to
	 * use, so the table's result stands.
	 */
	public function test_taxable_answer_without_a_shipping_breakdown_decides_nothing() {
		$integration = $this->taxjar( 'TX' );
		$method      = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'get_shipping_answer' );
		$method->setAccessible( true );

		$answer = $method->invoke(
			$integration,
			array(
				'freight_taxable' => 1,
				'rate_ids'        => array(),
			),
			array(
				'to_country' => 'US',
				'from_state' => 'TX',
				'to_state'   => 'TX',
				'to_zip'     => '78664',
				'to_city'    => 'Round Rock',
			)
		);

		$this->assertNull( $answer );
	}

	/**
	 * A plugin shipping a cart's packages to several addresses moves the customer for each:
	 * the answer is about the address looked up, so another one stays with the table.
	 */
	public function test_cart_answer_is_not_applied_to_a_rate_for_another_address() {
		update_option( 'woocommerce_shipping_tax_class', '' );
		$this->merchant_local_rate( 'MI', '49866;49849', '', 6 );
		$this->ship_to( array( 'MI', '49866', 'Negaunee' ) );
		$integration = $this->hook( $this->taxjar( 'MI' ) );
		$this->calculate_cart( array( $this->product() ) );

		$this->assertSame( array(), $integration->override_cart_shipping_taxes( array( 1 => 0.6 ), 10.0 ), 'The address looked up' );

		$this->ship_to( array( 'MI', '49849', 'Ishpeming' ) );
		$this->assertSame( array( 1 => 0.6 ), $integration->override_cart_shipping_taxes( array( 1 => 0.6 ), 10.0 ), 'Another address' );
	}

	/**
	 * Each answer an admin recalculation looked up applies to that recalculation. A later
	 * one in the same request, with no lookup of its own, stays with the table.
	 */
	public function test_admin_recalculation_answer_is_used_once() {
		$integration = $this->taxjar( 'MI' );

		$order = new WC_Order();
		$order->save();
		$item = new WC_Order_Item_Shipping();
		$item->set_order_id( $order->get_id() );
		$item->set_total( '10' );
		$item->set_taxes( array( 'total' => array( 5 => '0.6' ) ) );
		$item->save();

		$property = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'admin_recalculation_shipping_answers' );
		$property->setAccessible( true );
		$property->setValue(
			$integration,
			array(
				$order->get_id() => array(
					$item->get_id() => array(
						'address' => array( 'US', 'MI', '49866', 'NEGAUNEE' ),
						'rates'   => array(),
					),
				),
			)
		);

		$integration->override_order_shipping_taxes( $item );
		$this->assertSame( array(), $item->get_taxes()['total'], 'The recalculation it was looked up for' );

		$item->set_taxes( array( 'total' => array( 5 => '0.6' ) ) );
		$integration->override_order_shipping_taxes( $item );
		$this->assertSame( array( 5 => '0.6' ), $item->get_taxes()['total'], 'A later one' );
	}

	/**
	 * A shipping line that is not taxable keeps no tax, as WooCommerce left it, whatever
	 * the lookup said.
	 */
	public function test_shipping_line_that_is_not_taxable_is_left_alone() {
		$integration = $this->hook( $this->taxjar( 'TX' ) );
		$this->ship_to( self::$places['TX']['to'] );
		$this->calculate_cart( array( $this->product() ) );

		$item = new class() extends WC_Order_Item_Shipping {
			/**
			 * This line is not taxable.
			 *
			 * @param string $context Unused.
			 * @return string
			 */
			public function get_tax_status( $context = 'view' ) {
				return 'none';
			}
		};
		$item->set_total( '10' );

		$integration->override_order_shipping_taxes(
			$item,
			array(
				'country'  => 'US',
				'state'    => 'TX',
				'postcode' => '78664',
				'city'     => 'Round Rock',
			)
		);

		$this->assertSame( array(), $item->get_taxes()['total'] );
	}

	/**
	 * A cart totalled during the main cart's total (a Subscriptions recurring cart) gives
	 * the main cart its answer back, like the rest of what the main cart's lookup said.
	 */
	public function test_nested_cart_gives_the_main_cart_its_shipping_answer_back() {
		$this->ship_to( self::$places['MI']['to'] );
		$integration = $this->hook( $this->taxjar( 'MI' ) );

		$property = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'response_shipping_answer' );
		$property->setAccessible( true );

		$depth = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'main_cart_total_depth' );
		$depth->setAccessible( true );
		$depth->setValue( $integration, 1 );
		$property->setValue( $integration, array( 'main' ) );

		WC()->cart->add_to_cart( $this->product()->get_id(), 1 );
		$other = clone WC()->cart;
		$integration->calculate_totals( $other );

		$this->assertSame( array( 'main' ), $property->getValue( $integration ) );
	}
}
