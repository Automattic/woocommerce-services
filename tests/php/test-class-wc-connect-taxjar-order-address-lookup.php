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
	 * Whether the fake TaxJar rejects the request as a ZIP that is not in the state.
	 *
	 * @var bool
	 */
	private $zip_not_in_state = false;

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

		$this->requests         = array();
		$this->taxjar_down      = false;
		$this->zip_not_in_state = false;
		$this->state_threshold  = false;

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
		remove_all_actions( 'woocommerce_order_item_shipping_after_calculate_taxes' );
		// Leave the integration's own callback; drop only what a test added.
		remove_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'exempt_product_b' ), 20 );
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

		if ( $this->zip_not_in_state ) {
			return array(
				'response' => array( 'code' => 400 ),
				'body'     => wp_json_encode(
					array(
						'status' => 400,
						'error'  => 'Bad Request',
						'detail' => 'to_zip ' . $body['to_zip'] . ' is not used within to_state ' . $body['to_state'],
					)
				),
			);
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
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'from the store\'s tax rates', $notes[0] );
		$this->assertStringNotContainsString( 'today\'s rates', $notes[0], 'TaxJar was not asked' );
	}

	/**
	 * Add a rate the merchant set up by hand for a state the store has no nexus in.
	 *
	 * @return int Rate id.
	 */
	private function insert_manual_ohio_rate() {
		return WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'OH',
				'tax_rate'          => '7.0000',
				'tax_rate_name'     => 'OH Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * @testdox Moving the address to a state with a manual rate charges that rate, as checkout does.
	 */
	public function test_out_of_state_address_keeps_manual_rate() {
		$fixture = $this->create_placed_order();
		$this->insert_manual_ohio_rate();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '43215', 'Columbus', '1 High St', 'OH' ) ) );

		// 7% of $30 and of $10 shipping.
		$this->assert_order_tax( $order, 2.10, 0.70, 42.80 );
		$this->assertCount( 0, $this->requests, 'no nexus, no request' );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Columbus', $notes[0] );
		$this->assertStringContainsString( 'from the store\'s tax rates', $notes[0] );
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
		$this->assertStringContainsString( 'the tax service did not answer', $notes[0] );
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
		$notes = $this->tax_notes( $order );
		$this->assertCount( 2, $notes, 'the re-apply note and the failure note' );
		$failure_note = implode( "\n", preg_grep( '/could not be updated/', $notes ) );
		$this->assertStringContainsString( 'the tax service did not answer', $failure_note );
		$this->assertStringContainsString( 'recorded when the order was placed were used instead', $failure_note );
		$this->assertStringNotContainsString( 'was kept', $failure_note, 'The tax changed, so the note must not say it was kept.' );
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
	 * Move the store to Boulder, so the store address and the fixture's Denver customer differ in rate.
	 */
	private function move_store_to_boulder() {
		update_option( 'woocommerce_store_city', 'Boulder' );
		update_option( 'woocommerce_store_postcode', '80301' );
	}

	/**
	 * Change the fixture order's shipping method over REST, keeping its amount.
	 *
	 * @param WC_Order $order     Fixture order.
	 * @param string   $method_id New method id.
	 * @return WC_Order
	 */
	private function rest_switch_shipping_method( WC_Order $order, $method_id ) {
		$shipping_ids = array_keys( $order->get_shipping_methods() );

		return $this->rest_update(
			$order->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'           => reset( $shipping_ids ),
						'method_id'    => $method_id,
						'method_title' => $method_id,
						'total'        => '10.00',
					),
				),
			)
		);
	}

	/**
	 * @testdox Switching a delivery order to local pickup taxes it at the store address.
	 */
	public function test_switch_to_local_pickup_asks_at_store_address() {
		$this->move_store_to_boulder();
		$fixture = $this->create_placed_order();

		$order = $this->rest_switch_shipping_method( $fixture['order'], 'local_pickup' );

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80301', $this->requests[0]['to_zip'], 'asked at the store' );
		// 8%: A 0.80, B 1.60, shipping 0.80.
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox Switching a local pickup order to delivery taxes it at the customer's address.
	 */
	public function test_switch_to_delivery_asks_at_customer_address() {
		$this->move_store_to_boulder();
		$fixture = $this->create_placed_order( array( 'shipping_method' => 'local_pickup' ) );

		$order = $this->rest_switch_shipping_method( $fixture['order'], 'flat_rate' );

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80202', $this->requests[0]['to_zip'], 'asked at the customer' );
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox With base tax for local pickup turned off, switching to local pickup is not a change.
	 */
	public function test_switch_to_local_pickup_without_base_tax_makes_no_request() {
		$this->move_store_to_boulder();
		$fixture = $this->create_placed_order();
		add_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );

		try {
			$order = $this->rest_switch_shipping_method( $fixture['order'], 'local_pickup' );
		} finally {
			remove_filter( 'woocommerce_apply_base_tax_for_local_pickup', '__return_false' );
		}

		$this->assertCount( 0, $this->requests );
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
	}

	/**
	 * @testdox A REST order created with local pickup is taxed at the store and gets no note.
	 */
	public function test_rest_created_local_pickup_order_is_taxed_at_store() {
		$this->move_store_to_boulder();

		$order = $this->rest_create(
			array(
				'billing'        => self::address(),
				'shipping'       => self::address(),
				'shipping_lines' => array(
					array(
						'method_id'    => 'local_pickup',
						'method_title' => 'Pickup',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80301', $this->requests[0]['to_zip'] );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'a new order gets no tax note' );
	}

	/**
	 * @testdox A woocommerce_order_get_tax_location filter decides where the order is taxed, as in core.
	 */
	public function test_order_tax_location_filter_is_honoured() {
		$fixture  = $this->create_placed_order();
		$location = static function ( $location ) {
			return array_merge(
				$location,
				array(
					'postcode' => '80301',
					'city'     => 'Boulder',
				)
			);
		};
		add_filter( 'woocommerce_order_get_tax_location', $location );

		try {
			// Pinned to one place: a customer address change does not move it.
			$unchanged = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80203', 'Denver', '2 Other St' ) ) );
			$this->assertCount( 0, $this->requests, 'the filtered location did not move' );
			$this->assert_order_tax( $unchanged, 1.80, 0.60, 42.40 );

			// A new order is looked up at the filtered location, with no street.
			$order = $this->rest_create( array( 'shipping' => self::address() ) );
		} finally {
			remove_filter( 'woocommerce_order_get_tax_location', $location );
		}

		$this->assertCount( 1, $this->requests );
		$this->assertSame( '80301', $this->requests[0]['to_zip'] );
		$this->assertEmpty( $this->requests[0]['to_street'] ?? '' );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
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

	// -------------------------------------------------------------------------
	// Tax another plugin sets while WooCommerce calculates is kept.
	// -------------------------------------------------------------------------

	/**
	 * Product id the exemption snippet zeroes; see exempt_product_b().
	 *
	 * @var int
	 */
	private $exempt_product_id = 0;

	/**
	 * Stand-in for an exemption plugin: zero one product's tax as WooCommerce calculates it.
	 *
	 * @param WC_Order_Item $item The item WooCommerce just taxed.
	 */
	public function exempt_product_b( $item ) {
		if ( is_callable( array( $item, 'get_product_id' ) ) && $this->exempt_product_id === $item->get_product_id() ) {
			$item->set_taxes( false );
		}
	}

	/**
	 * Create a REST order of A ($10) and B ($20), with B exempted by another plugin.
	 *
	 * @return array{order: WC_Order, a: int, b: int}
	 */
	private function rest_create_with_exempt_b() {
		$a                       = $this->create_product( '10' );
		$b                       = $this->create_product( '20' );
		$this->exempt_product_id = $b->get_id();
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'exempt_product_b' ), 20 );

		$order = $this->rest_create(
			array(
				'line_items' => array(
					array(
						'product_id' => $a->get_id(),
						'quantity'   => 1,
					),
					array(
						'product_id' => $b->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$ids = array();
		foreach ( $order->get_items() as $item_id => $item ) {
			$ids[ $item->get_product_id() === $b->get_id() ? 'b' : 'a' ] = $item_id;
		}

		return array_merge( array( 'order' => $order ), $ids );
	}

	/**
	 * @testdox A new REST order keeps the $0 tax another plugin gives an item while WooCommerce calculates.
	 */
	public function test_rest_created_order_keeps_tax_set_by_another_plugin() {
		$fixture = $this->rest_create_with_exempt_b();
		$order   = $fixture['order'];

		$this->assertCount( 1, $this->requests, 'TaxJar is still asked' );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['b'] ), 0.001, 'B stays exempt' );
		// 8%: A 0.80, shipping 0.80.
		$this->assertEqualsWithDelta( 0.80, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 0.80, 0.80, 41.60 );
	}

	/**
	 * @testdox A quantity change keeps the $0 tax another plugin gives the changed item.
	 */
	public function test_quantity_change_keeps_tax_set_by_another_plugin() {
		$fixture        = $this->rest_create_with_exempt_b();
		$this->requests = array();
		$this->forget_created_in_request();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['b'],
						'quantity' => 2,
						'subtotal' => '40.00',
						'total'    => '40.00',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['b'] ), 0.001, 'B stays exempt' );
		$this->assert_order_tax( $order, 0.80, 0.80, 61.60 );
	}

	/**
	 * @testdox The item hook runs again only for items whose tax was set here, not for unchanged ones.
	 */
	public function test_item_tax_hook_runs_again_only_for_retaxed_items() {
		$fixture        = $this->rest_create_with_exempt_b();
		$this->requests = array();
		$this->forget_created_in_request();

		$runs = array();
		$spy  = static function ( $item ) use ( &$runs ) {
			$runs[] = $item->get_id();
		};
		add_action( 'woocommerce_order_item_after_calculate_taxes', $spy, 30 );

		try {
			$this->rest_update(
				$fixture['order']->get_id(),
				array(
					'line_items' => array(
						array(
							'id'       => $fixture['b'],
							'quantity' => 2,
							'subtotal' => '40.00',
							'total'    => '40.00',
						),
					),
				)
			);
		} finally {
			remove_action( 'woocommerce_order_item_after_calculate_taxes', $spy, 30 );
		}

		$counts = array_count_values( $runs );
		$this->assertSame( 1, $counts[ $fixture['a'] ], 'A did not change: only WooCommerce ran the hook' );
		$this->assertSame( 2, $counts[ $fixture['b'] ], 'B was re-taxed: the hook ran again on that tax' );
	}

	/**
	 * @testdox A changed item re-taxed at the order's recorded rate goes through the other plugin's hook too.
	 */
	public function test_reapplied_rate_goes_through_other_plugin() {
		$fixture                 = $this->create_placed_order();
		$this->exempt_product_id = $fixture['order']->get_item( $fixture['b'] )->get_product_id();
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this, 'exempt_product_b' ), 20 );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['b'],
						'quantity' => 2,
						'subtotal' => '40.00',
						'total'    => '40.00',
					),
				),
			)
		);

		$this->assertCount( 0, $this->requests );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['b'] ), 0.001, 'the plugin zeroed the re-applied tax' );
		// A keeps 0.60 at the recorded 6%; shipping 0.60.
		$this->assert_order_tax( $order, 0.60, 0.60, 61.20 );
	}

	/**
	 * @testdox A non-taxable item on a new REST order goes through the item hook again too.
	 */
	public function test_non_taxable_item_runs_item_hook_again() {
		$product = $this->create_product( '5' );
		$product->set_tax_status( 'none' );
		$product->save();

		$runs = array();
		$spy  = static function ( $item ) use ( &$runs, $product ) {
			if ( $product->get_id() === $item->get_product_id() ) {
				$runs[] = $item->get_id();
			}
		};
		add_action( 'woocommerce_order_item_after_calculate_taxes', $spy, 30 );

		try {
			$this->rest_create(
				array(
					'line_items' => array(
						array(
							'product_id' => $product->get_id(),
							'quantity'   => 1,
						),
					),
				)
			);
		} finally {
			remove_action( 'woocommerce_order_item_after_calculate_taxes', $spy, 30 );
		}

		$this->assertCount( 2, $runs, 'once from WooCommerce, once on the tax set here' );
	}

	/**
	 * @testdox Shipping tax another plugin sets while WooCommerce calculates is kept on a new REST order.
	 */
	public function test_rest_created_order_keeps_shipping_tax_set_by_another_plugin() {
		add_action(
			'woocommerce_order_item_shipping_after_calculate_taxes',
			static function ( $item ) {
				$item->set_taxes( false );
			}
		);

		$order = $this->rest_create();

		// 8%: A 0.80, B 1.60; shipping left untaxed by the other plugin.
		$this->assert_order_tax( $order, 2.40, 0.0, 42.40 );
	}

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
	 * @testdox An order created through the REST API for a state with a manual rate is charged that rate.
	 */
	public function test_rest_created_order_out_of_state_keeps_manual_rate() {
		$this->insert_manual_ohio_rate();

		$order = $this->rest_create(
			array(
				'billing'  => self::address( '43215', 'Columbus', '1 High St', 'OH' ),
				'shipping' => self::address( '43215', 'Columbus', '1 High St', 'OH' ),
			)
		);

		$this->assert_order_tax( $order, 2.10, 0.70, 42.80 );
		$this->assertCount( 0, $this->requests, 'no nexus, no request' );
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
		$this->assertStringContainsString( 'the tax service did not answer', $notes[0] );
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

	/**
	 * Run a callback without a WooCommerce session, as REST, cron and WP-CLI requests do.
	 *
	 * @param callable $callback Callback.
	 * @return mixed The callback's return value.
	 */
	private function without_wc_session( callable $callback ) {
		$session      = WC()->session;
		WC()->session = null;

		try {
			return $callback();
		} finally {
			WC()->session = $session;
		}
	}

	/**
	 * Replace the integration's logger with a mock that expects the error to be logged.
	 *
	 * @param string $contains Text the logged error must contain.
	 */
	private function expect_logged_error( $contains ) {
		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$logger->expects( $this->atLeastOnce() )->method( 'error' )->with( $this->stringContains( $contains ) );
		$this->integration->logger = $logger;
	}

	/**
	 * @testdox A REST edit whose ZIP is not in the state keeps the tax, notes it and logs the error, without a session.
	 */
	public function test_zip_not_in_state_without_session_keeps_tax() {
		$fixture                = $this->create_placed_order();
		$this->zip_not_in_state = true;
		$this->expect_logged_error( 'is not used within to_state' );

		$order = $this->without_wc_session(
			function () use ( $fixture ) {
				return $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '43215', 'Boulder' ) ) );
			}
		);

		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 1, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be updated', $notes[0] );
		$this->assertStringContainsString( 'did not accept the address', $notes[0] );
	}

	/**
	 * @testdox A REST order created with a malformed ZIP is saved with a note and the error is logged, without a session.
	 */
	public function test_malformed_zip_on_create_without_session_does_not_fatal() {
		$this->expect_logged_error( 'zip code has incorrect format' );

		$order = $this->without_wc_session(
			function () {
				return $this->rest_create(
					array(
						'billing'  => self::address( '8030A', 'Boulder' ),
						'shipping' => self::address( '8030A', 'Boulder' ),
					)
				);
			}
		);

		$this->assertInstanceOf( WC_Order::class, $order );
		$this->assertCount( 0, $this->requests, 'A malformed ZIP is rejected before any request is sent.' );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'has a state or ZIP code that is not valid', $notes[0] );
	}

	/**
	 * @testdox The store notifier reports no notice and adds none when there is no session.
	 */
	public function test_notifier_without_session_does_not_fatal() {
		$notifier = new Automattic\WCServices\StoreNotices\StoreNoticesNotifier( false );

		$has_notice = $this->without_wc_session(
			function () use ( $notifier ) {
				$notifier->error( 'ZIP/Postal code does not match the selected state.', array(), 'taxjar' );

				return $notifier->has_notice( 'ZIP/Postal code does not match the selected state.', 'error', array(), 'taxjar' );
			}
		);

		$this->assertFalse( $has_notice );
	}

	// -------------------------------------------------------------------------
	// The note names the address when no request could be sent.
	// -------------------------------------------------------------------------

	/**
	 * New orders whose address cannot be sent to TaxJar.
	 *
	 * @return array[]
	 */
	public function incomplete_address_provider() {
		return array(
			'shipping country and state, no ZIP' => array(
				array(
					'billing'  => array(),
					'shipping' => array(
						'country' => 'US',
						'state'   => 'CO',
					),
				),
			),
			'shipping country only'              => array(
				array(
					'billing'  => array(),
					'shipping' => array( 'country' => 'US' ),
				),
			),
			'billing country and state only'     => array(
				array(
					'billing'  => array(
						'country' => 'US',
						'state'   => 'CO',
					),
					'shipping' => array(),
				),
			),
		);
	}

	/**
	 * @testdox A new order with an incomplete address is not sent to TaxJar, and the note says the address is incomplete.
	 * @dataProvider incomplete_address_provider
	 *
	 * @param array $addresses Billing and shipping for the new order.
	 */
	public function test_incomplete_address_on_create_is_noted_as_address( array $addresses ) {
		$order = $this->rest_create( $addresses );

		$this->assertCount( 0, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'because its address is incomplete', $notes[0] );
		$this->assertStringNotContainsString( 'tax service', $notes[0] );
	}

	/**
	 * @testdox Moving an order to a malformed ZIP keeps its tax, sends nothing, and the note blames the ZIP code.
	 */
	public function test_malformed_zip_on_update_is_noted_as_address() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '8030A', 'Boulder' ) ) );

		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 0, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be updated', $notes[0] );
		$this->assertStringContainsString( 'has a state or ZIP code that is not valid', $notes[0] );
		$this->assertStringNotContainsString( 'tax service', $notes[0] );
	}

	/**
	 * @testdox A ZIP code that is sent to TaxJar is not blamed when TaxJar does not answer.
	 */
	public function test_sent_zip_is_not_blamed_for_failed_request() {
		$fixture           = $this->create_placed_order();
		$this->taxjar_down = true;

		// Four digits: not a valid US ZIP, but only 5- and 10-character ones are checked before sending.
		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '8030', 'Boulder' ) ) );

		$this->assertCount( 1, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'the tax service did not answer', $notes[0] );
	}

	// -------------------------------------------------------------------------
	// The note names the store address when that is what stopped the request.
	// -------------------------------------------------------------------------

	/**
	 * Store addresses that cannot be sent to TaxJar.
	 *
	 * @return array[]
	 */
	public function bad_store_address_provider() {
		return array(
			'US store with a malformed ZIP' => array( 'US:CO', '4985A' ),
			'store without a country'       => array( '', '80202' ),
		);
	}

	/**
	 * @testdox Moving an order when the store address cannot be sent keeps its tax, and the note points at the store address.
	 * @dataProvider bad_store_address_provider
	 *
	 * @param string $country  Store country and state option.
	 * @param string $postcode Store postcode.
	 */
	public function test_bad_store_address_on_update_is_noted_as_store_address( $country, $postcode ) {
		$fixture = $this->create_placed_order();
		update_option( 'woocommerce_default_country', $country );
		update_option( 'woocommerce_store_postcode', $postcode );

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 0, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'the store address has no country or has a ZIP code that is not valid', $notes[0] );
		$this->assertStringContainsString( 'WooCommerce > Settings > General', $notes[0] );
		$this->assertStringContainsString( 'was kept', $notes[0] );
		$this->assertStringNotContainsString( 'tax service', $notes[0] );
		$this->assertStringNotContainsString( 'order\'s address', $notes[0] );
	}

	/**
	 * @testdox A new order when the store address cannot be sent gets a note that points at the store address.
	 */
	public function test_bad_store_address_on_create_is_noted_as_store_address() {
		update_option( 'woocommerce_store_postcode', '4985A' );

		$order = $this->rest_create();

		$this->assertCount( 0, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be calculated', $notes[0] );
		$this->assertStringContainsString( 'the store address has no country or has a ZIP code that is not valid', $notes[0] );
		$this->assertStringNotContainsString( 'tax service', $notes[0] );
	}

	/**
	 * @testdox When the store address cannot be sent and the amounts changed too, the note says the recorded rates were used.
	 */
	public function test_bad_store_address_with_amount_change_says_recorded_rates_were_used() {
		$fixture = $this->create_placed_order();
		update_option( 'woocommerce_store_postcode', '4985A' );

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

		$this->assert_order_tax( $order, 2.40, 0.60, 53.00 );
		$failure_note = implode( "\n", preg_grep( '/could not be updated/', $this->tax_notes( $order ) ) );
		$this->assertStringContainsString( 'the store address has no country', $failure_note );
		$this->assertStringContainsString( 'recorded when the order was placed were used instead', $failure_note );
		$this->assertStringNotContainsString( 'was kept', $failure_note );
	}

	/**
	 * @testdox A store outside the US is not blamed for its ZIP code when TaxJar does not answer.
	 */
	public function test_non_us_store_is_not_blamed_for_failed_request() {
		$fixture = $this->create_placed_order();
		update_option( 'woocommerce_default_country', 'CA:ON' );
		update_option( 'woocommerce_store_postcode', 'M5V 3L9' );
		$this->taxjar_down = true;

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder' ) ) );

		$this->assertCount( 1, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'the tax service did not answer', $notes[0] );
	}

	// -------------------------------------------------------------------------
	// The REST API takes a state name as well as a code.
	// -------------------------------------------------------------------------

	/**
	 * State names the REST API accepts for Colorado.
	 *
	 * @return array[]
	 */
	public function state_name_provider() {
		return array(
			'as WooCommerce lists it' => array( 'Colorado' ),
			'in lower case'           => array( 'colorado' ),
		);
	}

	/**
	 * US states TaxJar cannot be asked about.
	 *
	 * @return array[]
	 */
	public function unusable_state_provider() {
		return array(
			'empty'   => array( '' ),
			'unknown' => array( 'Narnia' ),
		);
	}

	/**
	 * @testdox Moving an order to an address with a state name asks TaxJar with the state's code and keeps the name on the order.
	 * @dataProvider state_name_provider
	 *
	 * @param string $state State as the client sent it.
	 */
	public function test_state_name_on_update_is_looked_up_as_its_code( $state ) {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder', '1 Main St', $state ) ) );

		$this->assertCount( 1, $this->requests );
		$this->assertSame( 'CO', $this->requests[0]['to_state'] );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertSame( $state, $order->get_shipping_state(), 'the saved address is left as sent' );

		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'CO, 80301', $notes[0], 'the note names the state that was asked about' );
	}

	/**
	 * @testdox A new order with a state name is taxed by TaxJar at the state's code.
	 * @dataProvider state_name_provider
	 *
	 * @param string $state State as the client sent it.
	 */
	public function test_state_name_on_create_is_looked_up_as_its_code( $state ) {
		$order = $this->rest_create(
			array(
				'billing'  => self::address( '80301', 'Boulder', '1 Main St', $state ),
				'shipping' => self::address( '80301', 'Boulder', '1 Main St', $state ),
			)
		);

		$this->assertCount( 1, $this->requests );
		$this->assertSame( 'CO', $this->requests[0]['to_state'] );
		$this->assert_order_tax( $order, 2.40, 0.80, 43.20 );
		$this->assertSame( $state, $order->get_shipping_state(), 'the saved address is left as sent' );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox Moving an order to a US address with an empty or unknown state keeps its tax, sends nothing, and the note blames the address.
	 * @dataProvider unusable_state_provider
	 *
	 * @param string $state State as the client sent it.
	 */
	public function test_unusable_state_on_update_keeps_tax( $state ) {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'shipping' => self::address( '80301', 'Boulder', '1 Main St', $state ) ) );

		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
		$this->assertCount( 0, $this->requests );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be updated', $notes[0] );
		$this->assertStringContainsString( 'state or ZIP code that is not valid', $notes[0] );
		$this->assertStringContainsString( 'was kept', $notes[0] );
		$this->assertStringNotContainsString( 'tax service', $notes[0] );
	}

	/**
	 * @testdox A new US order with an empty or unknown state is not sent to TaxJar, and the note blames the address.
	 * @dataProvider unusable_state_provider
	 *
	 * @param string $state State as the client sent it.
	 */
	public function test_unusable_state_on_create_is_noted_as_address( $state ) {
		$order = $this->rest_create(
			array(
				'billing'  => self::address( '80301', 'Boulder', '1 Main St', $state ),
				'shipping' => self::address( '80301', 'Boulder', '1 Main St', $state ),
			)
		);

		$this->assertCount( 0, $this->requests );
		$this->assert_order_tax( $order, 0.0, 0.0, 40.00 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'could not be calculated', $notes[0] );
		$this->assertStringContainsString( 'state or ZIP code that is not valid', $notes[0] );
		$this->assertStringNotContainsString( 'tax service', $notes[0] );
	}
}
