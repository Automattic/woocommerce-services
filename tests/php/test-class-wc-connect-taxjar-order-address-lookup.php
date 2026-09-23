<?php
/**
 * Tests for the live TaxJar lookup on orders created or re-addressed outside the cart.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Order_Address_Lookup
 *
 * The store is in Denver, CO 80202. A fake TaxJar answers with fixed rates per ZIP:
 *
 *   80202 (Denver)   state 2.9% + city 3.1% = 6%
 *   80301 (Boulder)  state 2.9% + city 5.1% = 8%
 *   other CO ZIPs    state 2.9%
 *
 * Out-of-state US addresses never reach TaxJar (the store has nexus in CO only).
 *
 * The placed order used by most tests was taxed at 6% in Denver:
 *
 *   Product A   $10.00  tax 0.60
 *   Product B   $20.00  tax 1.20
 *   Shipping    $10.00  tax 0.60
 *   ------------------------------
 *   cart tax 1.80, shipping tax 0.60, total 42.40
 *
 * Shipping is $10 so that every rate splits into whole cents: WooCommerce rounds
 * shipping tax per rate, which would otherwise blur the expected figures.
 *
 * The rule under test: TaxJar is asked only when the address the order is taxed on
 * changed, or when a new order has no tax yet. If it cannot answer, the tax the
 * order had is kept and a note says so.
 */
class WP_Test_WC_Connect_TaxJar_Order_Address_Lookup extends WC_Unit_Test_Case {

	/**
	 * Integration under test.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $integration;

	/**
	 * Request bodies sent to the fake TaxJar, decoded.
	 *
	 * @var array[]
	 */
	private $requests = array();

	/**
	 * When true the fake TaxJar fails every request.
	 *
	 * @var bool
	 */
	private $taxjar_down = false;

	/**
	 * When true the fake TaxJar exempts lines of $20 or more from the state rate, the
	 * way thresholds (such as clothing in some states) make two lines differ.
	 *
	 * @var bool
	 */
	private $state_threshold = false;

	/**
	 * Rate id the fixture order was taxed with.
	 *
	 * @var int
	 */
	private $rate_id;

	/**
	 * Products created for the fixture, deleted on tear down.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Load the integration's dependencies.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-custom-surcharge.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-functions.php';
	}

	/**
	 * Enable automated taxes against a fake TaxJar and register the hooks as a store does.
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_default_country', 'US:CO' );
		update_option( 'woocommerce_store_address', '1 Store St' );
		update_option( 'woocommerce_store_city', 'Denver' );
		update_option( 'woocommerce_store_postcode', '80202' );
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'yes' );

		$this->requests        = array();
		$this->taxjar_down     = false;
		$this->state_threshold = false;

		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock();
		$api_client->method( 'proxy_request' )->willReturnCallback( array( $this, 'fake_taxjar' ) );
		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock();

		// Real notifier: the request path clears its notices, a no-op without a WC session.
		$notifier = new Automattic\WCServices\StoreNotices\StoreNoticesNotifier( false );

		$this->integration = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks, $notifier );
		$this->integration->init();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Remove the fixture rows, products and options.
	 */
	public function tear_down() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%tj_tax_%'" );
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		foreach ( $this->products as $product ) {
			$product->delete( true );
		}
		$this->products = array();

		remove_all_filters( 'woocommerce_order_is_vat_exempt' );
		foreach ( array( 'woocommerce_calc_taxes', 'woocommerce_tax_based_on', 'woocommerce_store_address', 'woocommerce_store_city', 'woocommerce_store_postcode', WC_Connect_TaxJar_Integration::OPTION_NAME ) as $option ) {
			delete_option( $option );
		}

		parent::tear_down();
	}

	/**
	 * Fake TaxJar proxy: records the request and answers with the rates in the class docblock.
	 *
	 * @param string $path Proxy path.
	 * @param array  $args Request args.
	 * @return array|WP_Error
	 */
	public function fake_taxjar( $path, $args ) {
		$body             = json_decode( $args['body'], true );
		$this->requests[] = $body;

		if ( $this->taxjar_down ) {
			return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		}

		$rates = array( 'state_tax_rate' => 0.029 );
		if ( '80202' === $body['to_zip'] ) {
			$rates['city_tax_rate'] = 0.031;
		} elseif ( '80301' === $body['to_zip'] ) {
			$rates['city_tax_rate'] = 0.051;
		}
		$combined = array_sum( $rates );

		$line_items = array();
		foreach ( $body['line_items'] ?? array() as $line_item ) {
			$line_rates = $rates;
			if ( '99999' === (string) $line_item['product_tax_code'] ) {
				$line_rates = array_map( '__return_zero', $rates );
			} elseif ( $this->state_threshold && (float) $line_item['unit_price'] >= 20 ) {
				$line_rates['state_tax_rate'] = 0;
			}
			$line_items[] = array_merge(
				array( 'id' => $line_item['id'] ),
				$line_rates,
				array( 'combined_tax_rate' => array_sum( $line_rates ) )
			);
		}

		return array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'tax' => array(
						'rate'            => $combined,
						'has_nexus'       => true,
						'freight_taxable' => true,
						'jurisdictions'   => array(
							'country' => 'US',
							'state'   => 'CO',
							'county'  => 'DENVER',
							'city'    => 'DENVER',
						),
						'breakdown'       => array(
							'line_items' => $line_items,
							'shipping'   => array_merge( $rates, array( 'combined_tax_rate' => $combined ) ),
						),
					),
				)
			),
		);
	}

	// -------------------------------------------------------------------------
	// Fixture helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a simple product.
	 *
	 * @param string $price Regular price.
	 * @return WC_Product
	 */
	private function create_product( $price ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $price );
		$product->save();
		$this->products[] = $product;

		return $product;
	}

	/**
	 * An address in the order's field shape.
	 *
	 * @param string $postcode ZIP.
	 * @param string $city     City.
	 * @param string $street   Street.
	 * @param string $state    State.
	 * @return array
	 */
	private static function address( $postcode = '80202', $city = 'Denver', $street = '1 Main St', $state = 'CO' ) {
		return array(
			'country'   => 'US',
			'state'     => $state,
			'postcode'  => $postcode,
			'city'      => $city,
			'address_1' => $street,
		);
	}

	/**
	 * Forget which orders and items this PHP request created.
	 *
	 * Fixtures are built in the same request as the edit under test; on a store they
	 * were placed in an earlier one.
	 */
	private function forget_created_in_request() {
		foreach ( array( 'orders_created_in_request', 'order_items_created_in_request' ) as $name ) {
			$property = new ReflectionProperty( $this->integration, $name );
			$property->setAccessible( true );
			$property->setValue(
				$this->integration,
				array(
					'objects' => array(),
					'ids'     => array(),
				)
			);
		}
	}

	/**
	 * Build the placed fixture order described in the class docblock.
	 *
	 * @param array $overrides Optional: 'a_tax' (hand-typed tax on A), 'shipping_method'.
	 * @return array{order: WC_Order, a: int, b: int}
	 */
	private function create_placed_order( array $overrides = array() ) {
		$a_tax = $overrides['a_tax'] ?? '0.6';

		$this->rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '6.0000',
				'tax_rate_name'     => 'CO Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
		$rate_id       = $this->rate_id;

		$order = wc_create_order();
		$order->set_address( self::address(), 'billing' );
		$order->set_address( self::address(), 'shipping' );

		$a_id = $order->add_product( $this->create_product( '10' ), 1 );
		$b_id = $order->add_product( $this->create_product( '20' ), 1 );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_method_id( $overrides['shipping_method'] ?? 'flat_rate' );
		$shipping->set_total( '10' );
		$shipping->set_taxes( array( 'total' => array( $rate_id => '0.6' ) ) );
		$order->add_item( $shipping );

		$order->get_item( $a_id, false )->set_taxes(
			array(
				'total'    => array( $rate_id => $a_tax ),
				'subtotal' => array( $rate_id => $a_tax ),
			)
		);
		$order->get_item( $b_id, false )->set_taxes(
			array(
				'total'    => array( $rate_id => '1.2' ),
				'subtotal' => array( $rate_id => '1.2' ),
			)
		);

		$cart_tax = round( (float) $a_tax + 1.2, 2 );

		$tax_line = new WC_Order_Item_Tax();
		$tax_line->set_rate_id( $rate_id );
		$tax_line->set_rate_code( 'US-CO-CO TAX-1' );
		$tax_line->set_label( 'CO Tax' );
		$tax_line->set_rate_percent( 6.0 );
		$tax_line->set_compound( false );
		$tax_line->set_tax_total( $cart_tax );
		$tax_line->set_shipping_tax_total( '0.6' );
		$order->add_item( $tax_line );

		$order->set_shipping_total( '10' );
		$order->set_cart_tax( $cart_tax );
		$order->set_shipping_tax( '0.6' );
		$order->set_total( 30 + 10 + $cart_tax + 0.6 );
		$order->set_status( 'processing' );
		$order->save();
		$this->forget_created_in_request();

		return array(
			'order' => $order,
			'a'     => $a_id,
			'b'     => $b_id,
		);
	}

	/**
	 * Send a request to the orders REST API, as the POS app and integrations do.
	 *
	 * @param string $method   HTTP method.
	 * @param string $route    Route.
	 * @param array  $body     Request body.
	 * @param int    $expected Expected status.
	 * @return WC_Order The order reloaded from the database.
	 */
	private function rest( $method, $route, array $body, $expected ) {
		$request = new WP_REST_Request( $method, $route );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( $expected, $response->get_status(), 'REST request failed: ' . wp_json_encode( $response->get_data() ) );

		// Every request starts clean; a new order's tax must come from TaxJar, not a warm cache.
		return wc_get_order( $response->get_data()['id'] );
	}

	/**
	 * Update an order through the REST API.
	 *
	 * @param int   $order_id Order id.
	 * @param array $body     Request body.
	 * @return WC_Order
	 */
	private function rest_update( $order_id, array $body ) {
		return $this->rest( 'PUT', '/wc/v3/orders/' . $order_id, $body, 200 );
	}

	/**
	 * Create an order through the REST API with A ($10), B ($20) and $10 shipping.
	 *
	 * @param array $overrides Body keys to replace.
	 * @return WC_Order
	 */
	private function rest_create( array $overrides = array() ) {
		$body = array_merge(
			array(
				'billing'        => self::address( '80301', 'Boulder' ),
				'shipping'       => self::address( '80301', 'Boulder' ),
				'line_items'     => array(
					array(
						'product_id' => $this->create_product( '10' )->get_id(),
						'quantity'   => 1,
					),
					array(
						'product_id' => $this->create_product( '20' )->get_id(),
						'quantity'   => 1,
					),
				),
				'shipping_lines' => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Flat rate',
						'total'        => '10.00',
					),
				),
			),
			$overrides
		);

		return $this->rest( 'POST', '/wc/v3/orders', $body, 201 );
	}

	/**
	 * Assert the order-level tax figures and total, and that the tax lines add up.
	 *
	 * @param WC_Order $order        Order.
	 * @param float    $cart_tax     Expected cart tax.
	 * @param float    $shipping_tax Expected shipping tax.
	 * @param float    $total        Expected order total.
	 */
	private function assert_order_tax( WC_Order $order, $cart_tax, $shipping_tax, $total ) {
		$this->assertEqualsWithDelta( $cart_tax, (float) $order->get_cart_tax(), 0.001, 'cart tax' );
		$this->assertEqualsWithDelta( $shipping_tax, (float) $order->get_shipping_tax(), 0.001, 'shipping tax' );
		$this->assertEqualsWithDelta( $total, (float) $order->get_total(), 0.001, 'order total' );

		$line_cart     = 0.0;
		$line_shipping = 0.0;
		foreach ( $order->get_taxes() as $line ) {
			$line_cart     += (float) $line->get_tax_total();
			$line_shipping += (float) $line->get_shipping_tax_total();
		}
		$this->assertEqualsWithDelta( $cart_tax, $line_cart, 0.011, 'tax lines add up to the cart tax' );
		$this->assertEqualsWithDelta( $shipping_tax, $line_shipping, 0.011, 'tax lines add up to the shipping tax' );
	}

	/**
	 * Total tax on one order item.
	 *
	 * @param WC_Order $order   Order.
	 * @param int      $item_id Item id.
	 * @return float
	 */
	private function item_tax( WC_Order $order, $item_id ) {
		$taxes = $order->get_item( $item_id )->get_taxes();

		return (float) array_sum( $taxes['total'] );
	}

	/**
	 * Notes this plugin wrote about the order's tax, newest first.
	 *
	 * @param WC_Order $order Order.
	 * @return string[]
	 */
	private function tax_notes( WC_Order $order ) {
		return array_values(
			array_filter(
				wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' ),
				function ( $note ) {
					return false !== stripos( $note, 'tax' ) && false === stripos( $note, 'Adjusted stock' );
				}
			)
		);
	}

	// -------------------------------------------------------------------------
	// Address changes ask TaxJar.
	// -------------------------------------------------------------------------

	/**
	 * @testdox Moving the shipping address to another town taxes the order at that town's rate.
	 */
	public function test_address_change_taxes_order_at_new_rate() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		// 8%: A 0.80, B 1.60, shipping 0.80.
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertEqualsWithDelta( 0.80, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.60, $this->item_tax( $order, $fixture['b'] ), 0.001 );

		$this->assertCount( 1, $this->requests, 'one TaxJar request' );
		$this->assertSame( '80301', $this->requests[0]['to_zip'] );
		$this->assertEqualsWithDelta( 10.0, (float) $this->requests[0]['shipping'], 0.001, 'shipping is sent' );

		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( '80301', $notes[0], 'the note names the address' );
		$this->assertStringContainsString( '2.40', $notes[0], 'old tax' );
		$this->assertStringContainsString( '3.20', $notes[0], 'new tax' );
	}

	/**
	 * @testdox A street-only change asks TaxJar with the new street; two streets in one ZIP can differ.
	 */
	public function test_street_only_change_asks_taxjar() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => array( 'address_1' => '99 Other Rd' ) ) );

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '99 OTHER RD', strtoupper( $this->requests[0]['to_street'] ) );
		// Same ZIP, same fake rate: the tax stays, and the note still records the lookup.
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox Moving the address out of the state the store collects in removes the tax, without a request.
	 */
	public function test_out_of_state_address_removes_tax() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '90210', 'Beverly Hills', '1 Rodeo Dr', 'CA' ) ) );

		$this->assert_order_tax( $order, 0.0, 0.0, 40.00 );
		$this->assertCount( 0, $order->get_taxes(), 'no tax lines left' );
		$this->assertCount( 0, $this->requests, 'no nexus, no request' );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox An address change and a quantity change in one request tax the new amounts at the new rate.
	 */
	public function test_address_and_amount_change_together() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping'   => self::address( '80301', 'Boulder' ),
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 2,
						'subtotal' => '20.00',
						'total'    => '20.00',
					),
				),
			)
		);

		// 8%: A $20 1.60, B 1.60, shipping 0.80.
		$this->assert_order_tax( $order, 3.20, 0.80, 54.00 );
		$this->assertCount( 1, $this->requests );
	}

	/**
	 * @testdox A hand-typed tax is replaced when the address changes, and the note records it.
	 */
	public function test_hand_typed_tax_replaced_on_address_change() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '1.00' ) );

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assertEqualsWithDelta( 0.80, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( '2.80', $notes[0], 'old tax includes the typed figure' );
	}

	/**
	 * @testdox On an address change, a taxable fee is taxed at its class's new rate and a non-taxable fee stays untaxed.
	 */
	public function test_fees_on_address_change() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping'  => self::address( '80301', 'Boulder' ),
				'fee_lines' => array(
					array(
						'name'       => 'Service',
						'total'      => '10.00',
						'tax_status' => 'taxable',
						'tax_class'  => '',
					),
					array(
						'name'       => 'Deposit',
						'total'      => '5.00',
						'tax_status' => 'none',
					),
				),
			)
		);

		$fees = array();
		foreach ( $order->get_fees() as $fee ) {
			$fees[ $fee->get_name() ] = $fee;
		}
		// 8% of the $10 fee; nothing on the deposit.
		$this->assertEqualsWithDelta( 0.80, $this->item_tax( $order, $fees['Service']->get_id() ), 0.001 );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fees['Deposit']->get_id() ), 0.001 );
		// A 0.80 + B 1.60 + fee 0.80; shipping 0.80; 30 + 10 + 15 + 3.20 + 0.80.
		$this->assert_order_tax( $order, 3.20, 0.80, 59.00 );
	}

	/**
	 * @testdox A programmatic address change saved before the recalculation still asks TaxJar.
	 */
	public function test_programmatic_address_change_saved_first() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->set_address( self::address( '80301', 'Boulder' ), 'shipping' );
		$order->save();
		$order->calculate_totals();

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertCount( 1, $this->requests );
	}

	/**
	 * @testdox Recalculating twice after one address change asks and notes once.
	 */
	public function test_second_recalculation_does_not_ask_again() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->set_address( self::address( '80301', 'Boulder' ), 'shipping' );
		$order->calculate_totals();
		$order->calculate_totals();

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertCount( 1, $this->requests );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox The lookup works without a customer session, as in cron or WP-CLI.
	 */
	public function test_lookup_without_customer_session() {
		$fixture       = $this->create_placed_order();
		$customer      = WC()->customer;
		WC()->customer = null;

		try {
			$order = wc_get_order( $fixture['order']->get_id() );
			$order->set_address( self::address( '80301', 'Boulder' ), 'shipping' );
			$order->calculate_totals();
		} finally {
			WC()->customer = $customer;
		}

		$this->assert_order_tax( wc_get_order( $order->get_id() ), 2.40, 0.80, 43.20 );
	}

	// -------------------------------------------------------------------------
	// Lookup failures keep the tax.
	// -------------------------------------------------------------------------

	/**
	 * @testdox If TaxJar does not answer, an address change keeps the order's tax and notes it.
	 */
	public function test_failed_lookup_keeps_tax_and_notes_it() {
		$fixture           = $this->create_placed_order();
		$this->taxjar_down = true;

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 1, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be updated', $notes[0] );
	}

	/**
	 * @testdox If TaxJar does not answer, an address and quantity change re-apply the recorded rate.
	 */
	public function test_failed_lookup_with_amount_change_reapplies_recorded_rate() {
		$fixture           = $this->create_placed_order();
		$this->taxjar_down = true;

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping'   => self::address( '80301', 'Boulder' ),
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 2,
						'subtotal' => '20.00',
						'total'    => '20.00',
					),
				),
			)
		);

		// 6% recorded: A $20 1.20, B 1.20, shipping 0.60.
		$this->assert_order_tax( $order, 2.40, 0.60, 53.00 );
		$this->assertCount( 2, $this->tax_notes( $order ), 'the re-apply note and the failure note' );
	}

	// -------------------------------------------------------------------------
	// Everything else makes no request.
	// -------------------------------------------------------------------------

	/**
	 * @testdox A save that changes nothing about the taxed address makes no request.
	 */
	public function test_no_request_without_address_change() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'billing'  => array( 'phone' => '555-0100' ),
				'shipping' => self::address(),
			)
		);

		$this->assertCount( 0, $this->requests, 're-sending the same address is not a change' );
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox A quantity change alone re-uses the recorded rate and makes no request.
	 */
	public function test_amount_change_alone_makes_no_request() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 2,
						'subtotal' => '20.00',
						'total'    => '20.00',
					),
				),
			)
		);

		$this->assertCount( 0, $this->requests );
		$this->assert_order_tax( $order, 2.40, 0.60, 53.00 );
	}

	/**
	 * @testdox With tax based on billing, a shipping address change makes no request.
	 */
	public function test_billing_based_ignores_shipping_change() {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assertCount( 0, $this->requests );
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
	}

	/**
	 * @testdox With no shipping country, the billing address is the taxed one, and changing it asks TaxJar.
	 */
	public function test_shipping_based_falls_back_to_billing() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );
		$order->set_address( array_fill_keys( array( 'country', 'state', 'postcode', 'city', 'address_1' ), '' ), 'shipping' );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->rest_update( $order->get_id(), array( 'billing' => self::address( '80301', 'Boulder' ) ) );

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80301', $this->requests[0]['to_zip'] );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
	}

	/**
	 * @testdox With tax based on billing, a billing address change asks TaxJar.
	 */
	public function test_billing_based_asks_on_billing_change() {
		update_option( 'woocommerce_tax_based_on', 'billing' );
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'billing' => self::address( '80301', 'Boulder' ) ) );

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80301', $this->requests[0]['to_zip'] );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
	}

	/**
	 * @testdox A local pickup order is taxed at the store, so a customer address change makes no request.
	 */
	public function test_local_pickup_ignores_address_change() {
		$fixture = $this->create_placed_order( array( 'shipping_method' => 'local_pickup' ) );

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assertCount( 0, $this->requests );
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
	}

	/**
	 * @testdox A VAT-exempt order makes no request.
	 */
	public function test_vat_exempt_order_makes_no_request() {
		$fixture = $this->create_placed_order();
		add_filter( 'woocommerce_order_is_vat_exempt', '__return_true' );

		$this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assertCount( 0, $this->requests );
	}

	// -------------------------------------------------------------------------
	// New orders ask TaxJar (WOOTAX-346).
	// -------------------------------------------------------------------------

	/**
	 * @testdox An order created through the REST API is taxed by TaxJar, not by leftover rate rows.
	 */
	public function test_rest_created_order_is_taxed_by_taxjar() {
		$order = $this->rest_create();

		// 8%: 0.80 + 1.60, shipping 0.80.
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertCount( 1, $this->requests );
		$this->assertCount( 2, $this->requests[0]['line_items'], 'both items are sent' );
		$this->assertSame( array(), $this->tax_notes( $order ), 'a new order gets no tax note' );
	}

	/**
	 * @testdox Each line is taxed at the rates TaxJar returned for it, even when two lines share a rate row.
	 */
	public function test_each_line_taxed_at_its_own_answer() {
		$this->state_threshold = true;

		$order = $this->rest_create(
			array(
				'billing'  => self::address(),
				'shipping' => self::address(),
			)
		);

		// A $10: 2.9% + 3.1% = 0.60. B $20: state exempt, 3.1% = 0.62. Shipping 6% = 0.60.
		// Both lines' state rate is stored in one row, which ends up holding one value.
		$items = array_values( $order->get_items() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $items[0]->get_id() ), 0.001, 'A' );
		$this->assertEqualsWithDelta( 0.62, $this->item_tax( $order, $items[1]->get_id() ), 0.001, 'B' );
		$this->assert_order_tax( $order, 1.22, 0.60, 41.82 );
	}

	/**
	 * @testdox An order created in code gets its tax from TaxJar without a "tax changed" note.
	 */
	public function test_order_created_in_code_gets_no_note() {
		$order = wc_create_order();
		$order->set_address( self::address( '80301', 'Boulder' ), 'shipping' );
		$order->save();
		$order->add_product( $this->create_product( '10' ), 1 );
		$order->calculate_totals();

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $this->requests );
		$this->assert_order_tax( $order, 0.80, 0.0, 10.80 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox An in-person order with no customer address is taxed at the store address.
	 */
	public function test_created_order_without_address_uses_store_address() {
		$order = $this->rest_create(
			array(
				'billing'        => array( 'email' => 'walk-in@example.com' ),
				'shipping'       => array(),
				'shipping_lines' => array(),
			)
		);

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80202', $this->requests[0]['to_zip'] );
		$this->assertSame( '1 STORE ST', strtoupper( $this->requests[0]['to_street'] ) );
		// 6%: 0.60 + 1.20.
		$this->assert_order_tax( $order, 1.80, 0.0, 31.80 );
	}

	/**
	 * A new REST order with a reduced-rate product and a standard-class taxable fee.
	 *
	 * @return WC_Order
	 */
	private function rest_create_with_unmatched_fee() {
		$product = $this->create_product( '10' );
		$product->set_tax_class( 'reduced-rate' );
		$product->save();

		return $this->rest_create(
			array(
				'billing'        => self::address(),
				'shipping'       => self::address(),
				'line_items'     => array(
					array(
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
				'fee_lines'      => array(
					array(
						'name'       => 'Custom amount',
						'total'      => '10.00',
						'tax_status' => 'taxable',
						'tax_class'  => '',
					),
				),
				'shipping_lines' => array(),
			)
		);
	}

	/**
	 * @testdox A taxable fee with no product in its tax class keeps the tax WooCommerce gave it.
	 */
	public function test_fee_without_matching_product_keeps_woocommerce_tax() {
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '5.0000',
				'tax_rate_name'     => 'CO Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);

		$order = $this->rest_create_with_unmatched_fee();

		$fees = $order->get_fees();
		$fee  = reset( $fees );
		// The product at TaxJar's 6%, the fee at the stored standard 5%, a rate TaxJar never returned.
		$this->assertEqualsWithDelta( 0.50, $this->item_tax( $order, $fee->get_id() ), 0.001, 'fee' );
		$this->assert_order_tax( $order, 1.10, 0.0, 21.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox A taxable fee with no product in its tax class and no stored rate is noted as untaxed.
	 */
	public function test_untaxed_fee_without_matching_product_is_noted() {
		$order = $this->rest_create_with_unmatched_fee();

		$fees = $order->get_fees();
		$fee  = reset( $fees );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fee->get_id() ), 0.001, 'fee' );
		$this->assert_order_tax( $order, 0.60, 0.0, 20.60 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'No tax was added for Custom amount', $notes[0] );
	}

	/**
	 * @testdox If TaxJar does not answer for a new order, WooCommerce's own result stands and a note says so.
	 */
	public function test_failed_lookup_on_new_order_notes_it() {
		$this->taxjar_down = true;

		$order = $this->rest_create();

		// No rate rows exist, so WooCommerce finds no tax: what it did before the lookup.
		$this->assert_order_tax( $order, 0.0, 0.0, 40.00 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be calculated', $notes[0] );
	}

	/**
	 * @testdox Adding items to an untaxed draft order asks TaxJar.
	 */
	public function test_items_added_to_untaxed_order_ask_taxjar() {
		$order = wc_create_order();
		$order->set_address( self::address( '80301', 'Boulder' ), 'shipping' );
		$order->save();
		$this->assertCount( 0, $this->requests );

		// The draft was created in an earlier request; only the new item may trigger the lookup.
		$this->forget_created_in_request();

		$order = wc_get_order( $order->get_id() );
		$order->add_product( $this->create_product( '10' ), 1 );
		$order->calculate_totals();

		$this->assertCount( 1, $this->requests );
		$this->assert_order_tax( wc_get_order( $order->get_id() ), 0.80, 0.0, 10.80 );
	}
}
