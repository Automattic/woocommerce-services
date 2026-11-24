<?php
/**
 * Tests for how an existing order's tax follows edits made outside the cart.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Order_Recalculation
 *
 * Every test starts from the same placed order, taxed at 6% when it was sold:
 *
 *   Product A   $10.00  tax 0.60
 *   Product B   $20.00  tax 1.20
 *   Shipping     $5.00  tax 0.30
 *   ------------------------------
 *   cart tax 1.80, shipping tax 0.30, total 37.10
 *
 * The rate row the order points at has since been overwritten to 0% in the rate
 * table (the WOOTAX-342 damage), so any expected figure that comes out at 6% proves
 * the rate came from the order, not from the table.
 *
 * Expected values follow one rule: the rate is fixed when the order is placed, the
 * amount follows the order. Items the edit did not touch keep the tax they have.
 */
class WP_Test_WC_Connect_TaxJar_Order_Recalculation extends WC_Unit_Test_Case {

	/**
	 * Integration under test.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $integration;

	/**
	 * Rate id the fixture order was taxed with.
	 *
	 * @var int
	 */
	private $rate_id;

	/**
	 * How many requests the tax service got in this test.
	 *
	 * @var int
	 */
	private $tax_service_requests = 0;

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
	}

	/**
	 * Enable automated taxes and register the integration's hooks the way a store does.
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CO' );
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'yes' );

		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock();
		// Nothing here is about TaxJar's answer: a lookup that does run finds it unreachable.
		$this->tax_service_requests = 0;
		$api_client->method( 'proxy_request' )->willReturnCallback(
			function () {
				++$this->tax_service_requests;
				return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
			}
		);
		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock();

		// Real notifier, as the plugin passes one: a lookup clears its notices first.
		$notifier = new Automattic\WCServices\StoreNotices\StoreNoticesNotifier( false );

		$this->integration = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks, $notifier );
		$this->integration->init();

		// The row the order was taxed with, since overwritten (0%, new name) in the table.
		$this->rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '0.0000',
				'tax_rate_name'     => 'Overwritten',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Remove the fixture rows and products.
	 */
	public function tear_down() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		foreach ( $this->products as $product ) {
			$product->delete( true );
		}
		$this->products = array();

		remove_all_filters( 'woocommerce_order_is_vat_exempt' );
		remove_all_filters( 'woocommerce_get_order_item_classname' );
		delete_option( WC_Connect_TaxJar_Integration::OPTION_NAME );
		delete_option( 'woocommerce_calc_taxes' );
		wc_get_container()->reset_replacement( Automattic\WooCommerce\Internal\Tax\TaxRateDataStore::class );

		parent::tear_down();
	}

	// -------------------------------------------------------------------------
	// Fixture helpers
	// -------------------------------------------------------------------------

	/**
	 * Create a simple product.
	 *
	 * @param string $price     Regular price.
	 * @param string $tax_class Tax class slug.
	 * @return WC_Product
	 */
	private function create_product( $price, $tax_class = '' ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( $price );
		$product->set_tax_class( $tax_class );
		$product->save();
		$this->products[] = $product;

		return $product;
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
	 * Forget what this PHP request created and the rate rows WooCommerce read, as the
	 * next request would.
	 */
	private function start_new_request() {
		$this->forget_created_in_request();

		// A fresh store has an empty rate cache. WooCommerce 11.0 has no cache to forget.
		if ( class_exists( Automattic\WooCommerce\Internal\Tax\TaxRateDataStore::class ) ) {
			wc_get_container()->replace( Automattic\WooCommerce\Internal\Tax\TaxRateDataStore::class, new Automattic\WooCommerce\Internal\Tax\TaxRateDataStore() );
		}
	}

	/**
	 * Build the placed fixture order described in the class docblock.
	 *
	 * @param array $overrides Optional: 'a_tax' (hand-typed tax on A), 'rate_percent', 'status'
	 *                         (default on-hold with no payment date, so its tax is not locked).
	 * @return array{order: WC_Order, a: int, b: int, shipping: int}
	 */
	private function create_placed_order( array $overrides = array() ) {
		$a_tax        = isset( $overrides['a_tax'] ) ? $overrides['a_tax'] : '0.6';
		$rate_percent = array_key_exists( 'rate_percent', $overrides ) ? $overrides['rate_percent'] : 6.0;
		$rate_id      = $this->rate_id;

		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CO' );
		$order->set_shipping_postcode( '80202' );
		$order->set_shipping_city( 'Denver' );

		$a_id = $order->add_product( $this->create_product( '10' ), 1 );
		$b_id = $order->add_product( $this->create_product( '20' ), 1 );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_total( '5' );
		$shipping->set_taxes( array( 'total' => array( $rate_id => '0.3' ) ) );
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
		$tax_line->set_rate_percent( $rate_percent );
		$tax_line->set_compound( false );
		$tax_line->set_tax_total( $cart_tax );
		$tax_line->set_shipping_tax_total( '0.3' );
		$order->add_item( $tax_line );

		$order->set_shipping_total( '5' );
		$order->set_cart_tax( $cart_tax );
		$order->set_shipping_tax( '0.3' );
		$order->set_total( 30 + 5 + $cart_tax + 0.3 );
		$order->set_status( $overrides['status'] ?? 'on-hold' );
		$order->save();
		$this->forget_created_in_request();

		$shipping_ids = array_keys( $order->get_shipping_methods() );

		return array(
			'order'    => $order,
			'a'        => $a_id,
			'b'        => $b_id,
			'shipping' => reset( $shipping_ids ),
		);
	}

	/**
	 * Update an order through the REST API, as the POS app and integrations do.
	 *
	 * @param int   $order_id Order id.
	 * @param array $body     Request body.
	 * @return WC_Order The order reloaded from the database.
	 */
	private function rest_update( $order_id, array $body ) {
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order_id );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'REST update failed: ' . wp_json_encode( $response->get_data() ) );

		return wc_get_order( $order_id );
	}

	/**
	 * Per-item tax for the fixture's rate.
	 *
	 * @param WC_Order $order   Order.
	 * @param int      $item_id Item id.
	 * @return float
	 */
	private function item_tax( WC_Order $order, $item_id ) {
		$taxes = $order->get_item( $item_id )->get_taxes();

		return isset( $taxes['total'][ $this->rate_id ] ) ? (float) $taxes['total'][ $this->rate_id ] : 0.0;
	}

	/**
	 * Assert the order-level tax figures and total.
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

		$taxes = $order->get_taxes();
		$this->assertCount( 1, $taxes, 'one tax line' );
		$line = reset( $taxes );
		$this->assertSame( $this->rate_id, (int) $line->get_rate_id() );
		$this->assertSame( 'CO Tax', $line->get_label(), 'tax line label comes from the order, not the table' );
		$this->assertEqualsWithDelta( 6.0, (float) $line->get_rate_percent(), 0.0001, 'tax line keeps the rate it was sold at' );
		$this->assertEqualsWithDelta( $cart_tax, (float) $line->get_tax_total(), 0.001, 'tax line cart tax' );
		$this->assertEqualsWithDelta( $shipping_tax, (float) $line->get_shipping_tax_total(), 0.001, 'tax line shipping tax' );
	}

	/**
	 * Order notes (content only), newest first.
	 *
	 * @param WC_Order $order Order.
	 * @return string[]
	 */
	private function note_texts( WC_Order $order ) {
		return wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' );
	}

	/**
	 * Notes this plugin wrote about a tax change.
	 *
	 * @param WC_Order $order Order.
	 * @return string[]
	 */
	private function tax_notes( WC_Order $order ) {
		return array_values(
			array_filter(
				$this->note_texts( $order ),
				function ( $note ) {
					return false !== stripos( $note, 'tax' ) && false === stripos( $note, 'Adjusted stock' );
				}
			)
		);
	}

	// -------------------------------------------------------------------------
	// Nothing taxable changed: the recorded tax is kept exactly.
	// -------------------------------------------------------------------------

	/**
	 * @testdox A REST save that changes nothing taxable keeps the recorded tax.
	 */
	public function test_plain_resave_keeps_recorded_tax() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'billing' => array( 'phone' => '555-0100' ) ) );

		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'no tax note when nothing taxable changed' );
	}

	/**
	 * @testdox Re-sending unchanged line items (as a POS re-PUT does) keeps the recorded tax.
	 */
	public function test_resending_unchanged_items_keeps_recorded_tax() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 1,
						'subtotal' => '10.00',
						'total'    => '10.00',
					),
					array(
						'id'       => $fixture['b'],
						'quantity' => 1,
						'subtotal' => '20.00',
						'total'    => '20.00',
					),
				),
			)
		);

		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox A hand-typed tax figure survives a save that changes nothing taxable.
	 */
	public function test_hand_typed_tax_survives_plain_resave() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '1' ) );

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'billing' => array( 'phone' => '555-0100' ) ) );

		$this->assertEqualsWithDelta( 1.00, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 2.20, 0.30, 37.50 );
	}

	// -------------------------------------------------------------------------
	// The order changed: the rate it was sold at is applied to the new amounts.
	// -------------------------------------------------------------------------

	/**
	 * @testdox Raising a quantity over REST taxes the new amount at the rate the order was sold at.
	 */
	public function test_quantity_increase_moves_tax() {
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

		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox Raising the quantity of an item taxed $0 under the order's rate keeps it at $0.
	 */
	public function test_quantity_increase_on_zero_taxed_item_keeps_it_untaxed() {
		// A carries the rate id with no tax, as TaxJar's exempt lines are stored.
		$fixture = $this->create_placed_order( array( 'a_tax' => '0' ) );

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

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 46.50 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'the tax did not change' );
	}

	/**
	 * @testdox A $0 rate an item carries without a tax line on the order is not added as a new tax line.
	 */
	public function test_zero_rate_without_tax_line_is_not_added() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '0' ) );
		$order   = $fixture['order'];
		$other   = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '1.0000',
				'tax_rate_name'     => 'Table only',
				'tax_rate_priority' => 2,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
		$zeros   = array(
			$this->rate_id => '0',
			$other         => '0',
		);
		$this->place_item_as(
			$order->get_item( $fixture['a'] ),
			'10',
			array(
				'total'    => $zeros,
				'subtotal' => $zeros,
			),
			'10'
		);

		$order = $this->rest_update(
			$order->get_id(),
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

		$this->assertSame( array( $this->rate_id ), array_keys( $order->get_item( $fixture['a'] )->get_taxes()['total'] ) );
		$this->assert_order_tax( $order, 1.20, 0.30, 46.50 );
	}

	/**
	 * @testdox An order whose only item is taxed $0, with no shipping, keeps the $0 over two edits.
	 */
	public function test_only_item_taxed_zero_stays_untaxed_over_two_edits() {
		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );
		$item_id = $order->add_product( $this->create_product( '18' ), 1 );
		$order->get_item( $item_id, false )->set_taxes(
			array(
				'total'    => array( $this->rate_id => '0' ),
				'subtotal' => array( $this->rate_id => '0' ),
			)
		);

		$tax_line = new WC_Order_Item_Tax();
		$tax_line->set_rate_id( $this->rate_id );
		$tax_line->set_rate_code( 'US-CO-CO TAX-1' );
		$tax_line->set_label( 'CO Tax' );
		$tax_line->set_rate_percent( 6.0 );
		$tax_line->set_compound( false );
		$tax_line->set_tax_total( '0' );
		$tax_line->set_shipping_tax_total( '0' );
		$order->add_item( $tax_line );

		$order->set_total( '18' );
		$order->set_status( 'on-hold' );
		$order->save();
		$this->forget_created_in_request();

		// Without its tax line, the second edit would be treated as the order's first tax calculation.
		foreach ( array( 2, 3 ) as $quantity ) {
			$amount = (string) ( 18 * $quantity );
			$order  = $this->rest_update(
				$order->get_id(),
				array(
					'line_items' => array(
						array(
							'id'       => $item_id,
							'quantity' => $quantity,
							'subtotal' => $amount,
							'total'    => $amount,
						),
					),
				)
			);

			$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $item_id ), 0.001, "item tax at quantity $quantity" );
			$this->assertCount( 1, $order->get_taxes(), "tax line kept at quantity $quantity" );
			$this->assertEqualsWithDelta( (float) $amount, (float) $order->get_total(), 0.001, "order total at quantity $quantity" );
		}

		$this->assertSame( array(), $this->tax_notes( $order ), 'the tax did not change' );
	}

	/**
	 * Give a placed order's item new saved amounts and taxes, as if it had been placed so.
	 *
	 * Forgets the amounts it had before, so the next request sees only its own edit.
	 *
	 * @param WC_Order_Item $item     Item.
	 * @param string        $total    Total.
	 * @param array         $taxes    Taxes array.
	 * @param string|null   $subtotal Subtotal, for a line item.
	 */
	private function place_item_as( $item, $total, array $taxes, $subtotal = null ) {
		$item->set_total( $total );
		if ( null !== $subtotal ) {
			$item->set_subtotal( $subtotal );
		}
		$item->set_taxes( $taxes );
		$item->save();

		$property = new ReflectionProperty( $this->integration, 'order_item_base_before_save' );
		$property->setAccessible( true );
		$property->setValue( $this->integration, array() );
		$this->forget_created_in_request();
	}

	/**
	 * Situations where a changed item was charged $0 at its rate for a reason other than
	 * being exempt, so it is taxed at that rate again once it has an amount.
	 *
	 * @return array
	 */
	public function provide_zero_tax_that_is_not_exempt() {
		return array(
			'line discounted to $0'    => array(
				'a',
				'0',
				'10',
				array(
					'total'    => array( 'RATE' => '0' ),
					'subtotal' => array( 'RATE' => '0.6' ),
				),
				array(
					'line_items' => array(
						array(
							'id'       => 'ID',
							'subtotal' => '10.00',
							'total'    => '10.00',
						),
					),
				),
				0.60,
			),
			'free shipping now priced' => array(
				'shipping',
				'0',
				null,
				array( 'total' => array( 'RATE' => '0' ) ),
				array(
					'shipping_lines' => array(
						array(
							'id'    => 'ID',
							'total' => '5.00',
						),
					),
				),
				0.30,
			),
		);
	}

	/**
	 * @testdox An item charged $0 only because its amount was $0 is taxed at its rate once it has an amount.
	 * @dataProvider provide_zero_tax_that_is_not_exempt
	 *
	 * @param string      $which    Fixture item key.
	 * @param string      $total    Total it was placed with.
	 * @param string|null $subtotal Subtotal it was placed with.
	 * @param array       $taxes    Taxes it was placed with; RATE is replaced by the rate id.
	 * @param array       $body     REST body; ID is replaced by the item id.
	 * @param float       $expected Its tax after the edit.
	 */
	public function test_zero_tax_that_is_not_exempt_is_taxed_again( $which, $total, $subtotal, $taxes, $body, $expected ) {
		$fixture = $this->create_placed_order();
		$order   = $fixture['order'];
		$item    = 'shipping' === $which ? $order->get_item( $fixture['shipping'] ) : $order->get_item( $fixture[ $which ] );

		$rate_id = $this->rate_id;
		$taxes   = array_map(
			static function ( $by_rate ) use ( $rate_id ) {
				return array( $rate_id => $by_rate['RATE'] );
			},
			$taxes
		);
		$this->place_item_as( $item, $total, $taxes, $subtotal );

		$type                   = key( $body );
		$body[ $type ][0]['id'] = $item->get_id();
		$order                  = $this->rest_update( $order->get_id(), $body );

		$this->assertEqualsWithDelta( $expected, $this->item_tax( $order, $item->get_id() ), 0.001 );
	}

	/**
	 * @testdox An exempt line discounted to $0 stays at $0 when the discount is removed.
	 */
	public function test_exempt_line_discounted_to_zero_stays_untaxed() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '0' ) );
		$order   = $fixture['order'];
		$this->place_item_as(
			$order->get_item( $fixture['a'] ),
			'0',
			array(
				'total'    => array( $this->rate_id => '0' ),
				'subtotal' => array( $this->rate_id => '0' ),
			),
			'10'
		);

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'subtotal' => '10.00',
						'total'    => '10.00',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
	}

	/**
	 * @testdox Next to an item taxed $0, a taxed item in the same class still moves with its quantity.
	 */
	public function test_zero_taxed_item_beside_taxed_item_both_changed() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '0' ) );

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
					array(
						'id'       => $fixture['b'],
						'quantity' => 2,
						'subtotal' => '40.00',
						'total'    => '40.00',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 2.40, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 67.70 );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox Removing an item over REST removes its tax.
	 */
	public function test_item_removal_moves_tax() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['b'],
						'quantity' => 0,
					),
				),
			)
		);

		$this->assertCount( 1, $order->get_items(), 'B removed' );
		$this->assert_order_tax( $order, 0.60, 0.30, 15.90 );
	}

	/**
	 * @testdox Changing the shipping amount over REST moves the shipping tax.
	 */
	public function test_shipping_change_moves_shipping_tax() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'    => $fixture['shipping'],
						'total' => '10.00',
					),
				),
			)
		);

		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
	}

	/**
	 * @testdox A new shipping line uses the rate of the order's existing shipping.
	 */
	public function test_new_shipping_line_uses_shipping_rate() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertCount( 2, $order->get_shipping_methods() );
		$this->assert_order_tax( $order, 1.80, 0.90, 47.70 );
	}

	/**
	 * @testdox A new taxable fee uses the rate of the order's items in the same tax class.
	 */
	public function test_new_fee_uses_matching_class_rate() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'fee_lines' => array(
					array(
						'name'       => 'Service',
						'total'      => '10.00',
						'tax_status' => 'taxable',
						'tax_class'  => '',
					),
				),
			)
		);

		$fees = $order->get_fees();
		$fee  = reset( $fees );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fee->get_id() ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox On an order that can still be edited, a fee changed to not taxable loses its tax.
	 */
	public function test_fee_changed_to_not_taxable_loses_tax_on_editable_order() {
		$fixture = $this->create_editable_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'fee_lines' => array(
					array(
						'name'       => 'Service',
						'total'      => '10.00',
						'tax_status' => 'taxable',
					),
				),
			)
		);

		$fees   = $order->get_fees();
		$fee_id = reset( $fees )->get_id();

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'fee_lines' => array(
					array(
						'id'         => $fee_id,
						'total'      => '12.00',
						'tax_status' => 'none',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fee_id ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 49.10 );
	}

	/**
	 * Add a $10 fee to the fixture order over REST.
	 *
	 * @param int    $order_id   Order id.
	 * @param string $tax_status Fee tax status.
	 * @return array{order: WC_Order, fee: int}
	 */
	private function add_fee( $order_id, $tax_status ) {
		$order = $this->rest_update(
			$order_id,
			array(
				'fee_lines' => array(
					array(
						'name'       => 'Service',
						'total'      => '10.00',
						'tax_status' => $tax_status,
					),
				),
			)
		);

		$fees = $order->get_fees();

		return array(
			'order' => $order,
			'fee'   => reset( $fees )->get_id(),
		);
	}

	/**
	 * Whether one of the order's tax notes says an item was left untaxed.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private function has_untaxed_note( WC_Order $order ) {
		return (bool) preg_grep( '/No tax was added/', $this->tax_notes( $order ) );
	}

	/**
	 * Whether one of the order's tax notes is the note for an edit of a locked order.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private function has_kept_tax_note( WC_Order $order ) {
		return (bool) preg_grep( '/This order changed after its tax was charged\./', $this->tax_notes( $order ) );
	}

	/**
	 * Pay an order, as a gateway does: it gets a payment date, so its tax is locked.
	 *
	 * @param WC_Order $order Order.
	 * @return WC_Order The order, read again.
	 */
	private function pay_order( WC_Order $order ) {
		$order = wc_get_order( $order->get_id() );
		$order->set_status( 'processing' );
		$order->save();
		$this->forget_created_in_request();
		$this->assertNotNull( $order->get_date_paid(), 'the paid order has a payment date' );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * The placed order, paid: it has a payment date, so its tax is locked.
	 *
	 * @param array $overrides See create_placed_order().
	 * @return array See create_placed_order().
	 */
	private function create_paid_order( array $overrides = array() ) {
		$fixture = $this->create_placed_order( array_merge( $overrides, array( 'status' => 'processing' ) ) );
		$this->assertNotNull( $fixture['order']->get_date_paid(), 'the paid fixture has a payment date' );

		return $fixture;
	}

	/**
	 * The placed order, moved to on hold with no payment date: it can be edited and its tax is not locked.
	 *
	 * @return array See create_placed_order().
	 */
	private function create_editable_order() {
		$fixture = $this->create_placed_order();
		$fixture['order']->set_status( 'on-hold' );
		$fixture['order']->save();

		return $fixture;
	}

	/**
	 * Move a fixture item's product to another tax class in the catalog, as a merchant
	 * editing the product after the sale does.
	 *
	 * @param WC_Order $order     Order.
	 * @param int      $item_id   Item id.
	 * @param string   $tax_class New tax class of the product.
	 * @return WC_Product
	 */
	private function move_product_to_class( WC_Order $order, $item_id, $tax_class ) {
		$product = wc_get_product( $order->get_item( $item_id )->get_product_id() );
		$product->set_tax_class( $tax_class );
		$product->save();

		return $product;
	}

	/**
	 * Put a fixture line on a new variation that is in its own tax class, keeping the
	 * line's $10 and $0.60 tax. The variable product is in the Standard class.
	 *
	 * @param WC_Order $order             Order.
	 * @param int      $item_id           Item id.
	 * @param string   $variation_class   Tax class of the variation the line is on.
	 * @param string   $other_class       Tax class of a second variation of the same product.
	 * @return array{parent: WC_Product, other: WC_Product}
	 */
	private function place_line_on_variation( WC_Order $order, $item_id, $variation_class, $other_class = '' ) {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Variable product' );
		$parent->set_tax_class( '' );
		$parent->save();

		$variations = array();
		foreach ( array( $variation_class, $other_class ) as $tax_class ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $parent->get_id() );
			$variation->set_regular_price( '10' );
			$variation->set_tax_class( $tax_class );
			$variation->save();
			$variations[] = $variation;
		}
		// The variations go first, so deleting the parent finds nothing left to delete.
		array_push( $this->products, $variations[0], $variations[1], $parent );

		$item = $order->get_item( $item_id );
		$item->set_product( $variations[0] );
		$taxes = array(
			'total'    => array( $this->rate_id => '0.6' ),
			'subtotal' => array( $this->rate_id => '0.6' ),
		);
		$this->place_item_as( $item, '10', $taxes, '10' );

		return array(
			'parent' => $parent,
			'other'  => $variations[1],
		);
	}

	/**
	 * @testdox On an order that can still be edited, a fee made not taxable loses its tax even when its amount stays the same.
	 */
	public function test_fee_tax_status_change_alone_moves_tax_on_editable_order() {
		$fixture = $this->create_editable_order();
		$added   = $this->add_fee( $fixture['order']->get_id(), 'taxable' );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $added['order'], $added['fee'] ), 0.001, 'the fee starts taxed' );

		$order = $this->rest_update(
			$added['order']->get_id(),
			array(
				'fee_lines' => array(
					array(
						'id'         => $added['fee'],
						'tax_status' => 'none',
					),
				),
			)
		);

		// 30 + 10 fee + 5 shipping + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $added['fee'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
	}

	/**
	 * @testdox On an order that can still be edited, a fee made taxable is taxed at the rate of the order's items in its tax class.
	 */
	public function test_fee_made_taxable_uses_matching_class_rate_on_editable_order() {
		$fixture = $this->create_editable_order();
		$added   = $this->add_fee( $fixture['order']->get_id(), 'none' );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $added['order'], $added['fee'] ), 0.001, 'the fee starts untaxed' );

		$order = $this->rest_update(
			$added['order']->get_id(),
			array(
				'fee_lines' => array(
					array(
						'id'         => $added['fee'],
						'tax_status' => 'taxable',
					),
				),
			)
		);

		// 6% of $10; 30 + 10 + 5 + 2.40 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $added['fee'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox On an order that can still be edited, an item moved to a tax class the order has no rate for loses its tax, with a note.
	 */
	public function test_item_tax_class_change_alone_moves_tax_on_editable_order() {
		$fixture = $this->create_editable_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		// A untaxed, B 6% of $20; 30 + 5 + 1.20 + 0.30.
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ), 'a note explains the untaxed item' );
	}

	/**
	 * @testdox On an order that can still be edited, a tax class sent for the line counts even when the product now has the same class.
	 */
	public function test_tax_class_sent_for_line_counts_when_product_has_it_on_editable_order() {
		$fixture = $this->create_editable_order();
		$this->move_product_to_class( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ) );
	}

	/**
	 * @testdox On a paid order, re-sending product_id for a line whose product changed tax class keeps the line's class and tax.
	 */
	public function test_resent_product_id_keeps_class_and_tax_on_locked_order() {
		$fixture = $this->create_paid_order();
		$product = $this->move_product_to_class( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertSame( '', $order->get_item( $fixture['a'] )->get_tax_class(), 'The line keeps the class it was sold in' );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox On an order that can still be edited, re-sending product_id for a line whose product changed tax class taxes the line by its new class.
	 */
	public function test_resent_product_id_moves_tax_to_product_class_on_editable_order() {
		$fixture = $this->create_placed_order();
		$fixture['order']->set_status( 'on-hold' );
		$fixture['order']->save();
		$product = $this->move_product_to_class( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		// The order has no Reduced rate, so the line is untaxed and the note says so.
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a line whose saved tax class was deleted keeps its tax on a re-send.
	 */
	public function test_resent_product_id_keeps_tax_when_saved_class_was_deleted() {
		$fixture = $this->create_paid_order();
		WC_Tax::create_tax_class( 'Gift wrap', 'gift-wrap' );
		$item = $fixture['order']->get_item( $fixture['a'] );
		$item->set_tax_class( 'gift-wrap' );
		$taxes = array(
			'total'    => array( $this->rate_id => '0.6' ),
			'subtotal' => array( $this->rate_id => '0.6' ),
		);
		$this->place_item_as( $item, '10', $taxes, '10' );
		WC_Tax::delete_tax_class_by( 'slug', 'gift-wrap' );
		$product = $this->move_product_to_class( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $product->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		// WooCommerce reads a deleted class back as Standard, so that is the class kept.
		$this->assertSame( '', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox On an order that can still be edited, a line switched to a product in another tax class is taxed for that class.
	 */
	public function test_line_switched_to_product_in_other_class_moves_tax_on_editable_order() {
		$fixture = $this->create_editable_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $this->create_product( '10', 'reduced-rate' )->get_id(),
					),
				),
			)
		);

		// The line keeps its $10; the order has no Reduced rate, so it is untaxed.
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ) );
	}

	/**
	 * @testdox On an order that can still be edited, a line switched to another variation of its product in another tax class is taxed for that class.
	 */
	public function test_line_switched_to_other_variation_in_other_class_moves_tax_on_editable_order() {
		$fixture  = $this->create_editable_order();
		$products = $this->place_line_on_variation( $fixture['order'], $fixture['a'], '', 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'           => $fixture['a'],
						'product_id'   => $products['parent']->get_id(),
						'variation_id' => $products['other']->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);

		// The line keeps its $10; the order has no Reduced rate, so it is untaxed.
		$this->assertSame( $products['other']->get_id(), $order->get_item( $fixture['a'] )->get_variation_id() );
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ) );
	}

	/**
	 * @testdox On a paid order, sending only the parent's product_id for a variation line keeps the variation's class and the line's tax.
	 */
	public function test_parent_product_id_keeps_variation_class_and_tax_on_locked_order() {
		$fixture  = $this->create_paid_order();
		$products = $this->place_line_on_variation( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $products['parent']->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox On an order that can still be edited, sending only the parent's product_id for a variation line gives it the parent's class.
	 */
	public function test_parent_product_id_gives_variation_line_parent_class_on_editable_order() {
		$fixture = $this->create_placed_order();
		$fixture['order']->set_status( 'on-hold' );
		$fixture['order']->save();
		$products = $this->place_line_on_variation( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $products['parent']->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		// WooCommerce gives the line the parent's Standard class, and the order has a
		// Standard rate from line B: 6% of $10.
		$this->assertSame( '', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
	}

	/**
	 * @testdox On a paid order, a class another callback sets on the line is kept and moves the tax.
	 */
	public function test_class_set_by_other_callback_stays_on_locked_order() {
		$fixture = $this->create_paid_order();

		add_action(
			'woocommerce_rest_set_order_item',
			static function ( $item ) {
				$item->set_tax_class( 'reduced-rate' );
			},
			5
		);

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 1,
					),
				),
			)
		);

		// Not the product's class, so not a reset: it stays, and the tax charged stays too.
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a class another callback sets on a variation line is kept when it's neither the variation's nor the parent's.
	 */
	public function test_class_set_by_other_callback_stays_on_locked_variation_line() {
		$fixture = $this->create_paid_order();
		$this->place_line_on_variation( $fixture['order'], $fixture['a'], 'reduced-rate' );

		add_action(
			'woocommerce_rest_set_order_item',
			static function ( $item ) {
				$item->set_tax_class( 'zero-rate' );
			},
			5
		);

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 1,
					),
				),
			)
		);

		// The variation is Reduced rate and its parent Standard, so this isn't a reset.
		$this->assertSame( 'zero-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On an order that can still be edited, items moved out of a tax class leave their rate to a new item in that class.
	 */
	public function test_reclassed_items_leave_their_rate_to_their_old_class_on_editable_order() {
		$fixture = $this->create_editable_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
					array(
						'id'        => $fixture['b'],
						'tax_class' => 'reduced-rate',
					),
					array(
						'product_id' => $this->create_product( '30' )->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$c_id = $this->other_line_item( $order, array( $fixture['a'], $fixture['b'] ) );
		// A and B untaxed; C 6% of $30. 10 + 20 + 30 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.80, $this->item_tax( $order, $c_id ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 67.10 );
	}

	/**
	 * @testdox On an order that can still be edited, a tax class changed on the item in code, then calculate_totals(), moves the tax the same way.
	 */
	public function test_programmatic_tax_class_change_moves_tax_on_editable_order() {
		$fixture = $this->create_editable_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->get_item( $fixture['a'], false )->set_tax_class( 'reduced-rate' );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
	}

	/**
	 * @testdox On a paid order, code that gives a line its product's new tax class keeps the order's tax.
	 */
	public function test_programmatic_product_reset_keeps_tax_on_locked_order() {
		$fixture = $this->create_paid_order();
		$product = $this->move_product_to_class( $fixture['order'], $fixture['a'], 'reduced-rate' );
		$order   = wc_get_order( $fixture['order']->get_id() );

		// Not a REST request, so the class is not put back before the recalculation.
		$order->get_item( $fixture['a'], false )->set_product( $product );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	// -------------------------------------------------------------------------
	// An order that can't be edited: a class or status change is saved, the tax stays.
	// -------------------------------------------------------------------------

	/**
	 * @testdox On a paid order, a tax class change is saved and the tax charged stays, with a note.
	 */
	public function test_item_tax_class_change_keeps_tax_on_locked_order() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		// Nothing moved: A $0.60, B $1.20, shipping $0.30, total 30 + 5 + 2.10.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
		$this->assertFalse( $this->has_untaxed_note( $order ) );
		$this->assertCount( 1, preg_grep( '/' . preg_quote( $order->get_item( $fixture['a'] )->get_name(), '/' ) . '/', $this->tax_notes( $order ) ), 'the note names the item' );
	}

	/**
	 * @testdox On a paid order, a class sent for a line whose product has it keeps the tax too.
	 */
	public function test_tax_class_sent_for_line_keeps_tax_on_locked_order_when_product_has_it() {
		$fixture = $this->create_paid_order();
		$this->move_product_to_class( $fixture['order'], $fixture['a'], 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a fee made not taxable is saved and keeps its tax, with a note.
	 */
	public function test_fee_made_not_taxable_keeps_tax_on_locked_order() {
		$fixture = $this->create_placed_order();
		$added   = $this->add_fee( $fixture['order']->get_id(), 'taxable' );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $added['order'], $added['fee'] ), 0.001, 'the fee starts taxed' );
		$paid = $this->pay_order( $added['order'] );

		$order = $this->rest_update(
			$paid->get_id(),
			array(
				'fee_lines' => array(
					array(
						'id'         => $added['fee'],
						'tax_status' => 'none',
					),
				),
			)
		);

		$this->assertSame( 'none', $order->get_item( $added['fee'] )->get_tax_status() );
		// 30 + 10 fee + 5 shipping + 2.40 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $added['fee'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a fee made taxable is saved and stays untaxed, with a note.
	 */
	public function test_fee_made_taxable_stays_untaxed_on_locked_order() {
		$fixture = $this->create_placed_order();
		$added   = $this->add_fee( $fixture['order']->get_id(), 'none' );
		$paid    = $this->pay_order( $added['order'] );

		$order = $this->rest_update(
			$paid->get_id(),
			array(
				'fee_lines' => array(
					array(
						'id'         => $added['fee'],
						'tax_status' => 'taxable',
					),
				),
			)
		);

		$this->assertSame( 'taxable', $order->get_item( $added['fee'] )->get_tax_status() );
		// 30 + 10 fee + 5 shipping + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $added['fee'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
		$this->assertFalse( $this->has_untaxed_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a fee made not taxable in the same edit as a new amount keeps the tax it was charged.
	 */
	public function test_fee_status_and_amount_change_keeps_charged_tax_on_locked_order() {
		$fixture = $this->create_placed_order();
		$added   = $this->add_fee( $fixture['order']->get_id(), 'taxable' );
		$paid    = $this->pay_order( $added['order'] );

		$order = $this->rest_update(
			$paid->get_id(),
			array(
				'fee_lines' => array(
					array(
						'id'         => $added['fee'],
						'total'      => '12.00',
						'tax_status' => 'none',
					),
				),
			)
		);

		$this->assertSame( 'none', $order->get_item( $added['fee'] )->get_tax_status() );
		// The $0.60 charged on the $10 fee stays; 30 + 12 + 5 + 2.40 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $added['fee'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 49.70 );
		// Adding the fee before payment wrote its own note; this edit writes one more.
		$notes = array_values( preg_grep( '/after its tax was charged/', $this->tax_notes( $order ) ) );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'This order changed after its tax was charged.', $notes[0] );
		$this->assertStringContainsString( 'Service changed from taxable to not taxable.', $notes[0] );
		$this->assertStringContainsString( 'The tax stays at', $notes[0] );
	}

	/**
	 * @testdox On a paid order, a new amount and class on one line keep the tax it was charged, in a single note.
	 */
	public function test_item_class_and_amount_change_keeps_charged_tax_in_one_note_on_locked_order() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
						'quantity'  => 2,
						'subtotal'  => '20.00',
						'total'     => '20.00',
					),
				),
			)
		);

		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		// A keeps the $0.60 it was charged on $10; 40 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'quantity changed from 1 to 2.', $notes[0] );
		$this->assertStringContainsString( 'tax class changed from Standard to Reduced rate.', $notes[0] );
		// The order has no Reduced rate, so the tax as it is now can't be given.
		$this->assertStringContainsString( 'The tax for the order as it is now was not calculated, because the order has no tax rate for', $notes[0] );
		$this->assertStringNotContainsString( 'Tax updated', $notes[0] );
	}

	/**
	 * @testdox On a paid order, a line switched to another product in another tax class is taxed for that class, as a removed and an added line would be.
	 */
	public function test_line_switched_to_product_in_other_class_moves_tax_on_locked_order() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $this->create_product( '10', 'reduced-rate' )->get_id(),
					),
				),
			)
		);

		// The line keeps its $10; the order has no Reduced rate, so it is untaxed: 1.80 - 0.60.
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ) );
		$this->assertFalse( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a line switched to another variation in another tax class is taxed for that class.
	 */
	public function test_line_switched_to_other_variation_in_other_class_moves_tax_on_locked_order() {
		$fixture  = $this->create_placed_order();
		$products = $this->place_line_on_variation( $fixture['order'], $fixture['a'], '', 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'           => $fixture['a'],
						'product_id'   => $products['parent']->get_id(),
						'variation_id' => $products['other']->get_id(),
						'quantity'     => 1,
					),
				),
			)
		);

		// The line keeps its $10; the order has no Reduced rate, so it is untaxed: 1.80 - 0.60.
		$this->assertSame( $products['other']->get_id(), $order->get_item( $fixture['a'] )->get_variation_id() );
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertTrue( $this->has_untaxed_note( $order ) );
		$this->assertFalse( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a tax class changed on the item in code keeps the tax, with a note.
	 */
	public function test_programmatic_tax_class_change_keeps_tax_on_locked_order() {
		$fixture = $this->create_paid_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->get_item( $fixture['a'], false )->set_tax_class( 'reduced-rate' );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, items moved out of a tax class keep their tax, and a new item in that class gets none.
	 */
	public function test_items_moved_out_of_class_keep_their_tax_on_locked_order() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
					array(
						'id'        => $fixture['b'],
						'tax_class' => 'reduced-rate',
					),
					array(
						'product_id' => $this->create_product( '30' )->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$c_id = $this->other_line_item( $order, array( $fixture['a'], $fixture['b'] ) );
		// A and B keep their tax; C, added after the sale, gets none. 10 + 20 + 30 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $c_id ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 67.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, a line moved to a class keeps its tax, and a new item in that class gets none.
	 */
	public function test_item_moved_to_class_gives_new_item_no_rate_on_locked_order() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
					array(
						'product_id' => $this->create_product( '30', 'reduced-rate' )->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$c_id = $this->other_line_item( $order, array( $fixture['a'], $fixture['b'] ) );
		// A keeps its Standard tax; C, added after the sale, gets none. 10 + 20 + 30 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $c_id ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 67.10 );
		$this->assertFalse( $this->has_untaxed_note( $order ), 'the note for a paid order says it instead' );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, sending the same tax class again writes no second note.
	 */
	public function test_same_tax_class_sent_twice_writes_one_note_on_locked_order() {
		$fixture = $this->create_paid_order();
		$body    = array(
			'line_items' => array(
				array(
					'id'        => $fixture['a'],
					'tax_class' => 'reduced-rate',
				),
			),
		);

		$this->rest_update( $fixture['order']->get_id(), $body );
		$this->forget_created_in_request();
		$order = $this->rest_update( $fixture['order']->get_id(), $body );

		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox A class sent in the request that also unlocks the order keeps the tax: the order was locked when the edit was made.
	 */
	public function test_class_sent_with_status_that_unlocks_order_keeps_tax() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'status'     => 'on-hold',
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertSame( 'on-hold', $order->get_status() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox A class sent in the request that also locks the order moves the tax: the order could be edited when the edit was made.
	 */
	public function test_class_sent_with_status_that_locks_order_moves_tax() {
		$fixture = $this->create_editable_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'status'     => 'processing',
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertSame( 'processing', $order->get_status() );
		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
		$this->assertFalse( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox A store that makes a status editable with wc_order_is_editable has its tax follow a class change there.
	 */
	public function test_status_made_editable_by_filter_moves_tax() {
		$fixture = $this->create_placed_order();
		add_filter( 'wc_order_is_editable', '__return_true' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $fixture['a'],
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);
		remove_filter( 'wc_order_is_editable', '__return_true' );

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.20, 0.30, 36.50 );
	}

	/**
	 * @testdox On an order that can't be edited and has no tax, a class change adds no tax, though the rate table has a rate for the class.
	 */
	public function test_class_change_adds_no_tax_to_untaxed_locked_order() {
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '6.0000',
				'tax_rate_name'     => 'CO Reduced',
				'tax_rate_priority' => '1',
				'tax_rate_compound' => '0',
				'tax_rate_shipping' => '1',
				'tax_rate_order'    => '1',
				'tax_rate_class'    => 'reduced-rate',
			)
		);

		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CO' );
		$order->set_shipping_postcode( '80202' );
		$order->set_shipping_city( 'Denver' );
		$item_id = $order->add_product( $this->create_product( '10' ), 1 );
		$order->set_total( 10 );
		$order->set_status( 'processing' );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'line_items' => array(
					array(
						'id'        => $item_id,
						'tax_class' => 'reduced-rate',
					),
				),
			)
		);

		$this->assertSame( 'reduced-rate', $order->get_item( $item_id )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 10.0, (float) $order->get_total(), 0.001 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox A new product with no rate on the order for its tax class is left untaxed, with a note.
	 */
	public function test_new_item_without_matching_rate_is_untaxed_with_note() {
		$fixture = $this->create_placed_order();
		$reduced = $this->create_product( '10', 'reduced-rate' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'product_id' => $reduced->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertCount( 3, $order->get_items() );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$this->assertNotEmpty( $this->tax_notes( $order ), 'a note explains the untaxed item' );
	}

	/**
	 * @testdox A new product that replaces the last items of its tax class in one edit takes their rate.
	 */
	public function test_replacing_last_items_of_class_keeps_their_rate() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 0,
					),
					array(
						'id'       => $fixture['b'],
						'quantity' => 0,
					),
					array(
						'product_id' => $this->create_product( '30' )->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertCount( 1, $order->get_items() );
		$items = $order->get_items();
		$this->assertEqualsWithDelta( 1.80, $this->item_tax( $order, key( $items ) ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'no note: the tax did not change and nothing was left untaxed' );
	}

	/**
	 * @testdox A new shipping line that replaces the order's only shipping takes its rate.
	 */
	public function test_replacing_only_shipping_keeps_its_rate() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'        => $fixture['shipping'],
						'method_id' => null,
					),
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertCount( 1, $order->get_shipping_methods() );
		$this->assert_order_tax( $order, 1.80, 0.60, 42.40 );
	}

	/**
	 * @testdox Replacing the last items of a class with a product of another class leaves it untaxed, with a note.
	 */
	public function test_replacing_last_items_with_other_class_is_untaxed_with_note() {
		$fixture = $this->create_placed_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 0,
					),
					array(
						'id'       => $fixture['b'],
						'quantity' => 0,
					),
					array(
						'product_id' => $this->create_product( '30', 'reduced-rate' )->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_cart_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.30, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertNotEmpty( $this->tax_notes( $order ), 'a note explains the untaxed item' );
	}

	/**
	 * Build a placed order with items in two tax classes and a negative fee:
	 *
	 *   Product A (Standard)        $10.00  tax 0.60 (6%)
	 *   Product R (Reduced rate)    $20.00  tax 0.40 (2%)
	 *   Discount fee (Standard)     -$6.00  tax -0.12 (6%) and -0.08 (2%)
	 *   ---------------------------------------------------
	 *   cart tax 0.80, no shipping, total 24.80
	 *
	 * WooCommerce taxes a negative fee by splitting it across the order's tax classes,
	 * by each class's share of the order: -$2 of it at Standard's 6%, -$4 at Reduced
	 * rate's 2%. So the fee carries the Reduced rate although its class is Standard.
	 * Both rows are overwritten to 0% in the table, as in the main fixture.
	 *
	 * @return array{order: WC_Order, a: int, r: int, fee: int, reduced_rate_id: int}
	 */
	private function create_placed_order_with_negative_fee() {
		$standard_id = $this->rate_id;
		$reduced_id  = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '0.0000',
				'tax_rate_name'     => 'Overwritten',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => 'reduced-rate',
			)
		);

		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );

		$a_id = $order->add_product( $this->create_product( '10' ), 1 );
		$r_id = $order->add_product( $this->create_product( '20', 'reduced-rate' ), 1 );

		$order->get_item( $a_id, false )->set_taxes(
			array(
				'total'    => array( $standard_id => '0.6' ),
				'subtotal' => array( $standard_id => '0.6' ),
			)
		);
		$order->get_item( $r_id, false )->set_taxes(
			array(
				'total'    => array( $reduced_id => '0.4' ),
				'subtotal' => array( $reduced_id => '0.4' ),
			)
		);

		$fee = new WC_Order_Item_Fee();
		$fee->set_name( 'Discount' );
		$fee->set_tax_class( '' );
		$fee->set_tax_status( 'taxable' );
		$fee->set_total( '-6' );
		$fee->set_taxes(
			array(
				'total' => array(
					$standard_id => '-0.12',
					$reduced_id  => '-0.08',
				),
			)
		);
		$order->add_item( $fee );

		foreach ( array( array( $standard_id, 'US-CO-CO TAX-1', 'CO Tax', 6.0, 0.48 ), array( $reduced_id, 'US-CO-CO REDUCED-1', 'CO Reduced', 2.0, 0.32 ) ) as $line ) {
			$tax_line = new WC_Order_Item_Tax();
			$tax_line->set_rate_id( $line[0] );
			$tax_line->set_rate_code( $line[1] );
			$tax_line->set_label( $line[2] );
			$tax_line->set_rate_percent( $line[3] );
			$tax_line->set_compound( false );
			$tax_line->set_tax_total( $line[4] );
			$tax_line->set_shipping_tax_total( 0 );
			$order->add_item( $tax_line );
		}

		$order->set_cart_tax( 0.80 );
		$order->set_total( 24.80 );
		$order->set_status( 'on-hold' );
		$order->save();
		$this->forget_created_in_request();

		return array(
			'order'           => $order,
			'a'               => $a_id,
			'r'               => $r_id,
			'fee'             => $fee->get_id(),
			'reduced_rate_id' => $reduced_id,
		);
	}

	/**
	 * Per-rate tax on an order item, keyed by rate id.
	 *
	 * @param WC_Order $order   Order.
	 * @param int      $item_id Item id.
	 * @return float[]
	 */
	private function item_taxes_by_rate( WC_Order $order, $item_id ) {
		$taxes = $order->get_item( $item_id )->get_taxes();

		return array_map( 'floatval', array_filter( $taxes['total'] ) );
	}

	/**
	 * The one product line on an order that is not one of the given items.
	 *
	 * @param WC_Order $order    Order.
	 * @param int[]    $item_ids Items to leave out.
	 * @return int
	 */
	private function other_line_item( WC_Order $order, array $item_ids ) {
		$ids = array_diff( array_keys( $order->get_items() ), $item_ids );
		$this->assertCount( 1, $ids );

		return (int) reset( $ids );
	}

	/**
	 * @testdox A new product replacing the last items of its class, with a negative fee among them, takes only that class's rate.
	 */
	public function test_replacing_class_with_negative_fee_keeps_only_its_rate() {
		$fixture = $this->create_placed_order_with_negative_fee();
		$c       = $this->create_product( '30' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['a'],
						'quantity' => 0,
					),
					array(
						'product_id' => $c->get_id(),
						'quantity'   => 1,
					),
				),
				'fee_lines'  => array(
					array(
						'id'   => $fixture['fee'],
						'name' => null,
					),
				),
			)
		);

		$this->assertCount( 0, $order->get_fees(), 'the fee was removed' );
		$c_id = $this->other_line_item( $order, array( $fixture['r'] ) );

		// C is Standard: $30 at 6% = 1.80. R keeps its 0.40 (2% of $20).
		$this->assertEqualsWithDelta( array( $this->rate_id => 1.80 ), $this->item_taxes_by_rate( $order, $c_id ), 0.001, 'C is taxed at the Standard rate only' );
		$this->assertEqualsWithDelta( array( $fixture['reduced_rate_id'] => 0.40 ), $this->item_taxes_by_rate( $order, $fixture['r'] ), 0.001 );
		$this->assertEqualsWithDelta( 2.20, (float) $order->get_cart_tax(), 0.001, 'cart tax' );
		$this->assertEqualsWithDelta( 52.20, (float) $order->get_total(), 0.001, 'order total: 20 + 30 + 2.20' );
	}

	/**
	 * @testdox A new product beside a negative fee of its class takes only that class's rate.
	 */
	public function test_new_item_beside_negative_fee_keeps_only_its_class_rate() {
		$fixture = $this->create_placed_order_with_negative_fee();
		$c       = $this->create_product( '30' );

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'product_id' => $c->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$c_id = $this->other_line_item( $order, array( $fixture['a'], $fixture['r'] ) );

		// C is Standard: $30 at 6% = 1.80. Everything else keeps its tax.
		$this->assertEqualsWithDelta( array( $this->rate_id => 1.80 ), $this->item_taxes_by_rate( $order, $c_id ), 0.001, 'C is taxed at the Standard rate only' );
		$this->assertEqualsWithDelta(
			array(
				$this->rate_id              => -0.12,
				$fixture['reduced_rate_id'] => -0.08,
			),
			$this->item_taxes_by_rate( $order, $fixture['fee'] ),
			0.001,
			'the fee keeps its split tax'
		);
		$this->assertEqualsWithDelta( 2.60, (float) $order->get_cart_tax(), 0.001, 'cart tax: 0.80 + 1.80' );
		$this->assertEqualsWithDelta( 56.60, (float) $order->get_total(), 0.001, 'order total: 10 + 20 - 6 + 30 + 2.60' );
	}

	/**
	 * @testdox A saved item that cannot be loaded does not break the edit.
	 */
	public function test_unloadable_saved_item_is_skipped() {
		$fixture = $this->create_placed_order();
		$a_id    = $fixture['a'];

		// WC_Order_Factory::get_order_item() returns false for an item whose class does not exist.
		add_filter(
			'woocommerce_get_order_item_classname',
			function ( $classname, $item_type, $id ) use ( $a_id ) {
				return (int) $id === $a_id ? 'WC_Order_Item_Missing_Class' : $classname;
			},
			10,
			3
		);

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['b'],
						'quantity' => 0,
					),
					array(
						'product_id' => $this->create_product( '20' )->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$items = $order->get_items();
		$this->assertCount( 1, $items );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, key( $items ) ), 0.001, 'the new item takes the rate of the item it replaced' );
	}

	/**
	 * Make the fixture's shipping untaxed, as in a state that does not tax shipping.
	 *
	 * @param array $fixture Fixture from create_placed_order().
	 * @return WC_Order
	 */
	private function untax_fixture_shipping( array $fixture ) {
		$order = $fixture['order'];
		$order->get_item( $fixture['shipping'], false )->set_taxes( false );
		$taxes = $order->get_taxes();
		reset( $taxes )->set_shipping_tax_total( 0 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 36.80 );
		$order->save();
		$this->forget_created_in_request();

		return $order;
	}

	/**
	 * @testdox A new shipping line on an order that does not tax shipping gets no tax and no note.
	 */
	public function test_new_shipping_line_on_untaxed_shipping_order_has_no_note() {
		$order = $this->untax_fixture_shipping( $this->create_placed_order() );

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertCount( 2, $order->get_shipping_methods() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 46.80, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'no note: the order never taxed shipping' );
	}

	/**
	 * @testdox Replacing the only shipping line on an order that does not tax shipping adds no note.
	 */
	public function test_replacing_untaxed_shipping_has_no_note() {
		$fixture = $this->create_placed_order();
		$order   = $this->untax_fixture_shipping( $fixture );

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'        => $fixture['shipping'],
						'method_id' => null,
					),
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '5.00',
					),
				),
			)
		);

		$this->assertCount( 1, $order->get_shipping_methods() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 36.80, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'no note: the order never taxed shipping' );
	}

	/**
	 * @testdox Applying a coupon over REST moves the tax and the discount tax.
	 */
	public function test_coupon_moves_tax() {
		$fixture = $this->create_placed_order();
		$coupon  = WC_Helper_Coupon::create_coupon(
			'tenpercent',
			array(
				'discount_type' => 'percent',
				'coupon_amount' => '10',
			)
		);

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array( 'coupon_lines' => array( array( 'code' => 'tenpercent' ) ) )
		);

		$this->assertEqualsWithDelta( 0.54, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 1.08, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assertEqualsWithDelta( 0.18, (float) $order->get_discount_tax(), 0.001, 'discount tax' );
		$this->assert_order_tax( $order, 1.62, 0.30, 33.92 );

		$coupon->delete( true );
	}

	/**
	 * @testdox A hand-typed tax figure on a changed item is replaced by the order's rate.
	 */
	public function test_hand_typed_tax_replaced_when_its_item_changes() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '1' ) );

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

		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox A hand-typed tax figure on an untouched item survives an edit to another item.
	 */
	public function test_hand_typed_tax_on_untouched_item_survives() {
		$fixture = $this->create_placed_order( array( 'a_tax' => '1' ) );

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

		$this->assertEqualsWithDelta( 1.00, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 2.40, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assert_order_tax( $order, 3.40, 0.30, 58.70 );
	}

	/**
	 * @testdox A tax change writes an order note with the old and new tax.
	 */
	public function test_tax_change_writes_note() {
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

		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes, 'exactly one tax note' );
		$this->assertStringContainsString( '2.10', $notes[0], 'old tax' );
		$this->assertStringContainsString( '2.70', $notes[0], 'new tax' );
		$this->assertStringContainsString( 'already saved on the order were used', $notes[0] );
		$this->assertMatchesRegularExpression( '/updated from .*2\.10.* to .*2\.70.* because the order changed/', $notes[0], 'old tax first, in words' );
		$this->assertStringNotContainsString( 'when the order was placed', $notes[0] );
	}

	/**
	 * @testdox A programmatic item edit followed by calculate_totals() moves the tax the same way.
	 */
	public function test_programmatic_edit_moves_tax() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$item = $order->get_item( $fixture['a'], false );
		$item->set_quantity( 2 );
		$item->set_subtotal( '20' );
		$item->set_total( '20' );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );

		// A second recalculation with nothing new changed is a no-op.
		$order->calculate_totals( true );
		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertCount( 1, $this->tax_notes( $order ), 'the no-op pass writes no second note' );
	}

	/**
	 * @testdox A product added with add_product() (which saves it at once) is taxed at the order's rate.
	 */
	public function test_programmatic_add_product_is_taxed() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$item_id = $order->add_product( $this->create_product( '10' ), 1 );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $item_id ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox An item removed and saved before calculate_totals() (as the V4 REST route does) takes its tax with it.
	 */
	public function test_remove_and_save_before_recalculation_moves_tax() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->remove_item( $fixture['b'] );
		$order->save();
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $order->get_items(), 'B removed' );
		$this->assert_order_tax( $order, 0.60, 0.30, 15.90 );
		$this->assertCount( 1, $this->tax_notes( $order ) );
	}

	/**
	 * @testdox An item deleted with wc_delete_order_item() before a recalculation takes its tax with it.
	 */
	public function test_deleted_item_before_recalculation_moves_tax() {
		$fixture = $this->create_placed_order();

		wc_delete_order_item( $fixture['b'] );
		$order = wc_get_order( $fixture['order']->get_id() );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 0.60, 0.30, 15.90 );
	}

	/**
	 * @testdox Replacing every item of a class, saved before the recalculation, taxes the new item at the removed items' rate.
	 */
	public function test_replacing_items_saved_before_recalculation_keeps_their_rate() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->remove_item( $fixture['a'] );
		$order->remove_item( $fixture['b'] );
		$c = new WC_Order_Item_Product();
		$c->set_product( $this->create_product( '30' ) );
		$c->set_quantity( 1 );
		$c->set_subtotal( '30' );
		$c->set_total( '30' );
		$order->add_item( $c );
		$order->save();
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 1.80, $this->item_tax( $order, $c->get_id() ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
	}

	/**
	 * @testdox An item retyped off the order (Subscriptions' remove) loses its tax, and gets it back when retyped onto it (Undo).
	 */
	public function test_retyped_item_is_removed_and_restored() {
		$fixture  = $this->create_placed_order();
		$order_id = $fixture['order']->get_id();
		// Subscriptions maps its removed-item type to a product item class.
		$classname = static function ( $classname, $item_type ) {
			return 'line_item_removed' === $item_type ? 'WC_Order_Item_Product' : $classname;
		};
		add_filter( 'woocommerce_get_order_item_classname', $classname, 10, 2 );

		try {
			// What wcs_update_order_item_type() does, then WCS_Remove_Item recalculates.
			wc_update_order_item( $fixture['b'], array( 'order_item_type' => 'line_item_removed' ) );
			wp_cache_delete( 'order-items-' . $order_id, 'orders' );
			wc_get_order( $order_id )->calculate_totals();

			$order = wc_get_order( $order_id );
			$this->assertCount( 1, $order->get_items(), 'B is off the order' );
			$this->assert_order_tax( $order, 0.60, 0.30, 15.90 );

			wc_update_order_item( $fixture['b'], array( 'order_item_type' => 'line_item' ) );
			wp_cache_delete( 'order-items-' . $order_id, 'orders' );
			wc_get_order( $order_id )->calculate_totals();
		} finally {
			remove_filter( 'woocommerce_get_order_item_classname', $classname, 10 );
		}

		$order = wc_get_order( $order_id );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['b'] ), 0.001, 'B comes back with its own tax' );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
	}

	/**
	 * @testdox Items removed by type with remove_order_items() and saved before the recalculation take their tax with them.
	 */
	public function test_remove_order_items_and_save_before_recalculation_moves_tax() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		// Core deletes these on save() with direct SQL, without the per-item delete hook.
		$order->remove_order_items( 'shipping' );
		$order->save();
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertCount( 0, $order->get_shipping_methods(), 'shipping removed' );
		$this->assert_order_tax( $order, 1.80, 0.00, 31.80 );
	}

	/**
	 * @testdox Replacing every line item with remove_order_items(), saved before the recalculation, taxes the new item at the removed items' rate.
	 */
	public function test_replacing_items_with_remove_order_items_keeps_their_rate() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->remove_order_items( 'line_item' );
		$c = new WC_Order_Item_Product();
		$c->set_product( $this->create_product( '30' ) );
		$c->set_quantity( 1 );
		$c->set_subtotal( '30' );
		$c->set_total( '30' );
		$order->add_item( $c );
		$order->save();
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 1.80, $this->item_tax( $order, $c->get_id() ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), preg_grep( '/no tax was added/i', $this->tax_notes( $order ) ), 'no untaxed-item note' );
	}

	/**
	 * @testdox Two REST quantity edits after a lookup removed the order's rate row keep taxing at the rate it was sold at.
	 *
	 * WooCommerce drops the tax line of a rate it cannot find, the recorded rates put it
	 * back and update_taxes() saves it as "Tax" at 0% before the recorded name and
	 * percent are set. The second edit re-applies whatever the first one left on the line.
	 */
	public function test_rest_edits_after_rate_row_removed_keep_recorded_rate() {
		$fixture = $this->create_placed_order();
		WC_Tax::_delete_tax_rate( $this->rate_id );

		$this->start_new_request();
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
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertSame( 'US-CO-CO TAX-1', current( $order->get_taxes() )->get_rate_code() );

		$this->start_new_request();
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
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assertEqualsWithDelta( 2.40, $this->item_tax( $order, $fixture['b'] ), 0.001 );
		$this->assert_order_tax( $order, 3.60, 0.30, 68.90 );
		$this->assertSame( 'US-CO-CO TAX-1', current( $order->get_taxes() )->get_rate_code() );
	}

	// -------------------------------------------------------------------------
	// Edges.
	// -------------------------------------------------------------------------

	/**
	 * @testdox A new item that arrives already taxed keeps its tax (Store API draft rebuilt from the cart).
	 */
	public function test_new_item_arriving_with_tax_keeps_it() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		// What OrderController::update_line_items_from_cart() does: delete the line
		// items outright, then add the cart's items with the tax the cart calculated.
		$order->remove_order_items( 'line_item' );
		$item = new WC_Order_Item_Product();
		$item->set_product( $this->create_product( '15' ) );
		$item->set_quantity( 1 );
		$item->set_subtotal( '15' );
		$item->set_total( '15' );
		$item->set_taxes(
			array(
				'total'    => array( $this->rate_id => '0.9' ),
				'subtotal' => array( $this->rate_id => '0.9' ),
			)
		);
		$order->add_item( $item );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.90, $this->item_tax( $order, $item->get_id() ), 0.001 );
		$this->assert_order_tax( $order, 0.90, 0.30, 21.20 );
		foreach ( $this->tax_notes( $order ) as $note ) {
			$this->assertStringNotContainsString( 'No tax was added', $note );
		}
	}

	/**
	 * @testdox A checkout draft's tax moves with it, without writing a note.
	 */
	public function test_checkout_draft_moves_tax_without_note() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );
		$order->set_status( 'checkout-draft' );
		$order->save();

		$item = $order->get_item( $fixture['a'], false );
		$item->set_quantity( 2 );
		$item->set_subtotal( '20' );
		$item->set_total( '20' );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox An old order without a stored rate keeps its recorded tax and gets a note.
	 */
	public function test_order_without_stored_rate_keeps_tax_with_note() {
		$fixture = $this->create_placed_order( array( 'rate_percent' => null ) );

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

		$this->assertEqualsWithDelta( 1.80, (float) $order->get_cart_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.30, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 47.10, (float) $order->get_total(), 0.001 );
		$this->assertNotEmpty( $this->tax_notes( $order ), 'a note says the tax was not updated' );
	}

	/**
	 * @testdox An order made VAT exempt after the sale is not given its tax back.
	 */
	public function test_vat_exempt_order_is_not_retaxed() {
		$fixture = $this->create_placed_order();
		add_filter( 'woocommerce_order_is_vat_exempt', '__return_true' );

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'billing' => array( 'phone' => '555-0100' ) ) );

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 35.00, (float) $order->get_total(), 0.001 );
	}

	// -------------------------------------------------------------------------
	// calculate_taxes() and calculate_totals() called apart.
	// -------------------------------------------------------------------------

	/**
	 * Change A's quantity to 2 on the order object and run calculate_taxes() alone,
	 * which leaves a snapshot that no calculate_totals() has consumed yet.
	 *
	 * @param WC_Order $order   Order.
	 * @param int      $item_id Item id of A.
	 */
	private function bare_calculate_taxes_after_quantity_change( WC_Order $order, $item_id ) {
		$item = $order->get_item( $item_id, false );
		$item->set_quantity( 2 );
		$item->set_subtotal( '20' );
		$item->set_total( '20' );
		$order->calculate_taxes();
	}

	/**
	 * @testdox A snapshot left by a bare calculate_taxes() does not give a VAT exempt order its tax back in calculate_totals( true ).
	 */
	public function test_leftover_snapshot_does_not_undo_vat_exemption() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$this->bare_calculate_taxes_after_quantity_change( $order, $fixture['a'] );
		add_filter( 'woocommerce_order_is_vat_exempt', '__return_true' );
		$order->calculate_totals( true );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 45.00, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox A snapshot left by a bare calculate_taxes() does not give a VAT exempt order its tax back in calculate_totals( false ).
	 */
	public function test_leftover_snapshot_does_not_undo_vat_exemption_without_taxes() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$this->bare_calculate_taxes_after_quantity_change( $order, $fixture['a'] );
		add_filter( 'woocommerce_order_is_vat_exempt', '__return_true' );
		$order->calculate_totals( false );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 45.00, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox A calculate_taxes() rejected by a gate drops the snapshot an earlier one left.
	 */
	public function test_rejected_recalculation_drops_leftover_snapshot() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$this->bare_calculate_taxes_after_quantity_change( $order, $fixture['a'] );
		add_filter( 'woocommerce_order_is_vat_exempt', '__return_true' );
		$order->calculate_taxes();

		$property = new ReflectionProperty( $this->integration, 'pre_recalculation_tax_snapshots' );
		$property->setAccessible( true );
		$this->assertSame( array(), $property->getValue( $this->integration ) );
	}

	/**
	 * @testdox calculate_totals( false ) on its own leaves the item taxes alone.
	 */
	public function test_totals_without_taxes_alone_keeps_item_taxes() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$item = $order->get_item( $fixture['a'], false );
		$item->set_quantity( 2 );
		$item->set_subtotal( '20' );
		$item->set_total( '20' );
		$item->save();
		$order->calculate_totals( false );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	/**
	 * @testdox calculate_taxes() then calculate_totals( false ) still keeps the recorded tax.
	 */
	public function test_taxes_then_totals_without_taxes_keeps_recorded_tax() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order->calculate_taxes();
		$order->calculate_totals( false );

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
	}

	/**
	 * @testdox calculate_taxes() then calculate_totals( false ) moves the tax of a changed item.
	 */
	public function test_taxes_then_totals_without_taxes_moves_tax() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$this->bare_calculate_taxes_after_quantity_change( $order, $fixture['a'] );
		$order->calculate_totals( false );

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox TaxesController::calc_line_taxes() called outside AJAX keeps the recorded tax.
	 */
	public function test_calc_line_taxes_keeps_recorded_tax() {
		$fixture = $this->create_placed_order();

		$order = wc_get_container()->get( Automattic\WooCommerce\Internal\Orders\TaxesController::class )->calc_line_taxes(
			array(
				'order_id' => $fixture['order']->get_id(),
				'items'    => '',
				'country'  => 'US',
				'state'    => 'CO',
				'postcode' => '80202',
				'city'     => 'Denver',
			)
		);

		// Amounts only: wc_save_order_items() runs update_taxes(), which reloads the
		// tax line's label and rate from the table.
		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 1.80, (float) $order->get_cart_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.30, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 37.10, (float) $order->get_total(), 0.001 );
	}

	/**
	 * Send the request the admin's Recalculate button sends, moving a line to Reduced rate.
	 *
	 * @param WC_Order $order   Order.
	 * @param int      $item_id Line to move.
	 * @return WC_Order The order read back.
	 */
	private function admin_recalculate_with_class_change( WC_Order $order, $item_id ) {
		$post = array(
			'order_id' => $order->get_id(),
			// The admin form sends each line's amounts and tax back with it.
			'items'    => http_build_query(
				array(
					'order_item_id'        => array( $item_id ),
					'order_item_tax_class' => array( $item_id => 'reduced-rate' ),
					'order_item_qty'       => array( $item_id => 1 ),
					'line_subtotal'        => array( $item_id => '10' ),
					'line_total'           => array( $item_id => '10' ),
					'line_subtotal_tax'    => array( $item_id => array( $this->rate_id => '0.6' ) ),
					'line_tax'             => array( $item_id => array( $this->rate_id => '0.6' ) ),
				)
			),
			'country'  => 'US',
			'state'    => 'CO',
			'postcode' => '80202',
			'city'     => 'Denver',
		);

		$saved_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Saved to restore after the simulated request.
		$_POST      = $post;
		add_filter( 'wp_doing_ajax', '__return_true' );

		try {
			wc_get_container()->get( Automattic\WooCommerce\Internal\Orders\TaxesController::class )->calc_line_taxes( $post );
		} finally {
			$_POST = $saved_post;
			remove_filter( 'wp_doing_ajax', '__return_true' );
		}

		return wc_get_order( $order->get_id() );
	}

	/**
	 * @testdox A Recalculate request for an order that can't be edited changes no tax, though the admin hides the button.
	 */
	public function test_admin_recalculate_request_keeps_tax_on_locked_order() {
		$fixture = $this->create_paid_order();

		$order = $this->admin_recalculate_with_class_change( $fixture['order'], $fixture['a'] );

		$this->assertSame( 'reduced-rate', $order->get_item( $fixture['a'] )->get_tax_class() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		// Also checks the tax line's label and rate: wc_save_order_items() runs
		// update_taxes(), which reloads them from the table, with its own rounding.
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertTrue( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox A Recalculate request for an order that can still be edited is left to WooCommerce, as it always was.
	 */
	public function test_admin_recalculate_request_recalculates_editable_order() {
		$fixture = $this->create_editable_order();

		$order = $this->admin_recalculate_with_class_change( $fixture['order'], $fixture['a'] );

		// The fixture's row is overwritten to 0% in the table, and the lookup finds TaxJar unreachable.
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_cart_tax(), 0.001 );
		$this->assertFalse( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * Give the store nexus in the destination state, so an address change is looked up.
	 *
	 * With the store's nexus only in Colorado, a move out of state is answered without
	 * asking TaxJar (no tax applies). A nexus address in the destination state makes the
	 * lookup run, and the mocked API client lets it fail.
	 *
	 * @param string $state    State.
	 * @param string $postcode ZIP.
	 * @param string $city     City.
	 * @return callable The filter callback, to remove afterwards.
	 */
	private function add_nexus_in( $state, $postcode, $city ) {
		$callback = static function () use ( $state, $postcode, $city ) {
			return array(
				'id'      => 'nexus-' . strtolower( $state ),
				'country' => 'US',
				'state'   => $state,
				'zip'     => $postcode,
				'city'    => $city,
				'street'  => '1 Nexus St',
			);
		};
		add_filter( 'woocommerce_taxjar_nexus_address', $callback );

		return $callback;
	}

	/**
	 * @testdox calculate_taxes() then calculate_totals( true ) after an address change keeps the recorded tax.
	 */
	public function test_taxes_then_totals_after_address_change_keeps_recorded_tax() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );
		$nexus   = $this->add_nexus_in( 'NY', '10001', 'New York' );

		// No rate row matches NY, so WC's own recalculation wipes the tax lines.
		$order->set_shipping_state( 'NY' );
		$order->set_shipping_postcode( '10001' );
		$order->set_shipping_city( 'New York' );
		$order->set_billing_state( 'NY' );
		$order->set_billing_postcode( '10001' );
		$order->set_billing_city( 'New York' );
		$order->save();

		// The first call snapshots the recorded tax; the second finds the lines already
		// wiped and must not throw that snapshot away.
		try {
			$order->calculate_taxes();
			$order->calculate_totals( true );
		} finally {
			remove_filter( 'woocommerce_taxjar_nexus_address', $nexus );
		}

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertCount( 1, $order->get_taxes(), 'the tax line survives' );
	}

	/**
	 * @testdox A paid shipping line added to an order whose only shipping was free gets the untaxed-item note.
	 */
	public function test_new_shipping_after_free_shipping_has_note() {
		$fixture = $this->create_placed_order();
		$order   = $fixture['order'];

		// Checkout stores a $0 shipping line with no taxes: WC_Shipping_Method::add_rate()
		// only calculates tax when the cost is above 0. That says nothing about whether
		// the order's shipping is taxable.
		$shipping = $order->get_item( $fixture['shipping'], false );
		$shipping->set_method_id( 'free_shipping' );
		$shipping->set_method_title( 'Free shipping' );
		$shipping->set_total( '0' );
		$shipping->set_taxes( false );
		$taxes = $order->get_taxes();
		reset( $taxes )->set_shipping_tax_total( 0 );
		$order->set_shipping_total( 0 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 31.80 );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertNotEmpty( $this->tax_notes( $order ), 'a note explains the untaxed shipping' );
	}

	/**
	 * @testdox calculate_taxes() then calculate_totals( true ) after a quantity change and an address move re-taxes at the recorded rate.
	 */
	public function test_taxes_then_totals_after_change_and_address_move_reapplies_recorded_rate() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$item = $order->get_item( $fixture['a'], false );
		$item->set_quantity( 2 );
		$item->set_subtotal( '20' );
		$item->set_total( '20' );
		$item->save();

		$order->set_shipping_state( 'MI' );
		$order->set_shipping_postcode( '49841' );
		$order->set_shipping_city( 'Gwinn' );
		$order->set_billing_state( 'MI' );
		$order->set_billing_postcode( '49841' );
		$order->set_billing_city( 'Gwinn' );
		$order->save();

		$nexus = $this->add_nexus_in( 'MI', '49841', 'Gwinn' );
		try {
			$order->calculate_taxes();
			$order->calculate_totals( true );
		} finally {
			remove_filter( 'woocommerce_taxjar_nexus_address', $nexus );
		}

		$order = wc_get_order( $order->get_id() );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * Move the fixture's addresses to Gwinn, MI, where the store is given nexus so TaxJar is
	 * asked (and, with the mocked client, fails).
	 *
	 * @return array REST body fields.
	 */
	private static function michigan_address() {
		$address = array(
			'country'   => 'US',
			'state'     => 'MI',
			'postcode'  => '49841',
			'city'      => 'Gwinn',
			'address_1' => '1 Main St',
		);

		return array(
			'billing'  => $address,
			'shipping' => $address,
		);
	}

	/**
	 * @testdox A failed lookup that also leaves a new item untaxed writes one note.
	 */
	public function test_failed_lookup_with_untaxed_new_item_writes_one_note() {
		$fixture = $this->create_placed_order();
		$nexus   = $this->add_nexus_in( 'MI', '49841', 'Gwinn' );

		try {
			$order = $this->rest_update(
				$fixture['order']->get_id(),
				array_merge(
					self::michigan_address(),
					array(
						'line_items' => array(
							array(
								'product_id' => $this->create_product( '10', 'reduced-rate' )->get_id(),
								'quantity'   => 1,
							),
						),
					)
				)
			);
		} finally {
			remove_filter( 'woocommerce_taxjar_nexus_address', $nexus );
		}

		// The order has no rate for Reduced rate: the new $10 item is untaxed.
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes, 'one note for one update' );
		$this->assertStringContainsString( 'could not be updated', $notes[0] );
		$this->assertStringContainsString( 'No tax was added', $notes[0] );
		$this->assertStringContainsString( 'no recorded tax rate', $notes[0], 'the reason for the saved-rates path' );
	}

	/**
	 * @testdox A failed lookup on an order that does not record its rates writes one note.
	 */
	public function test_failed_lookup_without_stored_rate_writes_one_note() {
		$fixture = $this->create_placed_order( array( 'rate_percent' => null ) );
		$nexus   = $this->add_nexus_in( 'MI', '49841', 'Gwinn' );

		try {
			$order = $this->rest_update(
				$fixture['order']->get_id(),
				array_merge(
					self::michigan_address(),
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
				)
			);
		} finally {
			remove_filter( 'woocommerce_taxjar_nexus_address', $nexus );
		}

		// The recorded tax is kept: 40 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 1.80, (float) $order->get_cart_tax(), 0.001 );
		$this->assertEqualsWithDelta( 0.30, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertEqualsWithDelta( 47.10, (float) $order->get_total(), 0.001 );
		$notes = $this->tax_notes( $order );
		$this->assertCount( 1, $notes, 'one note for one update' );
		$this->assertStringContainsString( 'does not record the tax rates', $notes[0] );
		$this->assertStringContainsString( 'could not be updated', $notes[0] );
	}

	/**
	 * @testdox Swapping a free shipping line for a paid one gets the untaxed-item note.
	 */
	public function test_free_shipping_swapped_for_paid_has_note() {
		$fixture = $this->create_placed_order();
		$order   = $fixture['order'];

		$shipping = $order->get_item( $fixture['shipping'], false );
		$shipping->set_method_id( 'free_shipping' );
		$shipping->set_method_title( 'Free shipping' );
		$shipping->set_total( '0' );
		$shipping->set_taxes( false );
		$taxes = $order->get_taxes();
		reset( $taxes )->set_shipping_tax_total( 0 );
		$order->set_shipping_total( 0 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 31.80 );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'        => $fixture['shipping'],
						'method_id' => null,
					),
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertCount( 1, $order->get_shipping_methods(), 'the free line was replaced' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertNotEmpty( $this->tax_notes( $order ), 'a note explains the untaxed shipping' );
	}

	/**
	 * @testdox Swapping a non-taxable shipping method for a paid taxable one gets the untaxed-item note.
	 */
	public function test_non_taxable_shipping_swapped_for_taxable_has_note() {
		$fixture = $this->create_placed_order();
		$order   = $fixture['order'];

		$zone = new WC_Shipping_Zone();
		$zone->save();
		$instance_id = $zone->add_shipping_method( 'flat_rate' );
		update_option(
			'woocommerce_flat_rate_' . $instance_id . '_settings',
			array(
				'tax_status' => 'none',
				'cost'       => '5',
			)
		);

		// A line whose method is not taxable is stored with no tax, whatever the address.
		$shipping = $order->get_item( $fixture['shipping'], false );
		$shipping->set_instance_id( $instance_id );
		$shipping->set_taxes( false );
		$taxes = $order->get_taxes();
		reset( $taxes )->set_shipping_tax_total( 0 );
		$order->set_shipping_tax( 0 );
		$order->set_total( 36.80 );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'        => $fixture['shipping'],
						'method_id' => null,
					),
					array(
						'method_id'    => 'flat_rate',
						'method_title' => 'Express',
						'total'        => '10.00',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.001 );
		$this->assertNotEmpty( $this->tax_notes( $order ), 'a note explains the untaxed shipping' );
	}

	// -------------------------------------------------------------------------
	// A paid order keeps the tax it was charged.
	//
	// Accounting fixes the tax at the earliest of payment, invoice and shipment.
	// WooCommerce records the payment date and, with order fulfillments, the date a
	// shipment was first fulfilled. Expected figures below follow from that rule: the
	// tax lines and each item's tax stay as charged, the order total moves by the item
	// amounts only, and "the tax as it is now" is the order's own rate on the new amounts.
	// -------------------------------------------------------------------------

	/**
	 * An order note as a merchant reads it: no HTML, entities decoded.
	 *
	 * @param string $note Note content.
	 * @return string
	 */
	private static function note_text( $note ) {
		return html_entity_decode( wp_strip_all_tags( $note ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * The notes written for edits of a locked order, as text.
	 *
	 * @param WC_Order $order Order.
	 * @return string[]
	 */
	private function locked_order_notes( WC_Order $order ) {
		return array_values( array_map( array( self::class, 'note_text' ), preg_grep( '/after its tax was charged/', $this->tax_notes( $order ) ) ) );
	}

	/**
	 * Edit line A of the fixture from 1 to 2 at $20, through the REST API.
	 *
	 * @param array $fixture See create_placed_order().
	 * @return WC_Order
	 */
	private function double_line_a( array $fixture ) {
		return $this->rest_update(
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
	}

	/**
	 * @testdox On a paid order, a quantity change keeps the tax charged, with the note accounting reviewed (the $6.89 example).
	 */
	public function test_paid_order_quantity_change_keeps_charged_tax_with_note() {
		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CO' );
		$order->set_shipping_postcode( '80202' );
		$order->set_shipping_city( 'Denver' );
		$product = $this->create_product( '6.50' );
		$product->set_name( 'T-shirt' );
		$product->save();
		$item_id = $order->add_product( $product, 1 );
		$order->get_item( $item_id, false )->set_taxes(
			array(
				'total'    => array( $this->rate_id => '0.39' ),
				'subtotal' => array( $this->rate_id => '0.39' ),
			)
		);
		$tax_line = new WC_Order_Item_Tax();
		$tax_line->set_rate_id( $this->rate_id );
		$tax_line->set_rate_code( 'US-CO-CO TAX-1' );
		$tax_line->set_label( 'CO Tax' );
		$tax_line->set_rate_percent( 6.0 );
		$tax_line->set_tax_total( '0.39' );
		$order->add_item( $tax_line );
		$order->set_cart_tax( '0.39' );
		$order->set_total( 6.89 );
		$order->set_status( 'processing' );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->rest_update(
			$order->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $item_id,
						'quantity' => 2,
						'subtotal' => '13.00',
						'total'    => '13.00',
					),
				),
			)
		);

		$this->assertEqualsWithDelta( 0.39, $this->item_tax( $order, $item_id ), 0.001 );
		$this->assert_order_tax( $order, 0.39, 0.0, 13.39 );

		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$lines = explode( "\n", $notes[0] );
		$this->assertSame( 'This order changed after its tax was charged.', $lines[0] );
		$this->assertMatchesRegularExpression( '/^Changed: .+, by .+\.$/', $lines[1], 'when, and by the admin user' );
		$this->assertSame(
			array(
				'Change: T-shirt quantity changed from 1 to 2.',
				'Items: from $6.50 to $13.00. Order total: from $6.89 to $13.39.',
				'Tax charged: $0.39 (6% on $6.50). The tax stays at $0.39.',
				'Tax for the order as it is now: $0.78 (6% on $13.00). Difference: $0.39.',
				'No payment or refund was made for this change.',
				'Next step: if the customer must pay more, create a new order for the difference. If the customer must get money back, issue a refund. If you sent the customer an invoice for this order, also issue a corrective document that refers to that invoice.',
			),
			array_slice( $lines, 2 )
		);
		$this->assertStringNotContainsString( 'already paid', $notes[0] );

		$note = wc_get_order_notes( array( 'order_id' => $order->get_id() ) )[0];
		$this->assertFalse( $note->customer_note, 'the note is private' );
	}

	/**
	 * @testdox On a paid order, removing a line keeps the tax charged, and the note shows the lower tax the order has now.
	 */
	public function test_paid_order_removed_line_keeps_charged_tax_with_note() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['b'],
						'quantity' => 0,
					),
				),
			)
		);

		$this->assertFalse( $order->get_item( $fixture['b'] ), 'B is removed' );
		// The $2.10 charged stays; 10 + 5 + 1.80 + 0.30.
		$this->assert_order_tax( $order, 1.80, 0.30, 17.10 );

		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Change: Dummy Product removed.', $notes[0] );
		$this->assertStringContainsString( 'Items: from $30.00 to $10.00. Order total: from $37.10 to $17.10.', $notes[0] );
		// A $10, B $20 and shipping $5, all at 6%.
		$this->assertStringContainsString( 'Tax charged: $2.10 (6% on $35.00). The tax stays at $2.10.', $notes[0] );
		// A $10 and shipping $5 at 6%.
		$this->assertStringContainsString( 'Tax for the order as it is now: $0.90 (6% on $15.00). Difference: -$1.20.', $notes[0] );
		$this->assertStringContainsString( 'If the customer must get money back, issue a refund.', $notes[0] );
	}

	/**
	 * @testdox On a paid order, a line switched to another product keeps its tax, and the note names both products.
	 */
	public function test_paid_order_product_switch_keeps_charged_tax() {
		$fixture = $this->create_paid_order();
		$hoodie  = $this->create_product( '10', 'reduced-rate' );
		$hoodie->set_name( 'Hoodie' );
		$hoodie->save();

		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'         => $fixture['a'],
						'product_id' => $hoodie->get_id(),
						'quantity'   => 1,
					),
				),
			)
		);

		$this->assertSame( $hoodie->get_id(), $order->get_item( $fixture['a'] )->get_product_id() );
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001, 'taxed as the line it replaced was' );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Dummy Product changed to Hoodie.', $notes[0] );
		$this->assertStringContainsString( 'Order total: $37.10 (no change).', $notes[0] );
	}

	/**
	 * @testdox A paid order moved back to on hold stays locked: an edit keeps the tax charged.
	 */
	public function test_paid_order_moved_back_to_on_hold_keeps_charged_tax() {
		$fixture = $this->create_paid_order();
		$fixture['order']->set_status( 'on-hold' );
		$fixture['order']->save();
		$this->assertTrue( $fixture['order']->is_editable() );

		$order = $this->double_line_a( $fixture );

		// 40 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$this->assertCount( 1, $this->locked_order_notes( $order ) );
	}

	/**
	 * @testdox A cancelled order that was never paid is not locked: an edit moves the tax with the order.
	 */
	public function test_cancelled_unpaid_order_follows_the_order() {
		$fixture = $this->create_placed_order( array( 'status' => 'cancelled' ) );
		$this->assertNull( $fixture['order']->get_date_paid() );
		$this->assertFalse( $fixture['order']->is_editable() );

		$order = $this->double_line_a( $fixture );

		// 6% of $20 = $1.20; 40 + 5 + 2.40 + 0.30.
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertSame( array(), $this->locked_order_notes( $order ) );
	}

	/**
	 * Turn on WooCommerce's order fulfillments and add a fulfillment for line A.
	 *
	 * @param WC_Order $order  Order.
	 * @param int      $a      Line A's id.
	 * @param string   $status 'fulfilled', or 'unfulfilled' for a draft shipment.
	 */
	private function add_fulfillment( WC_Order $order, $a, $status ) {
		if ( ! class_exists( Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment::class ) ) {
			$this->markTestSkipped( 'Order fulfillments need WooCommerce 10.2 or later.' );
		}

		update_option( 'woocommerce_feature_fulfillments_enabled', 'yes' );
		wc_get_container()->get( Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentsController::class )->initialize_fulfillments();

		$fulfillment = new Automattic\WooCommerce\Admin\Features\Fulfillments\Fulfillment();
		$fulfillment->set_props(
			array(
				'entity_type' => WC_Order::class,
				'entity_id'   => (string) $order->get_id(),
				'status'      => $status,
			)
		);
		$fulfillment->set_items(
			array(
				array(
					'item_id' => $a,
					'qty'     => 1,
				),
			)
		);
		$fulfillment->save();
		$this->forget_created_in_request();
	}

	/**
	 * @testdox An unpaid order with a shipment marked fulfilled is locked: an edit keeps the tax charged.
	 */
	public function test_unpaid_order_with_fulfilled_shipment_keeps_charged_tax() {
		$fixture = $this->create_placed_order();
		try {
			$this->add_fulfillment( $fixture['order'], $fixture['a'], 'fulfilled' );
			$this->assertNull( wc_get_order( $fixture['order']->get_id() )->get_date_paid() );

			$order = $this->double_line_a( $fixture );
		} finally {
			update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		}

		// A partial shipment locks the whole order: 40 + 5 + 1.80 + 0.30.
		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$this->assertCount( 1, $this->locked_order_notes( $order ) );
	}

	/**
	 * @testdox An unpaid order with only a draft shipment is not locked: an edit moves the tax with the order.
	 */
	public function test_unpaid_order_with_draft_shipment_follows_the_order() {
		$fixture = $this->create_placed_order();
		try {
			$this->add_fulfillment( $fixture['order'], $fixture['a'], 'unfulfilled' );

			$order = $this->double_line_a( $fixture );
		} finally {
			update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );
		}

		// 6% of $20 = $1.20; 40 + 5 + 2.40 + 0.30.
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertSame( array(), $this->locked_order_notes( $order ) );
	}

	/**
	 * @testdox With order fulfillments turned off, a fulfilled shipment saved earlier does not lock an unpaid order.
	 */
	public function test_fulfilled_shipment_is_ignored_when_fulfillments_are_off() {
		$fixture = $this->create_placed_order();
		$this->add_fulfillment( $fixture['order'], $fixture['a'], 'fulfilled' );
		update_option( 'woocommerce_feature_fulfillments_enabled', 'no' );

		$order = $this->double_line_a( $fixture );

		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
	}

	/**
	 * @testdox The note on a paid order names the user and the WooCommerce app when the edit comes from the app.
	 */
	public function test_paid_order_note_names_user_and_app() {
		$fixture = $this->create_paid_order();
		wp_set_current_user(
			self::factory()->user->create(
				array(
					'role'         => 'administrator',
					'display_name' => 'Jane Smith',
				)
			)
		);

		$user_agent                 = $_SERVER['HTTP_USER_AGENT'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 wc-ios/21.3';
		try {
			$order = $this->double_line_a( $fixture );
		} finally {
			if ( null === $user_agent ) {
				unset( $_SERVER['HTTP_USER_AGENT'] );
			} else {
				$_SERVER['HTTP_USER_AGENT'] = $user_agent;
			}
		}

		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertMatchesRegularExpression( '/^Changed: .+, by Jane Smith \(WooCommerce app\)\.$/m', $notes[0] );
	}

	/**
	 * @testdox On a paid order whose items are in two tax classes, the note gives the tax charged without a single rate.
	 */
	public function test_paid_order_note_leaves_out_rate_for_mixed_rates() {
		$fixture = $this->create_paid_order();
		$order   = wc_get_order( $fixture['order']->get_id() );
		// B was taxed at another rate as well, as a second tax class would be.
		$second = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '0.0000',
				'tax_rate_name'     => 'Other',
				'tax_rate_priority' => 2,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 1,
				'tax_rate_class'    => '',
			)
		);
		$taxes  = array(
			'total'    => array(
				$this->rate_id => '1.2',
				$second        => '0.4',
			),
			'subtotal' => array(
				$this->rate_id => '1.2',
				$second        => '0.4',
			),
		);
		$order->get_item( $fixture['b'], false )->set_taxes( $taxes );
		$order->get_item( $fixture['b'], false )->save();
		$tax_line = new WC_Order_Item_Tax();
		$tax_line->set_rate_id( $second );
		$tax_line->set_label( 'Other' );
		$tax_line->set_rate_percent( 2.0 );
		$tax_line->set_tax_total( '0.4' );
		$order->add_item( $tax_line );
		$order->set_cart_tax( '2.2' );
		$order->set_total( 37.50 );
		$order->save();
		$this->forget_created_in_request();

		$order = $this->double_line_a( $fixture );

		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Tax charged: $2.50. The tax stays at $2.50.', $notes[0] );
	}

	/**
	 * @testdox On a paid order, saving again with nothing changed writes no note.
	 */
	public function test_paid_order_resave_writes_no_note() {
		$fixture = $this->create_paid_order();

		$order = $this->rest_update( $fixture['order']->get_id(), array( 'customer_note' => 'Leave at the door' ) );
		$order->calculate_totals( true );
		$order = wc_get_order( $order->get_id() );

		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}

	// -------------------------------------------------------------------------
	// Review of the paid-order lock: subscriptions, Update, Add item, untaxed orders.

	/**
	 * Load an order as a subscription from now on, as WooCommerce Subscriptions does.
	 *
	 * @param int $order_id Order id.
	 */
	private function load_as_subscription( $order_id ) {
		require_once __DIR__ . '/class-wc-subscription-test-double.php';
		require_once __DIR__ . '/wcs-functions-test-double.php';
		add_filter(
			'woocommerce_order_class',
			static function ( $classname, $order_type = null, $id = null ) use ( $order_id ) {
				return (int) $id === (int) $order_id ? 'WC_Subscription' : $classname;
			},
			// After WooCommerce Admin's own filter, which gives its Order override class.
			PHP_INT_MAX,
			3
		);
		// An order already in WooCommerce's order cache is returned without the filter.
		if ( class_exists( Automattic\WooCommerce\Caches\OrderCache::class ) ) {
			wc_get_container()->get( Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order_id );
		}
		$this->assertInstanceOf( 'WC_Subscription', wc_get_order( $order_id ) );
	}

	/**
	 * Save an order's items as the order screen's Update button does: wc_save_order_items()
	 * with the form's fields, outside AJAX, with no tax calculation.
	 *
	 * @param WC_Order $order Order.
	 * @param array    $lines Line item and fee fields, keyed by item id: qty, total, tax.
	 * @return WC_Order The order read back.
	 */
	private function admin_update( WC_Order $order, array $lines ) {
		$items = array();
		foreach ( $lines as $item_id => $line ) {
			$items['order_item_id'][]                  = $item_id;
			$items['order_item_qty'][ $item_id ]       = (string) $line['qty'];
			$items['order_item_tax_class'][ $item_id ] = '';
			$items['line_subtotal'][ $item_id ]        = $line['total'];
			$items['line_total'][ $item_id ]           = $line['total'];
			$items['line_subtotal_tax'][ $item_id ]    = array( $this->rate_id => $line['tax'] );
			$items['line_tax'][ $item_id ]             = array( $this->rate_id => $line['tax'] );
		}

		wc_save_order_items( $order->get_id(), $items );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Press the order screen's Recalculate button: the wp_ajax_woocommerce_calc_line_taxes
	 * action, with WooCommerce's handler (nonce, HTML, wp_die) swapped for the call it makes.
	 *
	 * @param WC_Order $order Order.
	 * @param array    $items Optional: the order form's fields the button posts, see order_screen_form().
	 * @return WC_Order The order read back.
	 */
	private function press_recalculate( WC_Order $order, array $items = array() ) {
		global $wp_filter;

		$post = array(
			'order_id' => $order->get_id(),
			'items'    => http_build_query( $items ),
			'country'  => 'US',
			'state'    => 'CO',
			'postcode' => '80202',
			'city'     => 'Denver',
		);

		$saved_hook = $wp_filter['wp_ajax_woocommerce_calc_line_taxes'] ?? null;
		remove_all_actions( 'wp_ajax_woocommerce_calc_line_taxes' );
		add_action(
			'wp_ajax_woocommerce_calc_line_taxes',
			static function () use ( $post ) {
				wc_get_container()->get( Automattic\WooCommerce\Internal\Orders\TaxesController::class )->calc_line_taxes( $post );
			}
		);
		add_filter( 'wp_doing_ajax', '__return_true' );
		// The button posts the address too, which a tax lookup reads from $_POST.
		$saved_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Saved to restore after the simulated request.
		$_POST      = $post;

		try {
			do_action( 'wp_ajax_woocommerce_calc_line_taxes' );
		} finally {
			$_POST = $saved_post;
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_all_actions( 'wp_ajax_woocommerce_calc_line_taxes' );
			if ( null !== $saved_hook ) {
				$wp_filter['wp_ajax_woocommerce_calc_line_taxes'] = $saved_hook; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the hook this helper swapped.
			}
		}

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Assert the order kept the tax on the items it was charged, no shipping tax, and its
	 * one tax line, as create_paid_order_with_tax_line() made them.
	 *
	 * @param WC_Order $order        Order.
	 * @param float    $cart_tax     Tax on the items.
	 * @param float    $rate_percent Rate the tax line records.
	 * @param float    $total        Expected order total.
	 */
	private function assert_tax_line_kept( WC_Order $order, $cart_tax, $rate_percent, $total ) {
		$this->assertEqualsWithDelta( $cart_tax, (float) $order->get_cart_tax(), 0.00001, 'cart tax' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_shipping_tax(), 0.00001, 'shipping tax' );
		$this->assertEqualsWithDelta( $total, (float) $order->get_total(), 0.001, 'order total' );

		$taxes = $order->get_taxes();
		$this->assertCount( 1, $taxes, 'the tax line is kept' );
		$line = reset( $taxes );
		$this->assertEqualsWithDelta( $rate_percent, (float) $line->get_rate_percent(), 0.0001, 'at the rate it was sold at' );
		$this->assertEqualsWithDelta( $cart_tax, (float) $line->get_tax_total(), 0.00001, 'tax line cart tax' );
		$this->assertEqualsWithDelta( 0.0, (float) $line->get_shipping_tax_total(), 0.00001, 'tax line shipping tax' );
	}

	/**
	 * A paid order with one $10 line, $5 shipping and one tax line with the given tax, as
	 * an exempt order with a $0.00 line at 0% has. The rate table gives 6% now, so a
	 * recalculation that does not keep the tax adds it.
	 *
	 * @param string $cart_tax     Tax on the line.
	 * @param string $shipping_tax Tax on shipping.
	 * @param float  $rate_percent Rate the tax line records.
	 * @return WC_Order
	 */
	private function create_paid_order_with_tax_line( $cart_tax, $shipping_tax, $rate_percent = 6.0 ) {
		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );
		$item_id = $order->add_product( $this->create_product( '10' ), 1 );
		$order->get_item( $item_id, false )->set_taxes(
			array(
				'total'    => array( $this->rate_id => $cart_tax ),
				'subtotal' => array( $this->rate_id => $cart_tax ),
			)
		);

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_method_id( 'flat_rate' );
		$shipping->set_total( '5' );
		$shipping->set_taxes( array( 'total' => array( $this->rate_id => $shipping_tax ) ) );
		$order->add_item( $shipping );

		$tax_line = new WC_Order_Item_Tax();
		$tax_line->set_rate_id( $this->rate_id );
		$tax_line->set_rate_code( 'US-CO-CO TAX-1' );
		$tax_line->set_label( 'CO Tax' );
		$tax_line->set_rate_percent( $rate_percent );
		$tax_line->set_compound( false );
		$tax_line->set_tax_total( $cart_tax );
		$tax_line->set_shipping_tax_total( $shipping_tax );
		$order->add_item( $tax_line );

		$order->set_shipping_total( '5' );
		$order->set_cart_tax( $cart_tax );
		$order->set_shipping_tax( $shipping_tax );
		$order->set_total( 15 + (float) $cart_tax + (float) $shipping_tax );
		$order->set_status( 'processing' );
		$order->save();
		$this->forget_created_in_request();
		$this->assertNotNull( $order->get_date_paid(), 'the order has a payment date' );

		WC_Tax::_update_tax_rate( $this->rate_id, array( 'tax_rate' => '6.0000' ) );
		update_option( 'woocommerce_store_postcode', '80202' );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * A paid order with one $10 line and no tax at all, as an order made in the admin and
	 * saved as Processing before Recalculate was pressed is.
	 *
	 * @return WC_Order
	 */
	private function create_paid_untaxed_order() {
		$order = wc_create_order();
		$order->set_billing_country( 'US' );
		$order->set_billing_state( 'CO' );
		$order->set_billing_postcode( '80202' );
		$order->set_billing_city( 'Denver' );
		$order->add_product( $this->create_product( '10' ), 1 );
		$order->set_total( 10 );
		$order->set_status( 'processing' );
		$order->save();
		$this->forget_created_in_request();
		$this->assertNotNull( $order->get_date_paid(), 'the order has a payment date' );
		$this->assertSame( array(), $order->get_taxes(), 'the order has no tax' );

		// WooCommerce's rate table gives Denver 6%, as a lookup would have left it.
		WC_Tax::_update_tax_rate( $this->rate_id, array( 'tax_rate' => '6.0000' ) );

		// A store ZIP code, without which a lookup stops before it asks the tax service.
		update_option( 'woocommerce_store_postcode', '80202' );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * @testdox A subscription with a payment date is not locked: its tax follows a quantity change, so its renewals charge the new tax.
	 */
	public function test_paid_subscription_tax_follows_its_edits() {
		$fixture = $this->create_paid_order();
		$this->load_as_subscription( $fixture['order']->get_id() );

		// Recalculated in code, as Subscriptions' own screens and routes do. The orders
		// REST route builds a WC_Order whatever the class filter says.
		$order = wc_get_order( $fixture['order']->get_id() );
		$item  = $order->get_item( $fixture['a'] );
		$item->set_quantity( 2 );
		$item->set_subtotal( '20' );
		$item->set_total( '20' );
		$item->save();
		$order->calculate_totals( true );
		$order = wc_get_order( $order->get_id() );

		// As on an unpaid order: A is now $20 at the order's 6%.
		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		$this->assert_order_tax( $order, 2.40, 0.30, 47.70 );
		$this->assertFalse( $this->has_kept_tax_note( $order ) );
	}

	/**
	 * @testdox On a paid order, Update after a line was removed keeps the tax charged, though the lines left carry less.
	 */
	public function test_paid_order_update_after_removed_line_keeps_charged_tax() {
		$fixture = $this->create_paid_order();
		$this->rest_update(
			$fixture['order']->get_id(),
			array(
				'line_items' => array(
					array(
						'id'       => $fixture['b'],
						'quantity' => 0,
					),
				),
			)
		);
		$this->start_new_request();

		// The form shows A at its $0.60; the lines left carry $0.60 + $0.30 of the $2.10.
		$order = $this->admin_update(
			wc_get_order( $fixture['order']->get_id() ),
			array(
				$fixture['a'] => array(
					'qty'   => 1,
					'total' => '10',
					'tax'   => '0.6',
				),
			)
		);

		$this->assert_order_tax( $order, 1.80, 0.30, 17.10 );
		$this->assertCount( 1, $this->locked_order_notes( $order ), 'only the removal has a note: Update changed nothing' );
	}

	/**
	 * @testdox On a paid order, Update with a new quantity keeps the tax charged, though the form scaled the line's tax, with a note.
	 */
	public function test_paid_order_update_with_new_quantity_keeps_charged_tax() {
		$fixture = $this->create_paid_order();

		// The order screen doubles A's tax field with its quantity.
		$order = $this->admin_update(
			$fixture['order'],
			array(
				$fixture['a'] => array(
					'qty'   => 2,
					'total' => '20',
					'tax'   => '1.2',
				),
			)
		);

		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001 );
		// 20 + 20 + 5 + the $2.10 charged.
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Change: Dummy Product quantity changed from 1 to 2.', $notes[0] );
	}

	/**
	 * @testdox On a paid order, a line added with the order screen's Add item(s) gets no tax and is in the note of the next Recalculate.
	 */
	public function test_paid_order_line_added_on_order_screen_is_noted_at_recalculate() {
		$fixture = $this->create_paid_order();

		// What the Add item(s) button does: save the line, recalculate nothing.
		$order  = wc_get_order( $fixture['order']->get_id() );
		$new_id = $order->add_product( $this->create_product( '50' ), 1, array( 'order' => $order ) );
		do_action( 'woocommerce_ajax_order_items_added', array( $new_id => $order->get_item( $new_id ) ), $order );
		$this->start_new_request();

		$order = $this->press_recalculate( wc_get_order( $fixture['order']->get_id() ) );

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $new_id ), 0.001, 'the added line gets no tax' );
		// 30 + 50 + 5 + the $2.10 charged.
		$this->assert_order_tax( $order, 1.80, 0.30, 87.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Change: Dummy Product added.', $notes[0] );
		$this->assertStringContainsString( 'Items: from $30.00 to $80.00.', $notes[0] );

		$this->start_new_request();
		$order = $this->press_recalculate( $order );
		$this->assert_order_tax( $order, 1.80, 0.30, 87.10 );
		$this->assertCount( 1, $this->locked_order_notes( $order ), 'the next Recalculate has nothing to note' );
	}

	/**
	 * @testdox A paid order with no tax stays at no tax when Recalculate is pressed, with a note on how to charge it.
	 */
	public function test_recalculate_keeps_paid_order_without_tax_at_no_tax() {
		$order = $this->press_recalculate( $this->create_paid_untaxed_order() );

		// WooCommerce's 6% row would give $0.60: the tax stays at none.
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 10.0, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $order->get_taxes(), 'no tax lines' );
		$this->assertSame( 0, $this->tax_service_requests, 'the tax service is not asked' );

		$notes = array_map( array( self::class, 'note_text' ), $this->tax_notes( $order ) );
		$this->assertCount( 1, $notes );
		$this->assertStringStartsWith( "No tax was charged on this order. The tax stays at $0.00.\nChanged: ", $notes[0] );
		$this->assertStringContainsString( "\nThe tax for the order as it is now was not calculated.\nNo payment or refund was made for this change.\n", $notes[0] );
		$this->assertStringEndsWith( 'Next step: if tax must be charged, refund this order and create a new order with the same items. The new order gets its own tax. If you sent the customer an invoice for this order, also issue a corrective document that refers to that invoice.', $notes[0] );
	}

	/**
	 * @testdox Recalculate after a quantity change on a paid order with no tax keeps no tax and writes the edited-order note.
	 */
	public function test_recalculate_with_a_change_keeps_paid_order_without_tax_at_no_tax() {
		$order   = $this->create_paid_untaxed_order();
		$item_id = current( $order->get_items() )->get_id();

		$order = $this->press_recalculate(
			$order,
			$this->order_screen_form(
				$order,
				array(
					$item_id => array(
						'qty'   => 2,
						'total' => '20',
					),
				)
			)
		);

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 20.0, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $order->get_taxes(), 'no tax lines' );
		$this->assertSame( 0, $this->tax_service_requests, 'the tax service is not asked' );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'quantity changed from 1 to 2.', $notes[0] );
		$this->assertStringContainsString( 'The tax stays at $0.00.', $notes[0] );
		$this->assertCount( 1, $this->tax_notes( $order ), 'only that note' );
	}

	/**
	 * @testdox Recalculate with no change on a paid order with tax keeps its tax and writes no note.
	 */
	public function test_recalculate_without_change_keeps_paid_order_tax_without_note() {
		$fixture = $this->create_paid_order();
		$this->start_new_request();
		$order = wc_get_order( $fixture['order']->get_id() );
		$notes = $this->tax_notes( $order );

		$order = $this->press_recalculate( $order, $this->order_screen_form( $order ) );

		// 30 + 5 + the $2.10 charged.
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( $notes, $this->tax_notes( $order ), 'no new note' );
	}

	/**
	 * Tax lines whose tax is $0.00 as the order shows it, so the order was charged no tax.
	 *
	 * @return array<string, array{0: string, 1: float}> Tax on the items and the rate the tax line records.
	 */
	public function provide_zero_tax_lines() {
		return array(
			'all $0.00 at 0%'                        => array( '0', 0.0 ),
			// Tax rounded at subtotal keeps the unrounded sum on the order: $0.07 at 6%.
			'under half a cent, rounded at subtotal' => array( '0.0042', 6.0 ),
		);
	}

	/**
	 * @testdox A paid order whose tax lines come to $0.00 stays at no tax when Recalculate is pressed, with the note on how to charge it.
	 * @dataProvider provide_zero_tax_lines
	 *
	 * @param string $cart_tax     Tax on the items.
	 * @param float  $rate_percent Rate the tax line records.
	 */
	public function test_recalculate_keeps_paid_order_with_zero_tax_lines_at_no_tax( $cart_tax, $rate_percent ) {
		$order = $this->press_recalculate( $this->create_paid_order_with_tax_line( $cart_tax, '0', $rate_percent ) );

		// WooCommerce's 6% row would give $0.60 on the line and $0.30 on shipping.
		$this->assert_tax_line_kept( $order, (float) $cart_tax, $rate_percent, 15.0 );
		$this->assertSame( 0, $this->tax_service_requests, 'the tax service is not asked' );

		$notes = array_map( array( self::class, 'note_text' ), $this->tax_notes( $order ) );
		$this->assertCount( 1, $notes );
		$this->assertStringStartsWith( "No tax was charged on this order. The tax stays at $0.00.\nChanged: ", $notes[0] );
	}

	/**
	 * Tax lines whose tax is not $0.00, so the order was charged tax.
	 *
	 * @return array<string, array{0: string, 1: string}> Cart tax and shipping tax of the order.
	 */
	public function provide_charged_tax_lines() {
		return array(
			'one cent on the items'                   => array( '0.01', '0' ),
			'half a cent on the items'                => array( '0.005', '0' ),
			'tax on shipping only'                    => array( '0', '0.3' ),
			// Each rounds to $0.00 alone; together they are the cent in the order total.
			'under half a cent on items and shipping' => array( '0.003', '0.003' ),
		);
	}

	/**
	 * @testdox Recalculate with no change on a paid order charged a little tax keeps it and writes no note.
	 * @dataProvider provide_charged_tax_lines
	 *
	 * @param string $cart_tax     Tax on the items.
	 * @param string $shipping_tax Tax on shipping.
	 */
	public function test_recalculate_without_change_keeps_small_charged_tax_without_note( $cart_tax, $shipping_tax ) {
		$order = $this->press_recalculate( $this->create_paid_order_with_tax_line( $cart_tax, $shipping_tax ) );

		// The order total is in whole cents.
		$this->assert_order_tax( $order, (float) $cart_tax, (float) $shipping_tax, round( 15.0 + (float) $cart_tax + (float) $shipping_tax, 2 ) );
		$this->assertSame( array(), $this->tax_notes( $order ), 'no note' );
	}

	/**
	 * @testdox Recalculate after a quantity change on a paid order whose tax lines are all $0.00 keeps no tax and writes only the edited-order note.
	 */
	public function test_recalculate_with_a_change_keeps_paid_order_with_zero_tax_lines_at_no_tax() {
		$order   = $this->create_paid_order_with_tax_line( '0', '0', 0.0 );
		$item_id = current( $order->get_items() )->get_id();

		$order = $this->press_recalculate(
			$order,
			$this->order_screen_form(
				$order,
				array(
					$item_id => array(
						'qty'   => 2,
						'total' => '20',
					),
				)
			)
		);

		$this->assert_tax_line_kept( $order, 0.0, 0.0, 25.0 );
		$this->assertSame( 0, $this->tax_service_requests, 'the tax service is not asked' );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'quantity changed from 1 to 2.', $notes[0] );
		$this->assertStringContainsString( 'The tax stays at $0.00.', $notes[0] );
		$this->assertCount( 1, $this->tax_notes( $order ), 'only that note' );
	}

	/**
	 * @testdox A paid order with no tax stays at no tax on any other tax calculation from the order screen.
	 */
	public function test_other_admin_calculation_keeps_paid_order_without_tax_untaxed() {
		$order = $this->create_paid_untaxed_order();

		// As the order screen's Add fee does: calculate_taxes() over AJAX, no Recalculate.
		add_filter( 'wp_doing_ajax', '__return_true' );
		try {
			$order->calculate_taxes();
			$order->calculate_totals( false );
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
		}

		$order = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 10.0, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $this->tax_notes( $order ), 'only Recalculate writes the note' );
	}

	// -------------------------------------------------------------------------
	// Second review of the paid-order lock: the order screen's form, Add item(s) and
	// delete, typed tax, the address pencil, and the note's figures.

	/**
	 * The order screen's item fields as the browser posts them (html-order-item.php,
	 * html-order-shipping.php): every line has a tax field for each tax rate on the order,
	 * holding the tax the line carries under that rate, or nothing.
	 *
	 * @param WC_Order $order Order.
	 * @param array    $edits Optional changes, keyed by item id: 'qty', 'total' and 'tax' (for every rate).
	 * @return array
	 */
	private function order_screen_form( WC_Order $order, array $edits = array() ) {
		$rate_ids = array();
		foreach ( $order->get_taxes() as $tax_line ) {
			$rate_ids[] = (int) $tax_line->get_rate_id();
		}

		$form = array();
		foreach ( $order->get_items( array( 'line_item', 'fee' ) ) as $item_id => $item ) {
			$edit  = $edits[ $item_id ] ?? array();
			$taxes = $item->get_taxes();

			$form['order_item_id'][]                  = $item_id;
			$form['order_item_name'][ $item_id ]      = $item->get_name();
			$form['order_item_qty'][ $item_id ]       = (string) ( $edit['qty'] ?? $item->get_quantity() );
			$form['order_item_tax_class'][ $item_id ] = $item->get_tax_class();
			$form['line_subtotal'][ $item_id ]        = (string) ( $edit['total'] ?? $item->get_subtotal() );
			$form['line_total'][ $item_id ]           = (string) ( $edit['total'] ?? $item->get_total() );
			foreach ( $rate_ids as $rate_id ) {
				$form['line_tax'][ $item_id ][ $rate_id ]          = (string) ( $edit['tax'] ?? ( $taxes['total'][ $rate_id ] ?? '' ) );
				$form['line_subtotal_tax'][ $item_id ][ $rate_id ] = (string) ( $edit['tax'] ?? ( $taxes['subtotal'][ $rate_id ] ?? '' ) );
			}
		}

		foreach ( $order->get_shipping_methods() as $item_id => $item ) {
			$taxes = $item->get_taxes();

			$form['shipping_method_id'][]              = $item_id;
			$form['shipping_method'][ $item_id ]       = $item->get_method_id();
			$form['shipping_method_title'][ $item_id ] = $item->get_method_title();
			$form['shipping_cost'][ $item_id ]         = (string) $item->get_total();
			foreach ( $rate_ids as $rate_id ) {
				$form['shipping_taxes'][ $item_id ][ $rate_id ] = (string) ( $taxes['total'][ $rate_id ] ?? '' );
			}
		}

		return $form;
	}

	/**
	 * Press the order screen's Add item(s) button: what WC_AJAX::maybe_add_order_item()
	 * does to the order. It saves the line and recalculates nothing.
	 *
	 * @param WC_Order   $order   Order.
	 * @param WC_Product $product Product to add.
	 * @return int The new line's id.
	 */
	private function add_item_on_order_screen( WC_Order $order, WC_Product $product ) {
		$order   = wc_get_order( $order->get_id() );
		$item_id = $order->add_product( $product, 1, array( 'order' => $order ) );
		do_action( 'woocommerce_ajax_order_items_added', array( $item_id => $order->get_item( $item_id ) ), $order );

		return $item_id;
	}

	/**
	 * Press a line's delete icon on the order screen: the wp_ajax_woocommerce_remove_order_item
	 * action, with WooCommerce's handler (nonce, stock, HTML) swapped for what
	 * WC_AJAX::remove_order_item() does to the order. When lines are being edited, the
	 * form's fields are saved first.
	 *
	 * @param WC_Order $order   Order.
	 * @param int      $item_id The line to delete.
	 * @param array    $items   Optional: the form's fields, posted while lines are being edited.
	 * @return WC_Order The order read back.
	 */
	private function press_delete_icon( WC_Order $order, $item_id, array $items = array() ) {
		global $wp_filter;

		$order_id   = $order->get_id();
		$saved_hook = $wp_filter['wp_ajax_woocommerce_remove_order_item'] ?? null;
		remove_all_actions( 'wp_ajax_woocommerce_remove_order_item' );
		add_action(
			'wp_ajax_woocommerce_remove_order_item',
			static function () use ( $order_id, $item_id, $items ) {
				if ( $items ) {
					wc_save_order_items( $order_id, $items );
				}
				wc_delete_order_item( $item_id );

				$order = wc_get_order( $order_id );
				$order->calculate_taxes(
					array(
						'country'  => 'US',
						'state'    => 'CO',
						'postcode' => '80202',
						'city'     => 'DENVER',
					)
				);
				$order->calculate_totals( false );
			}
		);
		add_filter( 'wp_doing_ajax', '__return_true' );

		try {
			do_action( 'wp_ajax_woocommerce_remove_order_item' );
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_all_actions( 'wp_ajax_woocommerce_remove_order_item' );
			if ( null !== $saved_hook ) {
				$wp_filter['wp_ajax_woocommerce_remove_order_item'] = $saved_hook; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the hook this helper swapped.
			}
		}

		return wc_get_order( $order_id );
	}

	/**
	 * Press the order screen's Update button: woocommerce_process_shop_order_meta, with
	 * WooCommerce's two order meta boxes at their priorities. The items box saves the
	 * form's item fields (priority 10); the order data box then saves what the address
	 * pencil and the status field changed, and recalculates nothing (priority 40).
	 *
	 * @param WC_Order $order Order.
	 * @param array    $items The form's item fields, see order_screen_form().
	 * @param array    $props Order props the order data box saves, such as 'shipping_city' or 'status'.
	 * @return WC_Order The order read back.
	 */
	private function press_update( WC_Order $order, array $items, array $props = array() ) {
		$order_id   = $order->get_id();
		$save_items = static function ( $id ) use ( $items ) {
			wc_save_order_items( $id, $items );
		};
		$save_order = static function ( $id ) use ( $props ) {
			$order = wc_get_order( $id );
			$order->set_props( $props );
			$order->save();
		};

		add_action( 'woocommerce_process_shop_order_meta', $save_items, 10 );
		add_action( 'woocommerce_process_shop_order_meta', $save_order, 40 );
		try {
			do_action( 'woocommerce_process_shop_order_meta', $order_id, $order );
		} finally {
			remove_action( 'woocommerce_process_shop_order_meta', $save_items, 10 );
			remove_action( 'woocommerce_process_shop_order_meta', $save_order, 40 );
		}

		return wc_get_order( $order_id );
	}

	/**
	 * @testdox The mark on a line added with Add item(s) is hidden on the order screen, so it can't be seen, edited or deleted there.
	 */
	public function test_mark_on_line_added_to_paid_order_is_hidden_on_order_screen() {
		$fixture = $this->create_paid_order();
		$new_id  = $this->add_item_on_order_screen( $fixture['order'], $this->create_product( '50' ) );

		$item = wc_get_order( $fixture['order']->get_id() )->get_item( $new_id );
		$keys = wp_list_pluck( $item->get_meta_data(), 'key' );
		$ours = preg_grep( '/^_wc_connect/', $keys );
		$this->assertNotEmpty( $ours, 'the line carries a mark' );

		// What the order screen reads to leave meta out (html-order-item-meta.php).
		$hidden = (array) apply_filters( 'woocommerce_hidden_order_itemmeta', array() );
		foreach ( $ours as $key ) {
			$this->assertContains( $key, $hidden );
		}
	}

	/**
	 * @testdox On a paid order, a line added with Add item(s) and deleted again writes no note: the order is back to what was sold.
	 */
	public function test_paid_order_line_added_then_deleted_writes_no_note() {
		$fixture = $this->create_paid_order();
		$new_id  = $this->add_item_on_order_screen( $fixture['order'], $this->create_product( '50' ) );
		$this->start_new_request();

		$order = $this->press_delete_icon( wc_get_order( $fixture['order']->get_id() ), $new_id );

		$this->assertFalse( $order->get_item( $new_id ), 'the line is deleted' );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->locked_order_notes( $order ) );
	}

	/**
	 * @testdox On a paid order, a line added with Add item(s) and deleted while lines are being edited writes no note either.
	 */
	public function test_paid_order_line_added_then_deleted_in_edit_mode_writes_no_note() {
		$fixture = $this->create_paid_order();
		$new_id  = $this->add_item_on_order_screen( $fixture['order'], $this->create_product( '50' ) );
		$this->start_new_request();

		$order = wc_get_order( $fixture['order']->get_id() );
		$order = $this->press_delete_icon( $order, $new_id, $this->order_screen_form( $order ) );

		$this->assertFalse( $order->get_item( $new_id ), 'the line is deleted' );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$this->assertSame( array(), $this->locked_order_notes( $order ) );
	}

	/**
	 * @testdox On a paid order, Recalculate after Add item(s) counts the new line in the tax the order would have now.
	 */
	public function test_paid_order_line_added_on_order_screen_counts_in_tax_as_it_is_now() {
		$fixture = $this->create_paid_order();
		$new_id  = $this->add_item_on_order_screen( $fixture['order'], $this->create_product( '50' ) );
		$this->start_new_request();

		$order = wc_get_order( $fixture['order']->get_id() );
		$order = $this->press_recalculate( $order, $this->order_screen_form( $order ) );

		$this->assertEqualsWithDelta( 0.0, $this->item_tax( $order, $new_id ), 0.001, 'the added line gets no tax' );
		// 30 + 50 + 5 + the $2.10 charged.
		$this->assert_order_tax( $order, 1.80, 0.30, 87.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Change: Dummy Product added.', $notes[0] );
		// A $10, B $20, the new $50 and shipping $5, all at the order's 6%: $5.10, $3.00 more than charged.
		$this->assertStringContainsString( 'Tax for the order as it is now: $5.10 (6% on $85.00). Difference: $3.00.', $notes[0] );
	}

	/**
	 * @testdox On a paid order moved back to on hold, tax typed on a line and saved with Update stays at the tax charged, with a note.
	 */
	public function test_tax_typed_on_paid_order_on_hold_keeps_charged_tax_with_note() {
		$fixture = $this->create_paid_order();
		$order   = wc_get_order( $fixture['order']->get_id() );
		$order->set_status( 'on-hold' );
		$order->save();
		$this->start_new_request();

		$order = wc_get_order( $order->get_id() );
		$order = $this->press_update( $order, $this->order_screen_form( $order, array( $fixture['a'] => array( 'tax' => '1.00' ) ) ) );

		$this->assertEqualsWithDelta( 0.60, $this->item_tax( $order, $fixture['a'] ), 0.001, 'A keeps the tax it was charged' );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Change: Dummy Product tax changed from $0.60 to $1.00.', $notes[0] );
		$this->assertStringContainsString( 'Tax charged: $2.10 (6% on $35.00). The tax stays at $2.10.', $notes[0] );
	}

	/**
	 * @testdox On a paid order, tax typed on a line and then Recalculate stays at the tax charged, with one note.
	 */
	public function test_tax_typed_on_paid_order_then_recalculate_keeps_charged_tax_with_note() {
		$fixture = $this->create_paid_order();
		$order   = wc_get_order( $fixture['order']->get_id() );
		$order   = $this->press_recalculate( $order, $this->order_screen_form( $order, array( $fixture['b'] => array( 'tax' => '0.50' ) ) ) );

		$this->assertEqualsWithDelta( 1.20, $this->item_tax( $order, $fixture['b'] ), 0.001, 'B keeps the tax it was charged' );
		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Change: Dummy Product tax changed from $1.20 to $0.50.', $notes[0] );
	}

	/**
	 * @testdox On a paid order, a shipping address changed with the order screen's pencil and saved with Update keeps the tax, with the address note.
	 */
	public function test_paid_order_address_changed_on_order_screen_gets_address_note() {
		$fixture = $this->create_paid_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order = $this->press_update(
			$order,
			$this->order_screen_form( $order ),
			array(
				'shipping_city'     => 'Boulder',
				'shipping_postcode' => '80301',
			)
		);

		$this->assert_order_tax( $order, 1.80, 0.30, 37.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$lines = explode( "\n", $notes[0] );
		$this->assertSame( "This order's shipping address changed after its tax was charged.", $lines[0] );
		$this->assertSame( 'Change: Shipping address changed from Denver, CO, 80202, US to Boulder, CO, 80301, US.', $lines[2] );
		$this->assertSame( 'Order total: $37.10 (no change).', $lines[3] );
		$this->assertStringContainsString( 'The tax for the new address was not calculated.', $notes[0] );
		$this->assertStringContainsString( 'refund this order and create a new order with the new address', $notes[0] );
	}

	/**
	 * @testdox On a paid order, an address and a quantity changed in one Update get one address note that lists both.
	 */
	public function test_paid_order_address_and_quantity_changed_on_order_screen_get_one_note() {
		$fixture = $this->create_paid_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order = $this->press_update(
			$order,
			$this->order_screen_form(
				$order,
				array(
					$fixture['a'] => array(
						'qty'   => 2,
						'total' => '20',
						'tax'   => '1.2',
					),
				)
			),
			array(
				'shipping_city'     => 'Boulder',
				'shipping_postcode' => '80301',
			)
		);

		// 20 + 20 + 5 + the $2.10 charged.
		$this->assert_order_tax( $order, 1.80, 0.30, 47.10 );
		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( "This order's shipping address changed after its tax was charged.", $notes[0] );
		$this->assertStringContainsString( 'Change: Shipping address changed from Denver, CO, 80202, US to Boulder, CO, 80301, US. Dummy Product quantity changed from 1 to 2.', $notes[0] );
		$this->assertStringContainsString( 'Items: from $30.00 to $40.00. Order total: from $37.10 to $47.10.', $notes[0] );
		$this->assertStringNotContainsString( 'as it is now', $notes[0], 'the order\'s rates belong to the old address' );
	}

	/**
	 * @testdox An unpaid order given an address and made Processing in one Update gets no note: its tax was not charged before that save.
	 */
	public function test_unpaid_order_given_address_and_paid_in_one_update_gets_no_note() {
		$fixture = $this->create_placed_order();
		$order   = wc_get_order( $fixture['order']->get_id() );

		$order = $this->press_update(
			$order,
			$this->order_screen_form( $order ),
			array(
				'shipping_city'     => 'Boulder',
				'shipping_postcode' => '80301',
				'status'            => 'processing',
			)
		);

		$this->assertNotNull( $order->get_date_paid(), 'the order is paid now' );
		$this->assertSame( array(), $this->locked_order_notes( $order ) );
	}

	/**
	 * @testdox On a paid order, a change that leaves the tax the order would have as it was shows a difference of $0.00, never -$0.00.
	 */
	public function test_paid_order_note_shows_no_negative_zero_difference() {
		$fixture = $this->create_paid_order();

		// $5.04 shipping at 6% is $0.3024, which rounds to the $0.30 charged.
		$order = $this->rest_update(
			$fixture['order']->get_id(),
			array(
				'shipping_lines' => array(
					array(
						'id'    => $fixture['shipping'],
						'total' => '5.04',
					),
				),
			)
		);

		$notes = $this->locked_order_notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'Difference: $0.00.', $notes[0] );
		$this->assertStringNotContainsString( '-$0.00', $notes[0] );
	}
}
