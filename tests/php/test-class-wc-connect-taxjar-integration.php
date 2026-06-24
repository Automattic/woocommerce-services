<?php
/**
 * Tests for WC_Connect_TaxJar_Integration
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Integration
 *
 * Tests for the TaxJar integration class.
 */
class WP_Test_WC_Connect_TaxJar_Integration extends WC_Unit_Test_Case {

	/**
	 * TaxJar integration instance.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $integration;

	/**
	 * Test product.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Load required classes before running tests.
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
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		// Create mock dependencies.
		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )
			->disableOriginalConstructor()
			->getMock();

		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->getMock();

		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )
			->disableOriginalConstructor()
			->getMock();

		$this->integration = new WC_Connect_TaxJar_Integration(
			$api_client,
			$logger,
			'https://example.com',
			$tracks
		);
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		// Remove any filters added during tests.
		remove_all_filters( 'woocommerce_tax_line_item_location' );

		// Clean up products.
		if ( $this->product ) {
			WC_Helper_Product::delete_product( $this->product->get_id() );
			$this->product = null;
		}

		// Clear cart.
		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		parent::tear_down();
	}

	/**
	 * Test that calculate_order_taxes_via_taxjar hook is registered after init().
	 */
	public function test_calculate_order_taxes_hook_registered_after_init() {
		// Arrange: enable automated taxes option so init() does not bail early.
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Act.
		$this->integration->init();

		// Assert.
		$this->assertNotFalse(
			has_action( 'woocommerce_order_before_calculate_taxes', array( $this->integration, 'calculate_order_taxes_via_taxjar' ) ),
			'Hook woocommerce_order_before_calculate_taxes should be registered'
		);

		// Clean up.
		remove_all_actions( 'woocommerce_order_before_calculate_taxes' );
		delete_option( WC_Connect_TaxJar_Integration::OPTION_NAME );
		delete_option( 'woocommerce_calc_taxes' );
	}

	/**
	 * Helper to invoke protected methods via reflection.
	 *
	 * @param string $method_name Method name.
	 * @param array  $args        Method arguments.
	 * @return mixed Method result.
	 */
	private function invoke_protected_method( $method_name, $args = array() ) {
		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', $method_name );
		$reflection->setAccessible( true );
		return $reflection->invokeArgs( $this->integration, $args );
	}

	/**
	 * Helper to set private properties via reflection.
	 *
	 * @param string $property_name Property name.
	 * @param mixed  $value         Value to set.
	 */
	private function set_private_property( $property_name, $value ) {
		$reflection = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', $property_name );
		$reflection->setAccessible( true );
		$reflection->setValue( $this->integration, $value );
	}

	/**
	 * Test that get_line_items includes tax_location key with default value.
	 */
	public function test_get_line_items_includes_tax_location_with_default() {
		// Create a simple product.
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		// Add to cart.
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_line_items', array( WC()->cart ) );

		// Assert we have line items.
		$this->assertNotEmpty( $line_items );
		$this->assertCount( 1, $line_items );

		// Assert tax_location key exists.
		$this->assertArrayHasKey( 'tax_location', $line_items[0] );

		// Default should be 'shipping' (the WooCommerce default).
		$this->assertEquals( 'shipping', $line_items[0]['tax_location'] );
	}

	/**
	 * Test that get_line_items respects woocommerce_tax_based_on option.
	 */
	public function test_get_line_items_respects_tax_based_on_option() {
		// Set tax based on billing address.
		update_option( 'woocommerce_tax_based_on', 'billing' );

		// Create a simple product.
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		// Add to cart.
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_line_items', array( WC()->cart ) );

		// Assert tax_location uses billing.
		$this->assertEquals( 'billing', $line_items[0]['tax_location'] );

		// Clean up option.
		delete_option( 'woocommerce_tax_based_on' );
	}

	/**
	 * Test that woocommerce_tax_line_item_location filter can override location.
	 */
	public function test_get_line_items_filter_can_override_location() {
		// Create a simple product.
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		// Add filter to return 'base' for all products.
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) {
				return 'base';
			},
			10,
			2
		);

		// Add to cart.
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_line_items', array( WC()->cart ) );

		// Assert filter overrode the location.
		$this->assertEquals( 'base', $line_items[0]['tax_location'] );
	}

	/**
	 * Test that filter receives correct product object.
	 */
	public function test_get_line_items_filter_receives_product() {
		// Create a simple product.
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		$received_product  = null;
		$received_location = null;

		// Add filter to capture parameters.
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) use ( &$received_product, &$received_location ) {
				$received_location = $location;
				$received_product  = $product;
				return $location;
			},
			10,
			2
		);

		// Add to cart.
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		// Get line items.
		$this->invoke_protected_method( 'get_line_items', array( WC()->cart ) );

		// Assert filter received correct parameters.
		$this->assertNotNull( $received_product );
		$this->assertInstanceOf( 'WC_Product', $received_product );
		$this->assertEquals( $this->product->get_id(), $received_product->get_id() );
		$this->assertEquals( 'shipping', $received_location );
	}

	/**
	 * Test filter can return different locations for different products.
	 */
	public function test_get_line_items_filter_per_product_location() {
		// Create two products.
		$product_a = WC_Helper_Product::create_simple_product();
		$product_a->save();

		$product_b = WC_Helper_Product::create_simple_product();
		$product_b->save();

		$product_a_id = $product_a->get_id();

		// Add filter to return 'base' only for product A.
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) use ( $product_a_id ) {
				if ( $product && $product->get_id() === $product_a_id ) {
					return 'base';
				}
				return $location;
			},
			10,
			2
		);

		// Add both to cart.
		WC()->cart->add_to_cart( $product_a->get_id(), 1 );
		WC()->cart->add_to_cart( $product_b->get_id(), 1 );

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_line_items', array( WC()->cart ) );

		// Assert we have 2 line items.
		$this->assertCount( 2, $line_items );

		// Find items by product ID (they're in format "product_id-cart_key").
		$item_a = null;
		$item_b = null;
		foreach ( $line_items as $item ) {
			if ( strpos( $item['id'], $product_a->get_id() . '-' ) === 0 ) {
				$item_a = $item;
			} elseif ( strpos( $item['id'], $product_b->get_id() . '-' ) === 0 ) {
				$item_b = $item;
			}
		}

		// Assert different locations.
		$this->assertNotNull( $item_a );
		$this->assertNotNull( $item_b );
		$this->assertEquals( 'base', $item_a['tax_location'] );
		$this->assertEquals( 'shipping', $item_b['tax_location'] );

		// Clean up.
		WC_Helper_Product::delete_product( $product_a->get_id() );
		WC_Helper_Product::delete_product( $product_b->get_id() );
	}

	/**
	 * Test that get_backend_line_items includes tax_location key.
	 */
	public function test_get_backend_line_items_includes_tax_location() {
		// Create an order with a product.
		$order = WC_Helper_Order::create_order();
		$order->save();

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_backend_line_items', array( $order ) );

		// Assert we have line items.
		$this->assertNotEmpty( $line_items );

		// Assert tax_location key exists on all items.
		foreach ( $line_items as $item ) {
			$this->assertArrayHasKey( 'tax_location', $item );
		}
	}

	/**
	 * Test that get_backend_line_items filter can override location.
	 */
	public function test_get_backend_line_items_filter_can_override_location() {
		// Create an order.
		$order = WC_Helper_Order::create_order();
		$order->save();

		// Add filter to return 'base'.
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) {
				return 'base';
			},
			10,
			2
		);

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_backend_line_items', array( $order ) );

		// Assert all items have 'base' location.
		foreach ( $line_items as $item ) {
			$this->assertEquals( 'base', $item['tax_location'] );
		}
	}

	/**
	 * Test that filter receives product object in backend context.
	 */
	public function test_get_backend_line_items_filter_receives_product() {
		// Create an order.
		$order = WC_Helper_Order::create_order();
		$order->save();

		$received_product = null;

		// Add filter to capture product.
		add_filter(
			'woocommerce_tax_line_item_location',
			function ( $location, $product ) use ( &$received_product ) {
				$received_product = $product;
				return $location;
			},
			10,
			2
		);

		// Get line items.
		$this->invoke_protected_method( 'get_backend_line_items', array( $order ) );

		// Assert filter received a product.
		$this->assertNotNull( $received_product );
		$this->assertInstanceOf( 'WC_Product', $received_product );
	}

	/**
	 * Test that line items contain all expected keys.
	 */
	public function test_get_line_items_structure() {
		// Create a simple product.
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		// Add to cart.
		WC()->cart->add_to_cart( $this->product->get_id(), 2 );

		// Get line items.
		$line_items = $this->invoke_protected_method( 'get_line_items', array( WC()->cart ) );

		// Assert structure.
		$this->assertCount( 1, $line_items );

		$item = $line_items[0];
		$this->assertArrayHasKey( 'id', $item );
		$this->assertArrayHasKey( 'quantity', $item );
		$this->assertArrayHasKey( 'product_tax_code', $item );
		$this->assertArrayHasKey( 'unit_price', $item );
		$this->assertArrayHasKey( 'discount', $item );
		$this->assertArrayHasKey( 'tax_location', $item );

		// Assert quantity.
		$this->assertEquals( 2, $item['quantity'] );
	}

	// -------------------------------------------------------------------------
	// group_items_by_location() tests
	// -------------------------------------------------------------------------

	/**
	 * Test grouping items by tax_location key.
	 */
	public function test_group_items_by_location_groups_by_tax_location() {
		$line_items = array(
			array(
				'id'           => 'item-1',
				'tax_location' => 'shipping',
			),
			array(
				'id'           => 'item-2',
				'tax_location' => 'base',
			),
			array(
				'id'           => 'item-3',
				'tax_location' => 'shipping',
			),
		);

		$groups = $this->invoke_protected_method( 'group_items_by_location', array( $line_items, false ) );

		$this->assertCount( 2, $groups );
		$this->assertArrayHasKey( 'shipping', $groups );
		$this->assertArrayHasKey( 'base', $groups );
		$this->assertCount( 2, $groups['shipping'] );
		$this->assertCount( 1, $groups['base'] );
		$this->assertEquals( 'item-2', $groups['base'][0]['id'] );
	}

	/**
	 * Test local pickup forces all items to base.
	 */
	public function test_group_items_by_location_local_pickup_forces_base() {
		$line_items = array(
			array(
				'id'           => 'item-1',
				'tax_location' => 'shipping',
			),
			array(
				'id'           => 'item-2',
				'tax_location' => 'billing',
			),
		);

		$groups = $this->invoke_protected_method( 'group_items_by_location', array( $line_items, true ) );

		$this->assertCount( 1, $groups );
		$this->assertArrayHasKey( 'base', $groups );
		$this->assertCount( 2, $groups['base'] );
	}

	/**
	 * Test missing tax_location uses woocommerce_tax_based_on default.
	 */
	public function test_group_items_by_location_uses_default_when_missing() {
		update_option( 'woocommerce_tax_based_on', 'billing' );

		$line_items = array(
			array( 'id' => 'item-1' ), // No tax_location key.
		);

		$groups = $this->invoke_protected_method( 'group_items_by_location', array( $line_items, false ) );

		$this->assertArrayHasKey( 'billing', $groups );
		$this->assertCount( 1, $groups['billing'] );

		delete_option( 'woocommerce_tax_based_on' );
	}

	/**
	 * Test empty input returns empty groups.
	 */
	public function test_group_items_by_location_empty_input() {
		$groups = $this->invoke_protected_method( 'group_items_by_location', array( array(), false ) );

		$this->assertIsArray( $groups );
		$this->assertEmpty( $groups );
	}

	// -------------------------------------------------------------------------
	// get_taxable_address() tests
	// -------------------------------------------------------------------------

	/**
	 * Test that location_type 'base' returns store address.
	 */
	public function test_get_taxable_address_base_returns_store_address() {
		$store_country = WC()->countries->get_base_country();
		$store_state   = WC()->countries->get_base_state();

		$address = $this->invoke_protected_method( 'get_taxable_address', array( 'base' ) );

		$this->assertIsArray( $address );
		$this->assertEquals( $store_country, $address[0] );
		$this->assertEquals( $store_state, $address[1] );
	}

	/**
	 * Test that location_type 'shipping' returns customer shipping address.
	 */
	public function test_get_taxable_address_shipping_returns_customer_address() {
		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'NY' );
		WC()->customer->set_shipping_postcode( '10001' );

		$address = $this->invoke_protected_method( 'get_taxable_address', array( 'shipping' ) );

		$this->assertEquals( 'US', $address[0] );
		$this->assertEquals( 'NY', $address[1] );
		$this->assertEquals( '10001', $address[2] );
	}

	/**
	 * Test that location_type 'billing' returns customer billing address.
	 */
	public function test_get_taxable_address_billing_returns_billing_address() {
		WC()->customer->set_billing_country( 'US' );
		WC()->customer->set_billing_state( 'TX' );
		WC()->customer->set_billing_postcode( '73301' );

		$address = $this->invoke_protected_method( 'get_taxable_address', array( 'billing' ) );

		$this->assertEquals( 'US', $address[0] );
		$this->assertEquals( 'TX', $address[1] );
		$this->assertEquals( '73301', $address[2] );
	}

	/**
	 * Test that null location_type uses woocommerce_tax_based_on option (backward compat).
	 */
	public function test_get_taxable_address_null_uses_option() {
		update_option( 'woocommerce_tax_based_on', 'base' );

		$store_country = WC()->countries->get_base_country();

		$address = $this->invoke_protected_method( 'get_taxable_address', array( null ) );

		$this->assertEquals( $store_country, $address[0] );

		delete_option( 'woocommerce_tax_based_on' );
	}

	/**
	 * Test get_taxable_address returns early with empty array when WC()->customer is null.
	 */
	public function test_get_taxable_address_returns_empty_when_customer_is_null() {
		$original_customer = WC()->customer;
		WC()->customer     = null;

		$address = $this->invoke_protected_method( 'get_taxable_address', array( 'shipping' ) );

		$this->assertEquals( array( '', '', '', '', '' ), $address );

		WC()->customer = $original_customer;
	}

	/**
	 * Test get_taxable_address returns early for billing type when WC()->customer is null.
	 */
	public function test_get_taxable_address_billing_returns_empty_when_customer_is_null() {
		$original_customer = WC()->customer;
		WC()->customer     = null;

		$address = $this->invoke_protected_method( 'get_taxable_address', array( 'billing' ) );

		$this->assertEquals( array( '', '', '', '', '' ), $address );

		WC()->customer = $original_customer;
	}

	/**
	 * Test get_taxable_address base type still works when WC()->customer is null.
	 */
	public function test_get_taxable_address_base_works_when_customer_is_null() {
		$original_customer = WC()->customer;
		WC()->customer     = null;

		$store_country = WC()->countries->get_base_country();
		$address       = $this->invoke_protected_method( 'get_taxable_address', array( 'base' ) );

		$this->assertEquals( $store_country, $address[0] );

		WC()->customer = $original_customer;
	}

	// -------------------------------------------------------------------------
	// aggregate_tax_totals() tests
	// -------------------------------------------------------------------------

	/**
	 * Test aggregation combines entries with same label.
	 */
	public function test_aggregate_tax_totals_combines_same_labels() {
		$tax_a         = new stdClass();
		$tax_a->label  = 'County Tax';
		$tax_a->amount = 0.25;

		$tax_b         = new stdClass();
		$tax_b->label  = 'County Tax';
		$tax_b->amount = 0.12;

		$tax_c         = new stdClass();
		$tax_c->label  = 'State Tax';
		$tax_c->amount = 1.50;

		$tax_totals = array(
			'US-CA-1' => $tax_a,
			'US-NY-1' => $tax_b,
			'US-CA-2' => $tax_c,
		);

		$result = $this->integration->aggregate_tax_totals( $tax_totals, WC()->cart );

		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 'County Tax', $result );
		$this->assertArrayHasKey( 'State Tax', $result );
		$this->assertEquals( 0.37, $result['County Tax']->amount );
		$this->assertEquals( 1.50, $result['State Tax']->amount );
	}

	/**
	 * Test aggregation returns input unchanged when count <= 1.
	 */
	public function test_aggregate_tax_totals_single_entry_unchanged() {
		$tax         = new stdClass();
		$tax->label  = 'Tax';
		$tax->amount = 1.00;

		$tax_totals = array( 'US-1' => $tax );

		$result = $this->integration->aggregate_tax_totals( $tax_totals, WC()->cart );

		$this->assertSame( $tax_totals, $result );
	}

	/**
	 * Test aggregation returns input unchanged when not an array.
	 */
	public function test_aggregate_tax_totals_non_array_unchanged() {
		$result = $this->integration->aggregate_tax_totals( null, WC()->cart );
		$this->assertNull( $result );
	}

	/**
	 * Test aggregation does not mutate original objects.
	 */
	public function test_aggregate_tax_totals_clones_objects() {
		$tax_a         = new stdClass();
		$tax_a->label  = 'Tax';
		$tax_a->amount = 1.00;

		$tax_b         = new stdClass();
		$tax_b->label  = 'Tax';
		$tax_b->amount = 2.00;

		$tax_totals = array(
			'A' => $tax_a,
			'B' => $tax_b,
		);

		$result = $this->integration->aggregate_tax_totals( $tax_totals, WC()->cart );

		// Original objects should not be modified.
		$this->assertEquals( 1.00, $tax_a->amount );
		$this->assertEquals( 2.00, $tax_b->amount );

		// Aggregated result should have combined amount.
		$this->assertEquals( 3.00, $result['Tax']->amount );
	}

	// -------------------------------------------------------------------------
	// override_cart_item_tax_rates() tests
	// -------------------------------------------------------------------------

	/**
	 * Test returns original rates when response_rate_ids is empty.
	 */
	public function test_override_cart_item_tax_rates_returns_original_when_no_response() {
		$original_rates = array( 1 => array( 'rate' => 8.5 ) );
		$item           = new stdClass();
		$item->product  = null;

		$result = $this->integration->override_cart_item_tax_rates( $original_rates, $item, WC()->cart );

		$this->assertSame( $original_rates, $result );
	}

	/**
	 * Test returns original rates when item has no product.
	 */
	public function test_override_cart_item_tax_rates_returns_original_when_no_product() {
		$this->set_private_property( 'response_rate_ids', array( '10-abc' => array( 1, 2 ) ) );

		$original_rates = array( 1 => array( 'rate' => 8.5 ) );
		$item           = new stdClass();

		$result = $this->integration->override_cart_item_tax_rates( $original_rates, $item, WC()->cart );

		$this->assertSame( $original_rates, $result );
	}

	/**
	 * Test returns original rates when no matching product in response_rate_ids.
	 */
	public function test_override_cart_item_tax_rates_returns_original_when_no_match() {
		$this->set_private_property( 'response_rate_ids', array( '999-abc' => array( 1, 2 ) ) );

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		$original_rates = array( 1 => array( 'rate' => 8.5 ) );
		$item           = new stdClass();
		$item->product  = $this->product;

		$result = $this->integration->override_cart_item_tax_rates( $original_rates, $item, WC()->cart );

		$this->assertSame( $original_rates, $result );
	}

	/**
	 * Test returns TaxJar rates when matching product found.
	 */
	public function test_override_cart_item_tax_rates_returns_taxjar_rates_when_matched() {
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->save();

		// Insert a tax rate into the database.
		$rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '8.5',
				'tax_rate_name'     => 'CA Tax',
				'tax_rate_shipping' => 'no',
				'tax_rate_compound' => 'no',
				'tax_rate_priority' => 1,
				'tax_rate_class'    => '',
			)
		);

		$product_id = $this->product->get_id();
		$this->set_private_property( 'response_rate_ids', array( $product_id . '-abc123' => array( $rate_id ) ) );

		$original_rates = array();
		$item           = new stdClass();
		$item->product  = $this->product;

		$result = $this->integration->override_cart_item_tax_rates( $original_rates, $item, WC()->cart );

		$this->assertNotEmpty( $result );
		$this->assertArrayHasKey( $rate_id, $result );
		$this->assertEquals( 8.5, $result[ $rate_id ]['rate'] );
		$this->assertEquals( 'CA Tax', $result[ $rate_id ]['label'] );

		// Clean up.
		WC_Tax::_delete_tax_rate( $rate_id );
	}

	// -------------------------------------------------------------------------
	// override_order_item_taxes() tests
	// -------------------------------------------------------------------------

	/**
	 * Test no-op when response_rate_ids is empty (no TaxJar calc this request).
	 */
	public function test_override_order_item_taxes_noop_when_no_response() {
		$order = WC_Helper_Order::create_order();
		$items = $order->get_items();
		$item  = reset( $items );
		$orig  = $item->get_taxes();

		$this->integration->override_order_item_taxes( $item, array() );

		$this->assertSame( $orig, $item->get_taxes() );
		$order->delete( true );
	}

	/**
	 * Test no-op when item is not a WC_Order_Item_Product (e.g. fee or shipping).
	 */
	public function test_override_order_item_taxes_skips_non_product_items() {
		$this->set_private_property( 'response_rate_ids', array( '10-abc' => array( 1 ) ) );

		$fee = new WC_Order_Item_Fee();
		$fee->set_total( '5.00' );

		// Should not throw or modify.
		$this->integration->override_order_item_taxes( $fee, array() );

		$this->assertEmpty( $fee->get_taxes()['total'] );
	}

	/**
	 * Test no-op when the product is not found in response_rate_ids (cross-state item).
	 */
	public function test_override_order_item_taxes_skips_unmatched_product() {
		$this->set_private_property( 'response_rate_ids', array( '999-abc' => array( 1 ) ) );

		$order = WC_Helper_Order::create_order();
		$items = $order->get_items();
		$item  = reset( $items );
		$orig  = $item->get_taxes();

		$this->integration->override_order_item_taxes( $item, array() );

		$this->assertSame( $orig, $item->get_taxes() );
		$order->delete( true );
	}

	/**
	 * Test that TaxJar rates are applied for a matched base-group item.
	 */
	public function test_override_order_item_taxes_applies_taxjar_rates_when_matched() {
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( '20.00' );
		$this->product->save();

		// Insert a tax rate into the database.
		$rate_id = WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CA',
				'tax_rate'          => '10.0000',
				'tax_rate_name'     => 'CA Tax',
				'tax_rate_shipping' => 'no',
				'tax_rate_compound' => 'no',
				'tax_rate_priority' => 1,
				'tax_rate_class'    => '',
			)
		);

		$product_id = $this->product->get_id();
		$this->set_private_property( 'response_rate_ids', array( $product_id . '-abc123' => array( $rate_id ) ) );

		// Create an order item.
		$item = new WC_Order_Item_Product();
		$item->set_product( $this->product );
		$item->set_quantity( 1 );
		$item->set_total( '20.00' );
		$item->set_subtotal( '20.00' );

		$this->integration->override_order_item_taxes( $item, array() );

		$taxes = $item->get_taxes();
		$this->assertArrayHasKey( $rate_id, $taxes['total'] );
		$this->assertEquals( 2.0, (float) $taxes['total'][ $rate_id ] );
		$this->assertArrayHasKey( $rate_id, $taxes['subtotal'] );
		$this->assertEquals( 2.0, (float) $taxes['subtotal'][ $rate_id ] );

		// Clean up.
		WC_Tax::_delete_tax_rate( $rate_id );
	}

	// -------------------------------------------------------------------------
	// calculate_taxes_by_location() tests
	// -------------------------------------------------------------------------

	/**
	 * Test merges results from multiple location groups.
	 */
	public function test_calculate_taxes_by_location_merges_groups() {
		// Create a partial mock that stubs calculate_tax and get_address.
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_address', '_log' ) )
			->getMock();

		$integration->method( 'get_address' )->willReturnCallback(
			function ( $type ) {
				return array(
					'to_country' => 'US',
					'to_state'   => 'CA',
					'to_zip'     => '94110',
					'to_city'    => 'San Francisco',
					'to_street'  => '123 Main St',
				);
			}
		);

		$integration->method( 'calculate_tax' )->willReturnCallback(
			function ( $options ) {
				$has_shipping = ! empty( $options['shipping_amount'] );
				return array(
					'rate_ids'   => $has_shipping ? array( 'ship-item' => array( 5 ) ) : array( 'base-item' => array( 6 ) ),
					'line_items' => $has_shipping ? array( 'ship-item' => array( 'tax' => 1.0 ) ) : array( 'base-item' => array( 'tax' => 0.5 ) ),
				);
			}
		);

		$items_by_location = array(
			'shipping' => array( array( 'id' => 'ship-item' ) ),
			'base'     => array( array( 'id' => 'base-item' ) ),
		);

		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'calculate_taxes_by_location' );
		$reflection->setAccessible( true );
		$result = $reflection->invokeArgs( $integration, array( $items_by_location, 10.0 ) );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'ship-item', $result['rate_ids'] );
		$this->assertArrayHasKey( 'base-item', $result['rate_ids'] );
		$this->assertArrayHasKey( 'ship-item', $result['line_items'] );
		$this->assertArrayHasKey( 'base-item', $result['line_items'] );
	}

	/**
	 * Test shipping amount only applied to shipping group.
	 */
	public function test_calculate_taxes_by_location_shipping_only_on_shipping_group() {
		$received_options = array();

		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_address', '_log' ) )
			->getMock();

		$integration->method( 'get_address' )->willReturn(
			array(
				'to_country' => 'US',
				'to_state'   => 'CA',
				'to_zip'     => '94110',
				'to_city'    => 'San Francisco',
				'to_street'  => '',
			)
		);

		$integration->method( 'calculate_tax' )->willReturnCallback(
			function ( $options ) use ( &$received_options ) {
				$received_options[] = $options;
				return array(
					'rate_ids'   => array( 'x' => array( 1 ) ),
					'line_items' => array( 'x' => array() ),
				);
			}
		);

		$items_by_location = array(
			'base'     => array( array( 'id' => 'item-1' ) ),
			'shipping' => array( array( 'id' => 'item-2' ) ),
		);

		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'calculate_taxes_by_location' );
		$reflection->setAccessible( true );
		$reflection->invokeArgs( $integration, array( $items_by_location, 15.0 ) );

		// Base group should have 0 shipping.
		$this->assertEquals( 0, $received_options[0]['shipping_amount'] );
		// Shipping group should have full shipping amount.
		$this->assertEquals( 15.0, $received_options[1]['shipping_amount'] );
	}

	/**
	 * Test returns false when calculate_tax returns false for any group.
	 */
	public function test_calculate_taxes_by_location_returns_false_on_error() {
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_address', '_log' ) )
			->getMock();

		$integration->method( 'get_address' )->willReturn(
			array(
				'to_country' => 'US',
				'to_state'   => 'CA',
				'to_zip'     => '94110',
				'to_city'    => 'San Francisco',
				'to_street'  => '',
			)
		);

		// First group succeeds, second group fails.
		$integration->method( 'calculate_tax' )->willReturnOnConsecutiveCalls(
			array(
				'rate_ids'   => array( 'a' => array( 1 ) ),
				'line_items' => array( 'a' => array() ),
			),
			false
		);

		$items_by_location = array(
			'base'     => array( array( 'id' => 'item-1' ) ),
			'shipping' => array( array( 'id' => 'item-2' ) ),
		);

		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'calculate_taxes_by_location' );
		$reflection->setAccessible( true );
		$result = $reflection->invokeArgs( $integration, array( $items_by_location, 0 ) );

		$this->assertFalse( $result );
	}

	/**
	 * Test cross-state group returns empty taxes, other groups still proceed.
	 */
	public function test_calculate_taxes_by_location_cross_state_skips_group() {
		$call_count = 0;

		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_address', 'get_store_settings', '_log' ) )
			->getMock();

		// Store is in CA.
		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'CA',
				'postcode' => '94110',
				'city'     => 'San Francisco',
				'street'   => '',
			)
		);

		$integration->method( 'get_address' )->willReturnCallback(
			function ( $type ) {
				if ( 'base' === $type ) {
					return array(
						'to_country' => 'US',
						'to_state'   => 'CA',
						'to_zip'     => '94110',
						'to_city'    => 'San Francisco',
						'to_street'  => '',
					);
				}
				// Shipping group goes to NY (cross-state).
				return array(
					'to_country' => 'US',
					'to_state'   => 'NY',
					'to_zip'     => '10001',
					'to_city'    => 'New York',
					'to_street'  => '',
				);
			}
		);

		$integration->method( 'calculate_tax' )->willReturnCallback(
			function ( $options ) use ( &$call_count ) {
				$call_count++;
				$to_state = strtoupper( $options['to_state'] );
				// Cross-state returns empty taxes (the behavior under test).
				if ( 'NY' === $to_state ) {
					return array(
						'freight_taxable' => 1,
						'has_nexus'       => 0,
						'line_items'      => array(),
						'rate_ids'        => array(),
						'tax_rate'        => 0,
					);
				}
				return array(
					'rate_ids'   => array( 'base-item' => array( 1, 2 ) ),
					'line_items' => array( 'base-item' => array( 'tax' => 1.04 ) ),
				);
			}
		);

		$items_by_location = array(
			'base'     => array( array( 'id' => 'base-item' ) ),
			'shipping' => array( array( 'id' => 'ship-item' ) ),
		);

		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'calculate_taxes_by_location' );
		$reflection->setAccessible( true );
		$result = $reflection->invokeArgs( $integration, array( $items_by_location, 0 ) );

		// Should not be false — base group has taxes.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'base-item', $result['rate_ids'] );
		// Both groups were called.
		$this->assertEquals( 2, $call_count );
	}

	/**
	 * Test all groups empty (all cross-state) returns false.
	 */
	public function test_calculate_taxes_by_location_all_empty_returns_false() {
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_address', '_log' ) )
			->getMock();

		$integration->method( 'get_address' )->willReturn(
			array(
				'to_country' => 'US',
				'to_state'   => 'NY',
				'to_zip'     => '10001',
				'to_city'    => 'New York',
				'to_street'  => '',
			)
		);

		// All groups return empty taxes (cross-state).
		$integration->method( 'calculate_tax' )->willReturn(
			array(
				'freight_taxable' => 1,
				'has_nexus'       => 0,
				'line_items'      => array(),
				'rate_ids'        => array(),
				'tax_rate'        => 0,
			)
		);

		$items_by_location = array(
			'shipping' => array( array( 'id' => 'item-1' ) ),
		);

		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'calculate_taxes_by_location' );
		$reflection->setAccessible( true );
		$result = $reflection->invokeArgs( $integration, array( $items_by_location, 0 ) );

		$this->assertFalse( $result );
	}

	// -------------------------------------------------------------------------
	// calculate_tax() cross-state change tests
	// -------------------------------------------------------------------------

	/**
	 * Test US cross-state returns empty taxes array, not false.
	 */
	public function test_calculate_tax_cross_state_returns_empty_taxes() {
		// Set store to California.
		update_option( 'woocommerce_default_country', 'US:CA' );

		// Set customer to not VAT exempt so the early return doesn't trigger.
		WC()->customer->set_is_vat_exempt( false );

		$result = $this->invoke_protected_method(
			'calculate_tax',
			array(
				array(
					'to_country'      => 'US',
					'to_state'        => 'NY',
					'to_zip'          => '10001',
					'to_city'         => 'New York',
					'to_street'       => '123 Broadway',
					'shipping_amount' => 0,
					'line_items'      => array(
						array(
							'id'         => 'test-item',
							'quantity'   => 1,
							'unit_price' => '25.00',
						),
					),
				),
			)
		);

		// Should be an array (empty taxes), not false.
		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'rate_ids', $result );
		$this->assertArrayHasKey( 'line_items', $result );
		$this->assertEmpty( $result['rate_ids'] );
		$this->assertEmpty( $result['line_items'] );
		$this->assertEquals( 0, $result['has_nexus'] );
	}

	/**
	 * Test that calculate_order_taxes_via_taxjar skips when response_rate_ids already set (cart path ran).
	 */
	public function test_calculate_order_taxes_skips_when_response_rate_ids_populated() {
		// Arrange: pre-populate response_rate_ids as the cart path would.
		$this->set_private_property( 'response_rate_ids', array( 'product-key' => array( 1, 2 ) ) );

		$order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->getMock();

		// Assert: get_id() should never be called if gate triggers.
		$order->expects( $this->never() )->method( 'get_id' );

		// Act.
		$this->integration->calculate_order_taxes_via_taxjar( array(), $order );
	}

	/**
	 * Test that calculate_order_taxes_via_taxjar skips when order ID is 0 (new order).
	 */
	public function test_calculate_order_taxes_skips_when_order_id_is_zero() {
		// Arrange: response_rate_ids is empty (REST context).
		$this->set_private_property( 'response_rate_ids', array() );

		$order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->getMock();
		$order->method( 'get_id' )->willReturn( 0 );

		// Assert: get_shipping_country() should never be called if gate triggers.
		$order->expects( $this->never() )->method( 'get_shipping_country' );

		// Act.
		$this->integration->calculate_order_taxes_via_taxjar( array(), $order );
	}

	/**
	 * Test that calculate_order_taxes_via_taxjar skips when wp_doing_ajax() is true.
	 */
	public function test_calculate_order_taxes_skips_when_doing_ajax() {
		// Use a filter instead of define() — constants pollute the entire test run.
		add_filter( 'wp_doing_ajax', '__return_true' );

		// Arrange: response_rate_ids is empty so the first gate does not trigger.
		$this->set_private_property( 'response_rate_ids', array() );

		$order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_id' ) )
			->getMock();

		// Assert: get_id() is never reached because the AJAX gate fires first.
		$order->expects( $this->never() )->method( 'get_id' );

		// Act.
		$this->integration->calculate_order_taxes_via_taxjar( array(), $order );

		// Clean up.
		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	// -------------------------------------------------------------------------
	// snapshot_order_taxes() and restore_order_taxes() tests
	// -------------------------------------------------------------------------

	/**
	 * Test that snapshot_order_taxes captures existing tax lines and item taxes.
	 */
	public function test_snapshot_order_taxes_captures_tax_state() {
		// Create a real order with a tax line item.
		$order = wc_create_order();

		// Add a tax line.
		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_code( 'US-CA-TAX-1' );
		$tax_item->set_rate_id( 6 );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_tax_total( '1.76' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->save();

		// Act.
		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );

		// Assert structure.
		$this->assertArrayHasKey( 'tax_lines', $snapshot );
		$this->assertArrayHasKey( 'item_taxes', $snapshot );
		$this->assertCount( 1, $snapshot['tax_lines'] );

		$saved_tax = reset( $snapshot['tax_lines'] );
		$this->assertEquals( 6, $saved_tax['rate_id'] );
		$this->assertEquals( '1.76', $saved_tax['tax_total'] );
		$this->assertEquals( '0.00', $saved_tax['shipping_tax_total'] );

		// Clean up.
		$order->delete( true );
	}

	/**
	 * Test that restore_order_taxes re-adds tax lines removed from an order.
	 */
	public function test_restore_order_taxes_restores_tax_lines() {
		// Create a real order.
		$order = wc_create_order();

		// Add a tax line and save snapshot before wiping.
		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_tax_total( '1.76' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->save();

		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );

		// Simulate wipe: remove all tax items.
		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->save();
		$this->assertCount( 0, $order->get_taxes() );

		// Act: restore from snapshot.
		$this->invoke_protected_method( 'restore_order_taxes', array( $order, $snapshot ) );

		// Re-fetch order to confirm DB was updated.
		$restored_order = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $restored_order->get_taxes() );

		$taxes_list   = $restored_order->get_taxes();
		$restored_tax = reset( $taxes_list );
		$this->assertEquals( 6, $restored_tax->get_rate_id() );
		$this->assertEquals( '1.76', $restored_tax->get_tax_total() );

		// Clean up.
		$order->delete( true );
	}

	// -------------------------------------------------------------------------
	// calculate_order_taxes_via_taxjar() happy path tests
	// -------------------------------------------------------------------------

	/**
	 * Test happy path: calculate_order_taxes_via_taxjar calls TaxJar API
	 * and sets response_rate_ids / response_line_items on success.
	 */
	public function test_calculate_order_taxes_happy_path_sets_response_properties() {
		// Arrange: empty response_rate_ids (REST context, not cart).
		$this->set_private_property( 'response_rate_ids', array() );

		// Create a real order with a product line item.
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'TX' );
		$order->set_shipping_postcode( '78701' );
		$order->set_shipping_city( 'Austin' );
		$order->set_shipping_address_1( '123 Main St' );
		$order->save();

		// Mock calculate_tax() to return a fake successful response.
		$fake_taxes = array(
			'rate_ids'   => array( $product->get_id() . '-1' => array( 42 ) ),
			'line_items' => array(
				$product->get_id() . '-1' => (object) array(
					'tax_collectable'   => 1.23,
					'combined_tax_rate' => 0.0825,
					'line_total'        => 18.00,
				),
			),
		);

		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_backend_line_items', '_log' ) )
			->getMock();
		$integration->method( 'get_backend_line_items' )->willReturn( array() );
		$integration->method( 'calculate_tax' )->willReturn( $fake_taxes );
		$integration->method( '_log' )->willReturn( null );

		// Pre-set empty response_rate_ids on the mock.
		$reflection = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'response_rate_ids' );
		$reflection->setAccessible( true );
		$reflection->setValue( $integration, array() );

		// Need WC()->customer for calculate_tax guard.
		if ( is_null( WC()->customer ) ) {
			WC()->customer = new WC_Customer( $order->get_customer_id() );
		}

		// Act.
		$integration->calculate_order_taxes_via_taxjar( array(), $order );

		// Assert response properties were set.
		$rate_ids = $reflection->getValue( $integration );
		// After running, response_rate_ids should have been set (not empty from fake_taxes).
		// We check via the mock that calculate_tax was called once.
		// (response_rate_ids would be set to $fake_taxes['rate_ids']).
		$response_rate_ids_prop = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'response_rate_ids' );
		$response_rate_ids_prop->setAccessible( true );
		$this->assertEquals( $fake_taxes['rate_ids'], $response_rate_ids_prop->getValue( $integration ) );

		$response_line_items_prop = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', 'response_line_items' );
		$response_line_items_prop->setAccessible( true );
		$this->assertNotEmpty( $response_line_items_prop->getValue( $integration ) );

		// Clean up.
		WC_Helper_Product::delete_product( $product->get_id() );
		$order->delete( true );
	}

	// -------------------------------------------------------------------------
	// calculate_order_taxes_via_taxjar() failure fallback tests
	// -------------------------------------------------------------------------

	/**
	 * Test that failure flag is set when TaxJar API fails.
	 */
	public function test_calculate_order_taxes_sets_failure_flag_on_api_error() {
		$order       = wc_create_order();
		$integration = $this->getMockBuilder( WC_Connect_TaxJar_Integration::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_backend_line_items', '_log' ) )
			->getMock();
		$integration->method( 'calculate_tax' )->willReturn( false );
		$integration->method( 'get_backend_line_items' )->willReturn( array() );
		$integration->method( '_log' )->willReturn( null );

		$integration->calculate_order_taxes_via_taxjar( array(), $order );

		$prop = new ReflectionProperty( WC_Connect_TaxJar_Integration::class, 'taxjar_recalculation_failed' );
		$prop->setAccessible( true );
		$this->assertTrue( $prop->getValue( $integration ) );
		$order->delete( true );
	}

	/**
	 * Test that restore hook is registered when TaxJar API fails.
	 */
	public function test_calculate_order_taxes_registers_restore_hook_on_api_failure() {
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$order       = wc_create_order();
		$integration = $this->getMockBuilder( WC_Connect_TaxJar_Integration::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_backend_line_items', '_log' ) )
			->getMock();
		$integration->method( 'calculate_tax' )->willReturn( false );
		$integration->method( 'get_backend_line_items' )->willReturn( array() );
		$integration->method( '_log' )->willReturn( null );

		$integration->calculate_order_taxes_via_taxjar( array(), $order );

		$this->assertGreaterThan( 0, has_action( 'woocommerce_order_after_calculate_totals' ) );
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$order->delete( true );
	}

	/**
	 * End-to-end test: when TaxJar API fails, original taxes are preserved.
	 */
	public function test_calculate_order_taxes_preserves_existing_taxes_on_api_failure() {
		// Create order with a real tax line.
		$product = WC_Helper_Product::create_simple_product();
		$product->save();

		$order = wc_create_order();
		$order->add_product( $product, 1 );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'TX' );
		$order->set_shipping_postcode( '78701' );
		$order->save();

		// Add a TaxJar-style tax line.
		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_tax_total( '1.76' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->save();

		$this->assertCount( 1, $order->get_taxes() );

		// Mock integration with failing API.
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'calculate_tax', 'get_backend_line_items', '_log' ) )
			->getMock();
		$integration->method( 'calculate_tax' )->willReturn( false );
		$integration->method( 'get_backend_line_items' )->willReturn( array() );
		$integration->method( '_log' )->willReturn( null );

		foreach ( array( 'response_rate_ids', 'taxjar_recalculation_failed', 'pre_recalculation_tax_snapshot' ) as $prop ) {
			$r = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', $prop );
			$r->setAccessible( true );
			$r->setValue( $integration, 'taxjar_recalculation_failed' === $prop ? false : array() );
		}

		if ( is_null( WC()->customer ) ) {
			WC()->customer = new WC_Customer( $order->get_customer_id() );
		}

		// Simulate the full flow: run our hook, then wipe taxes (as WC would), then trigger after hook.
		$integration->calculate_order_taxes_via_taxjar( array(), $order );

		// Simulate WC wiping taxes (what update_taxes() does when no rates found).
		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->set_cart_tax( 0 );
		$order->save();
		$this->assertCount( 0, $order->get_taxes() );

		// Simulate woocommerce_order_after_calculate_totals firing.
		do_action( 'woocommerce_order_after_calculate_totals', true, $order );

		// Assert: tax line was restored.
		$restored = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $restored->get_taxes() );
		$taxes_list   = $restored->get_taxes();
		$restored_tax = reset( $taxes_list );
		$this->assertEquals( 6, $restored_tax->get_rate_id() );
		$this->assertEquals( '1.76', $restored_tax->get_tax_total() );

		// Clean up.
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		WC_Helper_Product::delete_product( $product->get_id() );
		$order->delete( true );
	}

	/**
	 * A tax calculation whose taxable amount is zero must not zero out the tax rate.
	 *
	 * When a subscription is switched, or when a free-trial subscription's initial
	 * cart total is $0, TaxJar returns a response whose top-level `rate` (and
	 * `amount_to_collect`) is 0 because `taxable_amount` is 0 — yet the real
	 * per-jurisdiction rates are still present inside `breakdown.line_items`. The
	 * itemized rate builder must persist those real rates and must never write the
	 * zeroed top-level rate, which previously clobbered an existing tax rate to
	 * 0.0000%.
	 *
	 * Regression test for WOOTAX-25 (free-trial renewal charged no tax) and
	 * WOOTAX-18 (subscription switch zeroed an existing rate). The fixture mirrors
	 * the exact response captured in WOOTAX-18.
	 */
	public function test_zero_amount_response_persists_real_itemized_rates() {
		$options = array(
			'to_country' => 'US',
			'to_state'   => 'MO',
			'to_zip'     => '64150',
			'to_city'    => 'RIVERSIDE',
		);

		// taxable_amount and the top-level rate are 0, but the line item still
		// carries the real jurisdiction rates (city 1.5%, county 1.25%, state 4.225%).
		$line_item = (object) array(
			'id'                   => '351-regressionkey',
			'city_tax_rate'        => 0.015,
			'county_tax_rate'      => 0.0125,
			'state_sales_tax_rate' => 0.04225,
			'special_tax_rate'     => 0.0,
			'combined_tax_rate'    => 0.06975,
			'taxable_amount'       => 0.0,
		);
		$taxjar_taxes = (object) array(
			'freight_taxable' => 0,
			'has_nexus'       => 1,
			'rate'            => 0.0,
			'jurisdictions'   => (object) array(
				'country' => 'US',
				'state'   => 'MO',
				'county'  => 'PLATTE COUNTY',
				'city'    => 'RIVERSIDE',
			),
			'breakdown'       => (object) array(
				'line_items' => array( $line_item ),
			),
		);

		$taxes = array(
			'freight_taxable' => 1,
			'has_nexus'       => 0,
			'line_items'      => array(),
			'rate_ids'        => array(),
			'tax_rate'        => 0,
		);

		$result = $this->invoke_protected_method( 'get_itemized_tax_rates', array( $taxes, $taxjar_taxes, $options ) );

		$this->assertArrayHasKey( '351-regressionkey', $result['rate_ids'] );

		$persisted = array();
		foreach ( $result['rate_ids']['351-regressionkey'] as $rate_id ) {
			$rate        = WC_Tax::_get_tax_rate( $rate_id );
			$persisted[] = (float) $rate['tax_rate'];
		}
		sort( $persisted );

		// One row per jurisdiction component (city, county, state, special district).
		$this->assertCount( 4, $persisted );
		$this->assertEqualsWithDelta( 0.0, $persisted[0], 0.0001, 'Special district rate.' );
		$this->assertEqualsWithDelta( 1.25, $persisted[1], 0.0001, 'County rate.' );
		$this->assertEqualsWithDelta( 1.5, $persisted[2], 0.0001, 'City rate.' );
		$this->assertEqualsWithDelta( 4.225, $persisted[3], 0.0001, 'State rate.' );

		// The combined persisted rate must equal the real 6.975%, never the zeroed
		// top-level rate. This is the core guard against the zeroing-out regression.
		$this->assertEqualsWithDelta( 6.975, array_sum( $persisted ), 0.0001, 'Existing tax rate must not be zeroed out by a $0 calculation.' );
	}
}
