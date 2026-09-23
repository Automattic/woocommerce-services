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
		$logger     = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$tracks     = $this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock();

		$this->integration = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks );
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
		delete_option( WC_Connect_TaxJar_Integration::OPTION_NAME );
		delete_option( 'woocommerce_calc_taxes' );

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
	 * Forget which order items this PHP request created.
	 *
	 * Fixtures are built in the same request as the edit under test; on a store they
	 * were placed in an earlier one.
	 */
	private function forget_created_in_request() {
		$property = new ReflectionProperty( $this->integration, 'order_items_created_in_request' );
		$property->setAccessible( true );
		$property->setValue(
			$this->integration,
			array(
				'objects' => array(),
				'ids'     => array(),
			)
		);
	}

	/**
	 * Build the placed fixture order described in the class docblock.
	 *
	 * @param array $overrides Optional: 'a_tax' (hand-typed tax on A), 'rate_percent'.
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
		$order->set_status( 'processing' );
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
	 * @testdox A fee changed to not taxable loses its tax.
	 */
	public function test_fee_changed_to_not_taxable_loses_tax() {
		$fixture = $this->create_placed_order();

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

	/**
	 * @testdox Creating an order over REST is left to WooCommerce (no recorded tax to keep).
	 */
	public function test_rest_order_creation_is_untouched() {
		$product = $this->create_product( '10' );

		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'shipping'   => array(
						'country'  => 'US',
						'state'    => 'CO',
						'postcode' => '80202',
					),
					'line_items' => array(
						array(
							'product_id' => $product->get_id(),
							'quantity'   => 1,
						),
					),
				)
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 201, $response->get_status() );

		$order = wc_get_order( $response->get_data()['id'] );

		// The table row is 0%, and nothing on a new order should override that.
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertEqualsWithDelta( 10.0, (float) $order->get_total(), 0.001 );
		$this->assertSame( array(), $this->tax_notes( $order ) );
	}
}
