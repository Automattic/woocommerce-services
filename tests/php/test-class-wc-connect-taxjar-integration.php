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
	 * Closure registered on `taxjar_store_settings` by the local-pickup helper.
	 *
	 * @var Closure|null
	 */
	private $store_settings_filter;

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
		remove_all_filters( 'woocommerce_services_override_tax_rate' );

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
	 * Test that the preserve_order_taxes_on_recalculation hook is registered after init().
	 */
	public function test_preserve_order_taxes_hook_registered_after_init() {
		// Arrange: enable automated taxes option so init() does not bail early.
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'yes' );
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Act.
		$this->integration->init();

		// Assert.
		$this->assertNotFalse(
			has_action( 'woocommerce_order_before_calculate_taxes', array( $this->integration, 'preserve_order_taxes_on_recalculation' ) ),
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
	 * A `taxjar_store_settings` callback returning a non-array must not fatal.
	 *
	 * The filter is public and unguarded. Before `get_store_settings()` enforced its own
	 * documented `@return array`, a callback returning null / false / a string reached
	 * `Address::from_store_settings( array $settings )` and raised an uncaught TypeError
	 * on every cart and checkout render resolving a base address. The unfiltered store
	 * address is used instead.
	 *
	 * @dataProvider provider_non_array_store_settings
	 *
	 * @param mixed $returned Value the filter callback returns.
	 */
	public function test_get_taxable_address_survives_non_array_store_settings( $returned ) {
		$callback = static function () use ( $returned ) {
			return $returned;
		};
		add_filter( 'taxjar_store_settings', $callback );

		$store_country = WC()->countries->get_base_country();
		$store_state   = WC()->countries->get_base_state();

		try {
			$address = $this->invoke_protected_method( 'get_taxable_address', array( 'base' ) );
		} finally {
			remove_filter( 'taxjar_store_settings', $callback );
		}

		$this->assertIsArray( $address );
		$this->assertEquals( $store_country, $address[0] );
		$this->assertEquals( $store_state, $address[1] );
	}

	/**
	 * Non-array return values a `taxjar_store_settings` callback might produce.
	 *
	 * @return array<string, array{0: mixed}>
	 */
	public function provider_non_array_store_settings() {
		return array(
			'null'   => array( null ),
			'false'  => array( false ),
			'string' => array( 'not-an-address' ),
			'object' => array( new stdClass() ),
		);
	}

	/**
	 * A `taxjar_store_settings` callback returning a partial array must not drop keys.
	 *
	 * Callers index the result directly, so a missing key used to raise undefined
	 * key warnings and feed null into strtoupper(). Missing keys are backfilled
	 * from the unfiltered store settings; keys the callback did set still win.
	 */
	public function test_get_store_settings_backfills_partial_array() {
		$callback = static function () {
			return array( 'postcode' => '90210' );
		};
		add_filter( 'taxjar_store_settings', $callback );

		try {
			$settings = $this->integration->get_store_settings();
		} finally {
			remove_filter( 'taxjar_store_settings', $callback );
		}

		$this->assertSame( '90210', $settings['postcode'] );

		foreach ( array( 'street', 'city', 'state', 'country' ) as $key ) {
			$this->assertArrayHasKey( $key, $settings );
		}

		$this->assertSame( WC()->countries->get_base_country(), $settings['country'] );
		$this->assertSame( WC()->countries->get_base_city(), $settings['city'] );
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
	 * Test that preserve_order_taxes_on_recalculation skips when the cart/checkout
	 * flow already populated response_rate_ids.
	 */
	public function test_preserve_order_taxes_skips_when_response_rate_ids_populated() {
		$this->set_private_property( 'response_rate_ids', array( 'product-key' => array( 1, 2 ) ) );

		$order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->getMock();

		// The first gate returns before the order is inspected.
		$order->expects( $this->never() )->method( 'get_id' );

		$this->integration->preserve_order_taxes_on_recalculation( array(), $order );
	}

	/**
	 * Test that preserve_order_taxes_on_recalculation skips admin AJAX recalculations,
	 * which are handled by calculate_backend_totals().
	 */
	public function test_preserve_order_taxes_skips_when_doing_ajax() {
		// Use a filter instead of define() so the constant does not leak into other tests.
		add_filter( 'wp_doing_ajax', '__return_true' );
		$this->set_private_property( 'response_rate_ids', array() );

		$order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_id' ) )
			->getMock();

		// The AJAX gate returns before the order id is inspected.
		$order->expects( $this->never() )->method( 'get_id' );

		$this->integration->preserve_order_taxes_on_recalculation( array(), $order );

		remove_filter( 'wp_doing_ajax', '__return_true' );
	}

	/**
	 * Test that preserve_order_taxes_on_recalculation skips a new order (id 0) and
	 * does not register a restore callback.
	 */
	public function test_preserve_order_taxes_skips_when_order_id_is_zero() {
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$this->set_private_property( 'response_rate_ids', array() );

		$order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_id' ) )
			->getMock();
		$order->method( 'get_id' )->willReturn( 0 );

		$this->integration->preserve_order_taxes_on_recalculation( array(), $order );

		$this->assertFalse( has_action( 'woocommerce_order_after_calculate_totals' ) );
	}

	/**
	 * Test that preserve_order_taxes_on_recalculation does not register a restore when
	 * the order has no existing tax lines, so a first-time calculation runs normally.
	 */
	public function test_preserve_order_taxes_skips_when_no_existing_tax_lines() {
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$this->set_private_property( 'response_rate_ids', array() );

		$order = wc_create_order();

		$this->integration->preserve_order_taxes_on_recalculation( array(), $order );

		$this->assertFalse(
			has_action( 'woocommerce_order_after_calculate_totals' ),
			'No restore should be registered for an order without existing tax lines.'
		);

		$order->delete( true );
	}

	/**
	 * Test that preserve_order_taxes_on_recalculation registers a restore callback when
	 * the order already has tax lines.
	 */
	public function test_preserve_order_taxes_registers_restore_when_order_has_taxes() {
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$this->set_private_property( 'response_rate_ids', array() );

		$order    = wc_create_order();
		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_tax_total( '1.76' );
		$order->add_item( $tax_item );
		$order->save();

		$this->integration->preserve_order_taxes_on_recalculation( array(), $order );

		$this->assertGreaterThan(
			0,
			has_action( 'woocommerce_order_after_calculate_totals' ),
			'A restore should be registered for an order that already has tax lines.'
		);

		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$order->delete( true );
	}

	// -------------------------------------------------------------------------
	// snapshot_order_taxes() and restore_order_taxes() tests
	// -------------------------------------------------------------------------

	/**
	 * Test that snapshot_order_taxes captures the full tax-line metadata, per-item
	 * taxes, and order-level tax totals.
	 */
	public function test_snapshot_order_taxes_captures_full_tax_state() {
		$order = wc_create_order();

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_code( 'US-CA-TAX-1' );
		$tax_item->set_rate_id( 6 );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_rate_percent( 8.25 );
		$tax_item->set_compound( false );
		$tax_item->set_tax_total( '1.76' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '1.76' );
		$order->save();

		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );

		$this->assertArrayHasKey( 'tax_lines', $snapshot );
		$this->assertArrayHasKey( 'item_taxes', $snapshot );
		$this->assertEquals( '1.76', $snapshot['cart_tax'] );
		$this->assertCount( 1, $snapshot['tax_lines'] );

		$saved = reset( $snapshot['tax_lines'] );
		$this->assertEquals( 6, $saved['rate_id'] );
		$this->assertEquals( 'US-CA-TAX-1', $saved['rate_code'] );
		$this->assertEquals( 'CA Tax', $saved['label'] );
		$this->assertEquals( 8.25, $saved['rate_percent'] );
		$this->assertEquals( '1.76', $saved['tax_total'] );

		$order->delete( true );
	}

	/**
	 * Test that restore_order_taxes re-adds tax lines with their full metadata and
	 * keeps the order tax totals in sync.
	 */
	public function test_restore_order_taxes_restores_tax_lines_with_metadata() {
		$order = wc_create_order();

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_code( 'US-CA-TAX-1' );
		$tax_item->set_rate_id( 6 );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_rate_percent( 8.25 );
		$tax_item->set_tax_total( '1.76' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '1.76' );
		$order->set_total( 1.76 );
		$order->save();

		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );

		// Simulate WC wiping the taxes for a changed address.
		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->set_cart_tax( 0 );
		$order->set_total( 0 );
		$order->save();
		$this->assertCount( 0, $order->get_taxes() );

		$this->invoke_protected_method( 'restore_order_taxes', array( $order, $snapshot ) );

		$restored = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $restored->get_taxes() );

		$taxes = $restored->get_taxes();
		$line  = reset( $taxes );
		$this->assertEquals( 6, $line->get_rate_id() );
		$this->assertEquals( 'US-CA-TAX-1', $line->get_rate_code() );
		$this->assertEquals( 'CA Tax', $line->get_label() );
		$this->assertEquals( '1.76', $line->get_tax_total() );
		$this->assertEqualsWithDelta( 1.76, (float) $restored->get_total_tax(), 0.001 );

		$order->delete( true );
	}

	// -------------------------------------------------------------------------
	// End-to-end preservation test
	// -------------------------------------------------------------------------

	/**
	 * End-to-end: a recalculation triggered by an address change on an existing order
	 * preserves the recorded taxes instead of wiping them to zero, and rebases the
	 * order total on the preserved tax.
	 */
	public function test_preserve_order_taxes_end_to_end_on_address_change() {
		remove_all_actions( 'woocommerce_order_before_calculate_taxes' );
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$this->set_private_property( 'response_rate_ids', array() );

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( '100' );
		$this->product->save();

		$order   = wc_create_order();
		$item_id = $order->add_product( $this->product, 1 );
		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '90210' );

		// Record a TaxJar-style tax of 8.25% on the line item and the order.
		$line_item = $order->get_item( $item_id );
		$line_item->set_taxes(
			array(
				'total'    => array( 6 => '8.25' ),
				'subtotal' => array( 6 => '8.25' ),
			)
		);

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_rate_code( 'US-CA-TAX-1' );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_rate_percent( 8.25 );
		$tax_item->set_tax_total( '8.25' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '8.25' );
		$order->set_total( 108.25 );
		$order->save();

		$this->assertEqualsWithDelta( 8.25, (float) $order->get_total_tax(), 0.001 );

		// Recalculate as a REST/programmatic update would after an address change.
		add_action( 'woocommerce_order_before_calculate_taxes', array( $this->integration, 'preserve_order_taxes_on_recalculation' ), 10, 2 );
		$order->set_shipping_postcode( '90211' );
		$order->calculate_totals( true );

		$reloaded = wc_get_order( $order->get_id() );

		// The recorded tax is preserved rather than wiped to zero.
		$this->assertEqualsWithDelta( 8.25, (float) $reloaded->get_total_tax(), 0.001, 'Recorded tax should be preserved.' );
		$this->assertCount( 1, $reloaded->get_taxes(), 'The original tax line should be preserved.' );

		$taxes    = $reloaded->get_taxes();
		$restored = reset( $taxes );
		$this->assertEquals( 'CA Tax', $restored->get_label(), 'Tax label should survive the recalculation.' );
		$this->assertEquals( 6, $restored->get_rate_id() );

		// The total reflects the preserved tax on top of the (unchanged) line total.
		$this->assertEqualsWithDelta( 108.25, (float) $reloaded->get_total(), 0.01, 'Order total should include the preserved tax.' );

		remove_all_actions( 'woocommerce_order_before_calculate_taxes' );
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$order->delete( true );
	}

	/**
	 * End-to-end: when a recalculation also changes the shipping amount, the
	 * preserved tax is rebased onto the new non-tax total (so the total reflects the
	 * updated shipping while the recorded tax is kept).
	 */
	public function test_preserve_order_taxes_rebases_total_when_shipping_changes() {
		remove_all_actions( 'woocommerce_order_before_calculate_taxes' );
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$this->set_private_property( 'response_rate_ids', array() );

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( '100' );
		$this->product->save();

		$order   = wc_create_order();
		$item_id = $order->add_product( $this->product, 1 );

		$shipping = new WC_Order_Item_Shipping();
		$shipping->set_method_title( 'Flat rate' );
		$shipping->set_total( '10' );
		$order->add_item( $shipping );

		$order->set_shipping_country( 'US' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '90210' );

		// Record 8.25% tax on the $100 product (shipping not taxed).
		$line_item = $order->get_item( $item_id );
		$line_item->set_taxes(
			array(
				'total'    => array( 6 => '8.25' ),
				'subtotal' => array( 6 => '8.25' ),
			)
		);

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_tax_total( '8.25' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '8.25' );
		$order->set_shipping_total( '10' );
		$order->set_total( 118.25 );
		$order->save();

		// Recalculate with a changed shipping amount ($10 -> $25) and address.
		add_action( 'woocommerce_order_before_calculate_taxes', array( $this->integration, 'preserve_order_taxes_on_recalculation' ), 10, 2 );
		$order->set_shipping_postcode( '90211' );
		foreach ( $order->get_shipping_methods() as $ship ) {
			$ship->set_total( '25' );
			$order->add_item( $ship );
		}
		$order->calculate_totals( true );

		$reloaded = wc_get_order( $order->get_id() );

		// Tax preserved, total rebased on the new $25 shipping: 100 + 25 + 8.25.
		$this->assertEqualsWithDelta( 8.25, (float) $reloaded->get_total_tax(), 0.001, 'Recorded tax should be preserved.' );
		$this->assertEqualsWithDelta( 133.25, (float) $reloaded->get_total(), 0.01, 'Total should use the new shipping amount plus the preserved tax.' );

		remove_all_actions( 'woocommerce_order_before_calculate_taxes' );
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$order->delete( true );
	}

	/**
	 * Test that snapshot/restore round-trips every tax line when an order has more than one.
	 */
	public function test_restore_order_taxes_restores_multiple_tax_lines() {
		$order = wc_create_order();

		$state_tax = new WC_Order_Item_Tax();
		$state_tax->set_rate_id( 6 );
		$state_tax->set_rate_code( 'US-CA-STATE-1' );
		$state_tax->set_label( 'CA State Tax' );
		$state_tax->set_rate_percent( 6.0 );
		$state_tax->set_tax_total( '6.00' );
		$state_tax->set_shipping_tax_total( '0.00' );
		$order->add_item( $state_tax );

		$county_tax = new WC_Order_Item_Tax();
		$county_tax->set_rate_id( 7 );
		$county_tax->set_rate_code( 'US-CA-COUNTY-1' );
		$county_tax->set_label( 'LA County Tax' );
		$county_tax->set_rate_percent( 2.25 );
		$county_tax->set_tax_total( '2.25' );
		$county_tax->set_shipping_tax_total( '0.00' );
		$order->add_item( $county_tax );

		$order->set_cart_tax( '8.25' );
		$order->set_total( 8.25 );
		$order->save();

		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );
		$this->assertCount( 2, $snapshot['tax_lines'], 'Both tax lines should be snapshotted.' );

		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->set_cart_tax( 0 );
		$order->set_total( 0 );
		$order->save();

		$this->invoke_protected_method( 'restore_order_taxes', array( $order, $snapshot ) );

		$restored = wc_get_order( $order->get_id() );
		$this->assertCount( 2, $restored->get_taxes(), 'Both tax lines should be restored.' );

		$labels = array();
		foreach ( $restored->get_taxes() as $line ) {
			$labels[ $line->get_rate_id() ] = $line->get_label();
		}
		$this->assertEquals( 'CA State Tax', $labels[6], 'State tax label should be restored.' );
		$this->assertEquals( 'LA County Tax', $labels[7], 'County tax label should be restored.' );
		$this->assertEqualsWithDelta( 8.25, (float) $restored->get_total_tax(), 0.001, 'Combined tax should be restored.' );

		$order->delete( true );
	}

	/**
	 * Test that a compound tax line's compound flag survives snapshot and restore.
	 */
	public function test_restore_order_taxes_preserves_compound_flag() {
		$order = wc_create_order();

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 8 );
		$tax_item->set_rate_code( 'CA-GST-1' );
		$tax_item->set_label( 'GST' );
		$tax_item->set_rate_percent( 5.0 );
		$tax_item->set_compound( true );
		$tax_item->set_tax_total( '5.00' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '5.00' );
		$order->set_total( 5.00 );
		$order->save();

		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );

		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->save();

		$this->invoke_protected_method( 'restore_order_taxes', array( $order, $snapshot ) );

		$restored = wc_get_order( $order->get_id() );
		$taxes    = $restored->get_taxes();
		$line     = reset( $taxes );
		$this->assertTrue( $line->get_compound(), 'Compound flag should survive snapshot and restore.' );

		$order->delete( true );
	}

	/**
	 * Test that the order-level discount tax total is restored from the snapshot.
	 */
	public function test_restore_order_taxes_restores_discount_tax() {
		$order = wc_create_order();

		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_tax_total( '1.76' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '1.76' );
		$order->set_discount_tax( '0.50' );
		$order->set_total( 1.76 );
		$order->save();

		$snapshot = $this->invoke_protected_method( 'snapshot_order_taxes', array( $order ) );
		$this->assertEqualsWithDelta( 0.50, (float) $snapshot['discount_tax'], 0.001, 'Discount tax should be snapshotted.' );

		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->set_discount_tax( 0 );
		$order->save();

		$this->invoke_protected_method( 'restore_order_taxes', array( $order, $snapshot ) );

		$restored = wc_get_order( $order->get_id() );
		$this->assertEqualsWithDelta( 0.50, (float) $restored->get_discount_tax(), 0.001, 'Discount tax should be restored.' );

		$order->delete( true );
	}

	/**
	 * Test that a bare calculate_taxes() leaves the snapshot pending (documented gap:
	 * no restore fires on that hook), and that the pending snapshot is then restored by
	 * the handler a following calculate_totals() invokes, without double-applying.
	 */
	public function test_preserve_order_taxes_pending_snapshot_restores_via_handler() {
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		$this->set_private_property( 'response_rate_ids', array() );

		$order    = wc_create_order();
		$tax_item = new WC_Order_Item_Tax();
		$tax_item->set_rate_id( 6 );
		$tax_item->set_label( 'CA Tax' );
		$tax_item->set_tax_total( '8.25' );
		$tax_item->set_shipping_tax_total( '0.00' );
		$order->add_item( $tax_item );
		$order->set_cart_tax( '8.25' );
		$order->set_total( 8.25 );
		$order->save();

		$this->integration->preserve_order_taxes_on_recalculation( array(), $order );

		$this->assertNotFalse(
			has_action( 'woocommerce_order_after_calculate_totals', array( $this->integration, 'restore_order_taxes_after_recalculation' ) ),
			'A single restore handler should be registered.'
		);

		// Simulate WC wiping the tax lines during the recalculation.
		foreach ( $order->get_taxes() as $t ) {
			$order->remove_item( $t->get_id() );
		}
		$order->set_cart_tax( 0 );
		$order->set_total( 0 );
		$order->save();

		$this->integration->restore_order_taxes_after_recalculation( true, $order );

		$restored = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $restored->get_taxes(), 'The recorded tax line should be restored.' );
		$this->assertEqualsWithDelta( 8.25, (float) $restored->get_total_tax(), 0.001, 'The recorded tax should be restored.' );

		// The snapshot is cleared once restored, so a second handler call is a no-op and
		// cannot duplicate the tax or leak into a later recalculation.
		$this->integration->restore_order_taxes_after_recalculation( true, wc_get_order( $order->get_id() ) );
		$again = wc_get_order( $order->get_id() );
		$this->assertCount( 1, $again->get_taxes(), 'Restoring twice must not duplicate tax lines.' );

		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
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
		$line_item    = (object) array(
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

	/**
	 * `normalize_city()` strips semicolons and collapses whitespace.
	 *
	 * `WC_Tax::_update_tax_rate_cities()` treats `;` as a multi-city separator,
	 * but `WC_Tax::find_rates()` treats it as a literal character. `normalize_city()`
	 * strips `;` (and collapses whitespace) so the round-trip stays symmetric.
	 *
	 * @see WOOTAX-19
	 *
	 * @dataProvider normalize_city_provider
	 *
	 * @param string $input    Raw city value to normalize.
	 * @param string $expected Expected normalized output.
	 */
	public function test_normalize_city_strips_semicolons_and_normalizes_whitespace( $input, $expected ) {
		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'normalize_city' );
		$reflection->setAccessible( true );

		$this->assertSame( $expected, $reflection->invoke( null, $input ) );
	}

	/**
	 * The deprecated `normalize_city()` agrees with the value object it delegates to.
	 *
	 * `WC_Connect_TaxJar_Integration::normalize_city()` is `protected static`, so an
	 * out-of-repo subclass may call it. It is deprecated in favour of
	 * `Address::normalize_city()` but deliberately retained, and this pins the two
	 * to the same answer so the deprecation cannot silently fork behaviour.
	 *
	 * String inputs must match exactly. Non-string inputs are the one deliberate
	 * difference: the deprecated method keeps returning them untouched, while the
	 * value object's signature does not accept them at all.
	 *
	 * @dataProvider normalize_city_provider
	 *
	 * @param mixed $input    Raw city value to normalize.
	 * @param mixed $expected Expected normalized output.
	 */
	public function test_deprecated_normalize_city_delegates_to_address_value_object( $input, $expected ) {
		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'normalize_city' );
		$reflection->setAccessible( true );

		$actual = $reflection->invoke( null, $input );

		if ( ! is_string( $input ) ) {
			$this->assertSame( $expected, $actual, 'Non-string input must be returned untouched.' );
			return;
		}

		$this->assertSame( \Automattic\WCServices\Tax\Address::normalize_city( $input ), $actual );
	}

	/**
	 * Data provider for `test_normalize_city_strips_semicolons_and_normalizes_whitespace`.
	 *
	 * @return array<string, array{0: mixed, 1: mixed}>
	 */
	public function normalize_city_provider() {
		return array(
			'no semicolon — unchanged'            => array( 'New York', 'New York' ),
			'simple semicolon between words'      => array( 'Casse;Berry', 'Casse Berry' ),
			'semicolon with following space'      => array( 'Casse; Berry', 'Casse Berry' ),
			'leading semicolon'                   => array( ';Casselberry', 'Casselberry' ),
			'trailing semicolon'                  => array( 'Casselberry;', 'Casselberry' ),
			'consecutive semicolons'              => array( 'Casse;;Berry', 'Casse Berry' ),
			'wrapped in whitespace'               => array( '  Casselberry  ', 'Casselberry' ),
			'tab and newline collapse to space'   => array( "Casse;\t\nBerry", 'Casse Berry' ),
			'empty string'                        => array( '', '' ),
			'multi-segment with mixed separators' => array( ' Casse; ;Berry ', 'Casse Berry' ),
			'null — returned unchanged'           => array( null, null ),
			'false — returned unchanged'          => array( false, false ),
		);
	}

	/**
	 * `get_backend_address()` strips a semicolon from an admin order city.
	 *
	 * The admin "Recalculate" path builds its taxable address from `$_POST`, so a
	 * `;`-bearing city must be normalized there too — otherwise backend recalculations
	 * would reintroduce the stored/looked-up asymmetry the frontend path now avoids.
	 *
	 * @see WOOTAX-19
	 */
	public function test_get_backend_address_normalizes_semicolon_city() {
		$_POST['country']  = 'US';
		$_POST['state']    = 'FL';
		$_POST['postcode'] = '33033';
		$_POST['city']     = 'Casse;Berry';

		try {
			$address = $this->invoke_protected_method( 'get_backend_address' );
		} finally {
			unset( $_POST['country'], $_POST['state'], $_POST['postcode'], $_POST['city'] );
		}

		$this->assertStringNotContainsString( ';', $address['to_city'], 'Backend order city must not retain a semicolon — `_update_tax_rate_cities()` would split it.' );
		$this->assertSame( 'CASSE BERRY', $address['to_city'] );
	}

	/**
	 * `create_or_update_tax_rate()` is idempotent across semicolon-bearing cities.
	 *
	 * Regression test for the unbounded `wp_woocommerce_tax_rates` growth:
	 * `create_or_update_tax_rate()` called twice with the same semicolon-bearing
	 * city must reuse the existing rate row instead of inserting a duplicate.
	 *
	 * @see WOOTAX-19
	 */
	public function test_create_or_update_tax_rate_does_not_duplicate_rows_for_semicolon_city() {
		global $wpdb;

		$location = array(
			'to_country' => 'US',
			'to_state'   => 'FL',
			'to_zip'     => '33033',
			'to_city'    => 'Casse;Berry',
			'from_state' => 'FL',
		);

		// Snapshot the row count BEFORE the first call so the test isn't sensitive
		// to fixtures/seed data (test DB might already have rates from other tests).
		$rates_table        = $wpdb->prefix . 'woocommerce_tax_rates';
		$initial_rate_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rates_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$first_id  = $this->integration->create_or_update_tax_rate( $location, 0.07, '', 1, 1, 'Tax' );
		$second_id = $this->integration->create_or_update_tax_rate( $location, 0.07, '', 1, 1, 'Tax' );

		// Same row id on both calls — find_rates() matched the second time.
		$this->assertSame( (int) $first_id, (int) $second_id, 'Second create_or_update_tax_rate() inserted a new row instead of reusing the existing one — find_rates() city lookup is asymmetric with _update_tax_rate_cities() storage.' );

		// Exactly one new row added, not two.
		$final_rate_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rates_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( $initial_rate_count + 1, $final_rate_count, 'Expected exactly one new tax rate row after two create_or_update_tax_rate() calls with the same Casse;Berry city.' );

		// Stored city in the locations table should be normalized — no `;`.
		$locations_table = $wpdb->prefix . 'woocommerce_tax_rate_locations';
		$stored_cities   = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$locations_table} WHERE tax_rate_id = %d AND location_type = %s", (int) $first_id, 'city' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertNotEmpty( $stored_cities );
		foreach ( $stored_cities as $city ) {
			$this->assertStringNotContainsString( ';', $city, 'Tax rate city stored with a semicolon — `_update_tax_rate_cities()` will split it and break find_rates() on subsequent lookups.' );
		}
	}

	/**
	 * Build a well-formed TaxJar tax response object (as returned under
	 * $taxjar_response->tax) with a base rate of 0.08.
	 *
	 * @return object
	 */
	private function build_taxjar_tax_response() {
		return (object) array(
			'rate'      => 0.08,
			'breakdown' => (object) array(
				'combined_tax_rate' => 0.08,
				'country_tax_rate'  => 0.0,
				'shipping'          => (object) array(
					'combined_tax_rate' => 0.08,
					'country_tax_rate'  => 0.0,
				),
				'line_items'        => array(
					(object) array(
						'combined_tax_rate'       => 0.08,
						'country_tax_rate'        => 0.0,
						'country_taxable_amount'  => 100.0,
						'taxable_amount'          => 100.0,
						'country_tax_collectable' => 0.0,
						'tax_collectable'         => 8.0,
					),
				),
			),
		);
	}

	/**
	 * The override filter should still rewrite every rate on a well-formed
	 * response (behavior preserved).
	 */
	public function test_maybe_override_taxjar_tax_applies_override_to_wellformed_response() {
		add_filter( 'woocommerce_services_override_tax_rate', '__return_zero' );

		$resp   = $this->build_taxjar_tax_response();
		$result = $this->integration->maybe_override_taxjar_tax( $resp, array() );

		$this->assertSame( 0.0, $result->rate );
		$this->assertSame( 0.0, $result->breakdown->combined_tax_rate );
		$this->assertSame( 0.0, $result->breakdown->country_tax_rate );
		$this->assertSame( 0.0, $result->breakdown->shipping->combined_tax_rate );
		$this->assertSame( 0.0, $result->breakdown->shipping->country_tax_rate );

		$line_item = $result->breakdown->line_items[0];
		$this->assertSame( 0.0, $line_item->combined_tax_rate );
		$this->assertSame( 0.0, $line_item->country_tax_rate );
		$this->assertSame( 0.0, $line_item->country_tax_collectable );
		$this->assertSame( 0.0, $line_item->tax_collectable );
	}

	/**
	 * A null (or otherwise non-object) line item must not fatal; it is left
	 * untouched while valid line items are still overridden. Regression for the
	 * reported "Attempt to assign property on null" fatal (WOOTAX-74).
	 */
	public function test_maybe_override_taxjar_tax_survives_null_line_item() {
		add_filter( 'woocommerce_services_override_tax_rate', '__return_zero' );

		$resp                        = $this->build_taxjar_tax_response();
		$valid_line_item             = $resp->breakdown->line_items[0];
		$resp->breakdown->line_items = array( null, $valid_line_item );

		$result = $this->integration->maybe_override_taxjar_tax( $resp, array() );

		$this->assertSame( 0.0, $result->rate );
		$this->assertNull( $result->breakdown->line_items[0] );
		$this->assertSame( 0.0, $result->breakdown->line_items[1]->combined_tax_rate );
	}

	/**
	 * A response with no breakdown must not fatal. Regression for WOOTAX-74.
	 */
	public function test_maybe_override_taxjar_tax_survives_missing_breakdown() {
		add_filter( 'woocommerce_services_override_tax_rate', '__return_zero' );

		$resp = (object) array( 'rate' => 0.08 );

		$result = $this->integration->maybe_override_taxjar_tax( $resp, array() );

		$this->assertSame( 0.0, $result->rate );
		$this->assertObjectNotHasProperty( 'breakdown', $result );
	}

	/**
	 * A breakdown missing its shipping member must not fatal. Regression for WOOTAX-74.
	 */
	public function test_maybe_override_taxjar_tax_survives_missing_shipping() {
		add_filter( 'woocommerce_services_override_tax_rate', '__return_zero' );

		$resp = $this->build_taxjar_tax_response();
		unset( $resp->breakdown->shipping );

		$result = $this->integration->maybe_override_taxjar_tax( $resp, array() );

		$this->assertSame( 0.0, $result->rate );
		$this->assertSame( 0.0, $result->breakdown->combined_tax_rate );
	}

	/**
	 * With no override filter registered the response is returned unchanged.
	 */
	public function test_maybe_override_taxjar_tax_returns_unchanged_without_override() {
		$resp = $this->build_taxjar_tax_response();

		$result = $this->integration->maybe_override_taxjar_tax( $resp, array() );

		// The method mutates and returns the same handle, so identity alone proves
		// nothing — assert the individual fields are untouched at every level.
		$this->assertSame( $resp, $result );
		$this->assertSame( 0.08, $result->rate );
		$this->assertSame( 0.08, $result->breakdown->combined_tax_rate );
		$this->assertSame( 0.08, $result->breakdown->shipping->combined_tax_rate );
		$this->assertSame( 0.08, $result->breakdown->line_items[0]->combined_tax_rate );
	}

	/**
	 * A non-object tax node is returned unchanged rather than fataling.
	 *
	 * The override filter is what makes this reachable: with a filter that leaves
	 * the rate at 0 the method early-returns anyway, so only a filter returning a
	 * *different* rate drives execution as far as the `->rate` write. calculate_tax()
	 * validates the tax node so this cannot happen in-plugin, but the method is
	 * public and third-party callers are not bound by that guarantee.
	 *
	 * @dataProvider provide_non_object_tax_nodes
	 *
	 * @param mixed $tax_node Non-object value passed in place of the tax node.
	 */
	public function test_maybe_override_taxjar_tax_returns_non_object_unchanged( $tax_node ) {
		add_filter(
			'woocommerce_services_override_tax_rate',
			function () {
				return 0.15;
			}
		);

		$this->assertSame( $tax_node, $this->integration->maybe_override_taxjar_tax( $tax_node, array() ) );
	}

	/**
	 * Non-object values a third-party caller could pass as the tax node.
	 *
	 * @return array
	 */
	public function provide_non_object_tax_nodes() {
		return array(
			'null'   => array( null ),
			'false'  => array( false ),
			'array'  => array( array( 'rate' => 0.08 ) ),
			'string' => array( 'not a tax object' ),
		);
	}

	// -------------------------------------------------------------------------
	// Read seam — address tuple arity, empty-value representation, slashing
	// -------------------------------------------------------------------------

	/**
	 * Force the local-pickup branch of `append_base_address_to_customer_taxable_address()`
	 * and give the store a non-empty street.
	 *
	 * @param string $street Store street to report through `taxjar_store_settings`.
	 * @return void
	 */
	private function force_local_pickup_with_store_street( $street ) {
		WC()->session->set( 'chosen_shipping_methods', array( 'local_pickup:1' ) );

		$this->store_settings_filter = function ( $settings ) use ( $street ) {
			$settings['street']   = $street;
			$settings['city']     = 'Homestead';
			$settings['postcode'] = '33033';
			return $settings;
		};
		add_filter( 'taxjar_store_settings', $this->store_settings_filter );
	}

	/**
	 * Undo `force_local_pickup_with_store_street()`.
	 *
	 * @return void
	 */
	private function reset_local_pickup_with_store_street() {
		WC()->session->set( 'chosen_shipping_methods', array() );

		if ( $this->store_settings_filter ) {
			remove_filter( 'taxjar_store_settings', $this->store_settings_filter );
			$this->store_settings_filter = null;
		}
	}

	/**
	 * `append_base_address_to_customer_taxable_address()` must not change the length
	 * of the tuple it was handed.
	 *
	 * WooCommerce core fires `woocommerce_customer_taxable_address` with a **four**
	 * element tuple (`WC_Customer::get_taxable_address()` — no street), and
	 * `WC_Tax::get_rates_from_location()` gates on `count( $location ) === 4` with a
	 * strict comparison. Returning five elements there makes core skip
	 * `WC_Tax::find_rates()` entirely and hand back an empty rate set.
	 *
	 * That is exactly what happens today on a local-pickup checkout: the callback
	 * substitutes the store street into the empty slot, which flips the return from
	 * four elements to five. The plugin's own `allow_street_address_for_matched_rates()`
	 * override happens to paper over it for `woocommerce_matched_rates` consumers, but
	 * every other caller of `WC_Tax::get_rates()` sees zero rates.
	 */
	public function test_append_base_address_preserves_four_element_core_tuple() {
		$this->force_local_pickup_with_store_street( '1 Store Street' );

		try {
			$address = $this->integration->append_base_address_to_customer_taxable_address(
				array( 'US', 'FL', '33030', 'Miami' )
			);
		} finally {
			$this->reset_local_pickup_with_store_street();
		}

		$this->assertCount(
			4,
			$address,
			'WC_Tax::get_rates_from_location() requires exactly 4 elements; returning 5 makes core return an empty rate set.'
		);

		/*
		 * The arity assertion above only means something while the local-pickup base
		 * substitution actually runs. If it stops running — a session not started under
		 * a different bootstrap, another test leaving `woocommerce_apply_base_tax_for_local_pickup`
		 * filtered false, a change in `wc_get_chosen_shipping_method_ids()` semantics —
		 * then `$street` stays empty, and the pre-fix implementation returned four
		 * elements too. The test would keep passing against the very defect it exists to
		 * catch.
		 *
		 * Asserting the substituted values makes that failure loud instead of silent: the
		 * store postcode and city (33033 / HOMESTEAD) are observable proof the branch was
		 * taken, and neither matches the customer values fed in (33030 / Miami).
		 */
		$this->assertSame(
			array( 'US', 'FL', '33033', 'HOMESTEAD' ),
			$address,
			'The local-pickup base substitution did not run, so the arity assertion above is vacuous.'
		);
	}

	/**
	 * The mirror of the above: a five-element tuple in must stay five elements out.
	 *
	 * `WC_Connect_TaxJar_Integration::get_taxable_address()` fires the same filter with
	 * a five-element tuple and `get_address()` reads index `[4]` off the result. Dropping
	 * the trailing element whenever the street is empty means the plugin's own contract
	 * silently changes arity based on the data, which is what allows a populated street to
	 * be lost when the tuple is rebuilt downstream.
	 */
	public function test_append_base_address_preserves_five_element_plugin_tuple() {
		$address = $this->integration->append_base_address_to_customer_taxable_address(
			array( 'US', 'FL', '33030', 'Miami', '' )
		);

		$this->assertCount( 5, $address, 'A 5-tuple in must stay a 5-tuple out — get_address() reads index [4].' );
		$this->assertSame( '', $address[4] );
	}

	/**
	 * Without local pickup, core's four-element tuple passes through unchanged.
	 *
	 * This is the most common core path: no substitution runs and the callback
	 * must behave as a no-op.
	 */
	public function test_append_base_address_passes_through_core_tuple_without_local_pickup() {
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );

		$address = $this->integration->append_base_address_to_customer_taxable_address(
			array( 'US', 'FL', '33030', 'Miami' )
		);

		$this->assertSame( array( 'US', 'FL', '33030', 'Miami' ), $address );
	}

	/**
	 * A malformed short tuple is padded up to the four fields every consumer reads.
	 */
	public function test_append_base_address_pads_short_tuple_to_four_elements() {
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );

		$address = $this->integration->append_base_address_to_customer_taxable_address(
			array( 'US', 'FL', '33030' )
		);

		$this->assertSame( array( 'US', 'FL', '33030', '' ), $address );
	}

	/**
	 * Anything past the street slot is dropped: the output is clamped to five
	 * elements even when an earlier callback appended a sixth.
	 */
	public function test_append_base_address_clamps_long_tuple_to_five_elements() {
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate:1' ) );

		$address = $this->integration->append_base_address_to_customer_taxable_address(
			array( 'US', 'FL', '33030', 'Miami', '123 Ocean Drive', 'extra' )
		);

		$this->assertSame( array( 'US', 'FL', '33030', 'Miami', '123 Ocean Drive' ), $address );
	}

	/**
	 * A populated street survives the round trip untouched.
	 *
	 * Note: this is a behavior pin, not one of the defect reproductions. The
	 * pre-fix code also passed it, since a populated street already returned a
	 * five-element tuple with the street intact.
	 */
	public function test_append_base_address_keeps_populated_street() {
		$address = $this->integration->append_base_address_to_customer_taxable_address(
			array( 'US', 'FL', '33030', 'Miami', '123 Ocean Drive' )
		);

		$this->assertCount( 5, $address );
		$this->assertSame( '123 Ocean Drive', $address[4] );
	}

	/**
	 * `get_address()` and `get_backend_address()` must represent an empty field the
	 * same way.
	 *
	 * Both feed `calculate_tax()`, which builds the TaxJar request body straight from
	 * these values — so the two paths currently put different JSON on the wire for the
	 * same empty input: the cart path sends `false`, the admin-recalculate path sends
	 * `""`. `Address::to_legacy_options()` is the single source of that decision.
	 */
	public function test_read_seam_uses_one_empty_value_representation() {
		// WC()->customer is a singleton that outlives this test; restore it below.
		$original_shipping = array(
			'country'  => WC()->customer->get_shipping_country(),
			'state'    => WC()->customer->get_shipping_state(),
			'postcode' => WC()->customer->get_shipping_postcode(),
			'city'     => WC()->customer->get_shipping_city(),
			'address'  => WC()->customer->get_shipping_address(),
		);

		WC()->customer->set_shipping_country( 'US' );
		WC()->customer->set_shipping_state( 'FL' );
		WC()->customer->set_shipping_postcode( '33033' );
		WC()->customer->set_shipping_city( '' );
		WC()->customer->set_shipping_address( '' );

		$_POST['country']  = 'US';
		$_POST['state']    = 'FL';
		$_POST['postcode'] = '33033';
		$_POST['city']     = '';
		$_POST['street']   = '';

		try {
			$frontend = $this->invoke_protected_method( 'get_address', array( 'shipping' ) );
			$backend  = $this->invoke_protected_method( 'get_backend_address' );
		} finally {
			unset( $_POST['country'], $_POST['state'], $_POST['postcode'], $_POST['city'], $_POST['street'] );

			WC()->customer->set_shipping_country( $original_shipping['country'] );
			WC()->customer->set_shipping_state( $original_shipping['state'] );
			WC()->customer->set_shipping_postcode( $original_shipping['postcode'] );
			WC()->customer->set_shipping_city( $original_shipping['city'] );
			WC()->customer->set_shipping_address( $original_shipping['address'] );
		}

		$this->assertSame(
			$frontend['to_city'],
			$backend['to_city'],
			'Cart and admin-recalculate paths disagree on how an empty city is represented.'
		);
		$this->assertSame(
			$frontend['to_street'],
			$backend['to_street'],
			'Cart and admin-recalculate paths disagree on how an empty street is represented.'
		);
		$this->assertFalse( $backend['to_city'] );
		$this->assertFalse( $backend['to_street'] );
	}

	/**
	 * `get_backend_address()` must unslash `$_POST` before sanitizing it.
	 *
	 * WordPress slashes every superglobal, and `wc_clean()` sanitizes without
	 * unslashing. So an apostrophe in an admin order address arrives as `O\'Brien`
	 * and is passed through verbatim — into the TaxJar request body and, via
	 * `create_or_update_tax_rate()`, into the `wp_woocommerce_tax_rates` city column,
	 * where it can never match the unslashed value the cart path stores.
	 */
	public function test_get_backend_address_unslashes_post_values() {
		$_POST['country'] = 'US';
		$_POST['state']   = 'FL';
		$_POST['city']    = "O\\'Brien";
		$_POST['street']  = "123 O\\'Malley Way";

		try {
			$address = $this->invoke_protected_method( 'get_backend_address' );
		} finally {
			unset( $_POST['country'], $_POST['state'], $_POST['city'], $_POST['street'] );
		}

		$this->assertSame( "O'BRIEN", $address['to_city'], 'Backend city retained the WordPress-added slash.' );
		$this->assertSame( "123 O'MALLEY WAY", $address['to_street'], 'Backend street retained the WordPress-added slash.' );
	}

	// -------------------------------------------------------------------------
	// Request seam — the address that gets validated must be the address that
	// gets sent.
	// -------------------------------------------------------------------------

	/**
	 * A plain US:CA store.
	 *
	 * @return array
	 */
	private function default_store_settings() {
		return array(
			'street'   => '1 Store Way',
			'city'     => 'Beverly Hills',
			'state'    => 'CA',
			'country'  => 'US',
			'postcode' => '90210',
		);
	}

	/**
	 * Options for an in-state destination, so `calculate_tax()` runs past the
	 * cross-state no-nexus gate and actually assembles a request.
	 *
	 * @return array
	 */
	private function in_state_options() {
		return array(
			'to_country'      => 'US',
			'to_state'        => 'CA',
			'to_zip'          => '94103',
			'to_city'         => 'San Francisco',
			'to_street'       => '1 Market St',
			'shipping_amount' => 0,
			'line_items'      => array(
				array(
					'id'         => 'test-item',
					'quantity'   => 1,
					'unit_price' => '25.00',
				),
			),
		);
	}

	/**
	 * Point the store address at an arbitrary value for one test.
	 *
	 * Uses the public `taxjar_store_settings` filter rather than options, so nothing
	 * leaks into global state.
	 *
	 * @param array $settings Store settings shape `{street, city, state, country, postcode}`.
	 * @return callable The registered filter, for removal.
	 */
	private function force_store_settings( array $settings ) {
		$filter = function () use ( $settings ) {
			return $settings;
		};

		add_filter( 'taxjar_store_settings', $filter, 99 );

		return $filter;
	}

	/**
	 * Run `calculate_tax()` against an API client that intercepts the proxy call, and
	 * return the decoded JSON body that would have gone to TaxJar.
	 *
	 * This is the only seam that shows the finished request: the
	 * `woocommerce_taxjar_nexus_address` filter fires before `nexus_addresses` is
	 * written, so a body captured there is always incomplete. Returning a `WP_Error`
	 * ends the call cleanly — `calculate_tax()` logs and returns false, and no
	 * transient is written.
	 *
	 * @param array         $options      Options passed to `calculate_tax()`.
	 * @param callable|null $nexus_filter Optional `woocommerce_taxjar_nexus_address` callback.
	 * @param array|null    $store        Store settings override; defaults to a US:CA store.
	 * @return array|null Decoded request body, or null if no request was attempted.
	 */
	private function capture_taxjar_request_json( array $options, $nexus_filter = null, $store = null ) {
		$sent = null;

		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )
			->disableOriginalConstructor()
			->getMock();
		$api_client->method( 'proxy_request' )->willReturnCallback(
			function ( $path, $args ) use ( &$sent ) {
				$sent = $args['body'];

				return new WP_Error( 'test_intercepted', 'Request intercepted by the test.' );
			}
		);

		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->getMock();
		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )
			->disableOriginalConstructor()
			->getMock();

		// The notifier is optional on the constructor but dereferenced unguarded in
		// `smartcalcs_cache_request()`, so it has to be supplied. A real instance
		// rather than a mock: `clear_notices()` is static, which a mock cannot stand in for.
		$notifier = new Automattic\WCServices\StoreNotices\StoreNoticesNotifier( false );

		$integration = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks, $notifier );

		$store_filter = $this->force_store_settings( null === $store ? $this->default_store_settings() : $store );
		if ( is_callable( $nexus_filter ) ) {
			add_filter( 'woocommerce_taxjar_nexus_address', $nexus_filter, 10, 2 );
		}
		WC()->customer->set_is_vat_exempt( false );

		try {
			$integration->calculate_tax( $options );
		} finally {
			if ( is_callable( $nexus_filter ) ) {
				remove_filter( 'woocommerce_taxjar_nexus_address', $nexus_filter, 10 );
			}
			remove_filter( 'taxjar_store_settings', $store_filter, 99 );
		}

		return null === $sent ? null : json_decode( $sent, true );
	}

	/**
	 * A comma-separated store postcode must be reduced to its first segment, exactly
	 * as the destination postcode already is.
	 *
	 * `calculate_tax()` applied `explode( ',', ... )` to `to_zip` but never to
	 * `from_zip`, so a store postcode entered as a list reached
	 * `validate_taxjar_request()` intact, failed `WC_Validation::is_postcode()`, and
	 * aborted the calculation before any request was made — while the identical
	 * customer-supplied value was handled.
	 */
	public function test_store_postcode_comma_list_is_reduced_like_the_destination() {
		$store             = $this->default_store_settings();
		$store['postcode'] = '90210, 90211';

		$body = $this->capture_taxjar_request_json( $this->in_state_options(), null, $store );

		$this->assertIsArray( $body, 'A comma-separated store postcode aborted the request instead of being reduced.' );
		$this->assertSame( '90210', $body['nexus_addresses'][0]['zip'] );

		// And again with the nexus address disabled, so the `from_*` fields are what
		// actually carries the store postcode onto the wire.
		$without_nexus = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			'__return_false',
			$store
		);

		$this->assertIsArray( $without_nexus, 'A comma-separated store postcode aborted the request instead of being reduced.' );
		$this->assertArrayNotHasKey( 'nexus_addresses', $without_nexus );
		$this->assertSame( '90210', $without_nexus['from_zip'] );
	}

	/**
	 * `get_address_parts()` must report the postcodes the body actually carries.
	 *
	 * It upper-cased them before handing them to `validate_taxjar_request()`, so the
	 * value that was validated was not the value that was sent. Country and state are
	 * a different case: they are compared against the literal `'US'` and against each
	 * other, and this is reachable from the public `validate_taxjar_request()` with a
	 * hand-built body, so those stay normalized.
	 */
	public function test_get_address_parts_reports_postcodes_verbatim() {
		$body = array(
			'from_country' => 'CA',
			'from_state'   => 'ON',
			'from_zip'     => 'k1a 0b1',
			'to_country'   => 'ca',
			'to_state'     => 'on',
			'to_zip'       => 'm5v 3l9',
		);

		$parts = $this->invoke_protected_method( 'get_address_parts', array( $body ) );

		$this->assertSame( 'k1a 0b1', $parts['from_zip'], 'from_zip was validated in a different case than it is sent.' );
		$this->assertSame( 'm5v 3l9', $parts['to_zip'], 'to_zip was validated in a different case than it is sent.' );
		$this->assertSame( 'CA', $parts['to_country'], 'Country is compared against a literal and must stay normalized.' );
		$this->assertSame( 'ON', $parts['to_state'], 'State is compared against from_state and must stay normalized.' );
	}

	/**
	 * Destination fields the caller omitted reach the body as empty strings.
	 *
	 * The body is built from the value object, which types every field as a string.
	 * `to_city` and `to_street` were sent as `null` when absent from the options;
	 * `""` is the new wire value, and the changed JSON shifts the md5 transient
	 * key, turning over one cache generation.
	 */
	public function test_omitted_destination_fields_are_sent_as_empty_strings() {
		$options = $this->in_state_options();
		unset( $options['to_city'], $options['to_street'] );

		$body = $this->capture_taxjar_request_json( $options );

		$this->assertIsArray( $body, 'Omitting optional destination fields aborted the request.' );
		$this->assertSame( '', $body['to_city'] );
		$this->assertSame( '', $body['to_street'] );
	}

	/**
	 * A nexus address whose required field is present but blank must be rejected, so
	 * the request falls back to the store address.
	 *
	 * The old schema check skipped any field that was present-but-empty, so
	 * `array( 'country' => '' )` passed validation, replaced the `from_*` fields, and
	 * then failed the "From country is missing" gate — aborting a calculation that
	 * would have succeeded had the nexus simply been dropped.
	 */
	public function test_blank_required_nexus_field_falls_back_instead_of_aborting() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['country'] = '';

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body, 'A blank nexus country aborted the request instead of falling back to the store address.' );
		$this->assertArrayNotHasKey( 'nexus_addresses', $body, 'An invalid nexus address was forwarded.' );
		$this->assertSame( 'US', $body['from_country'] );
	}

	/**
	 * A nexus address is validated in the same shape it is sent.
	 *
	 * The schema requires an upper-case country code, so a filter returning `'us'`
	 * used to be discarded silently. Normalizing before validating accepts it and
	 * sends the normalized value, so "what was checked" and "what was sent" are the
	 * same string.
	 */
	public function test_nexus_address_is_sent_in_the_shape_it_was_validated() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['country'] = 'us';
				$nexus_address['state']   = 'ca';

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body );
		$this->assertArrayHasKey(
			'nexus_addresses',
			$body,
			'A lower-case country from the filter was dropped instead of being normalized.'
		);
		$this->assertSame( 'US', $body['nexus_addresses'][0]['country'] );
		$this->assertSame( 'CA', $body['nexus_addresses'][0]['state'] );
	}

	/**
	 * Keys the filter added that the value object does not know about must survive.
	 *
	 * `woocommerce_taxjar_nexus_address` is documented as array-in / array-or-false-out.
	 * This passed before the change too — it is a guard against the new normalization
	 * turning that contract into a whitelist, not a demonstration of a fixed bug.
	 */
	public function test_nexus_address_passes_through_unknown_keys() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['custom_key'] = 'kept';

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body );
		$this->assertArrayHasKey( 'nexus_addresses', $body );
		$this->assertSame( 'kept', $body['nexus_addresses'][0]['custom_key'] );
	}

	/**
	 * A key the filter removed must stay removed.
	 *
	 * Normalisation writes back only the keys the filter kept. Merging the full
	 * normalised shape instead would resurrect a removed key as a blank string, so
	 * a filter that unset `street` would start sending `"street": ""`.
	 */
	public function test_nexus_key_removed_by_filter_is_not_resurrected() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				unset( $nexus_address['street'] );

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body, 'Removing an optional nexus key aborted the request.' );
		$this->assertArrayHasKey( 'nexus_addresses', $body );
		$this->assertArrayNotHasKey(
			'street',
			$body['nexus_addresses'][0],
			'A key the filter removed was resurrected as a blank string.'
		);
	}

	/**
	 * A non-scalar field must be rejected rather than coerced.
	 *
	 * The old schema check rejected non-strings outright. The value object casts every
	 * field to string instead, so without an explicit guard an array would trigger an
	 * array-to-string conversion and be sent as the literal `"Array"`. This passed
	 * before the change too — it pins behaviour across the migration rather than
	 * demonstrating a fixed bug.
	 */
	public function test_non_scalar_nexus_field_is_rejected() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['city'] = array( 'Beverly Hills' );

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body );
		$this->assertArrayNotHasKey( 'nexus_addresses', $body );
	}

	/**
	 * A boolean nexus field is rejected, not cast.
	 *
	 * `false` casts to `""` and `true` to `"1"`, either of which silently changes
	 * the origin TaxJar rates against. The old schema rejected non-strings
	 * outright; booleans keep that treatment while numeric postcodes are accepted.
	 */
	public function test_boolean_nexus_field_is_rejected() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['zip'] = false;

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body, 'A boolean nexus field aborted the request instead of falling back.' );
		$this->assertArrayNotHasKey(
			'nexus_addresses',
			$body,
			'A nexus address with a boolean field was forwarded.'
		);
	}

	/**
	 * A non-scalar value under an unknown key must not reject the whole address.
	 *
	 * The non-scalar guard exists because the value object casts the fields it reads to
	 * string. Keys it does not read are never cast — they are merged back untouched —
	 * so they carry no conversion risk. Checking them anyway would make a single
	 * custom key holding an array discard the entire nexus address, silently swapping
	 * which nexus TaxJar is asked about.
	 */
	public function test_non_scalar_unknown_nexus_key_is_passed_through() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['meta'] = array( 'source' => 'erp' );

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body );
		$this->assertArrayHasKey(
			'nexus_addresses',
			$body,
			'A non-scalar value under an unknown key rejected the whole nexus address.'
		);
		$this->assertSame( array( 'source' => 'erp' ), $body['nexus_addresses'][0]['meta'] );
		$this->assertSame( 'US', $body['nexus_addresses'][0]['country'] );
	}

	/**
	 * A store outside the US keeps its own nexus address even with a blank state.
	 *
	 * `to_nexus_array()` always emits a `state` key, blank for a store with no base
	 * state configured. The old inline schema skipped blank fields, so that nexus
	 * always passed; `Address::validate()` fails a blank required field, so requiring
	 * state everywhere would newly reject the store's own nexus on every calculation.
	 * The US-only requirement prevents that regression.
	 *
	 * The provider mixes countries WooCommerce gives no states with ones it does.
	 * Both groups belong here: measured against the live TaxJar API, every one of
	 * them rates a blank-state nexus identically to a populated one, because only
	 * the US derives nexus from the origin state.
	 *
	 * @dataProvider provider_non_us_store_countries
	 *
	 * @param string $country   Two-letter country code.
	 * @param string $postcode  A postcode valid for that country.
	 * @param string $to_state  Destination state. Only CA needs one — `validate_taxjar_request()`
	 *                          requires a destination state for US and CA, which is a rule about
	 *                          where the customer is, not about the store's nexus.
	 */
	public function test_non_us_store_keeps_its_nexus_address_without_a_state( $country, $postcode, $to_state = '' ) {
		$store = array(
			'street'   => '1 Store Way',
			'city'     => 'Somewhere',
			'state'    => '',
			'country'  => $country,
			'postcode' => $postcode,
		);

		$options               = $this->in_state_options();
		$options['to_country'] = $country;
		$options['to_state']   = $to_state;
		$options['to_zip']     = $postcode;
		$options['to_city']    = 'Somewhere';

		$body = $this->capture_taxjar_request_json( $options, null, $store );

		$this->assertIsArray( $body, sprintf( 'A %s store aborted the tax calculation.', $country ) );
		$this->assertArrayHasKey(
			'nexus_addresses',
			$body,
			sprintf( 'The store nexus was rejected for %s, where TaxJar does not need an origin state.', $country )
		);
		$this->assertSame( $country, $body['nexus_addresses'][0]['country'] );
		$this->assertSame( '', $body['nexus_addresses'][0]['state'] );
	}

	/**
	 * Non-US TaxJar-supported countries, with and without WooCommerce states.
	 *
	 * The `has states` flag is recorded only to document that the split is deliberate —
	 * nothing in the assertion depends on it, which is the point.
	 *
	 * @return array<string, array{0: string, 1: string, 2: string}>
	 */
	public function provider_non_us_store_countries() {
		return array(
			// No states in WooCommerce.
			'GB' => array( 'GB', 'SW1A 1AA', '' ),
			'FR' => array( 'FR', '75001', '' ),
			'NL' => array( 'NL', '1011 AB', '' ),
			'DK' => array( 'DK', '1050', '' ),

			// States in WooCommerce, but TaxJar rates them from the destination anyway.
			'DE' => array( 'DE', '10117', '' ),
			'ES' => array( 'ES', '28001', '' ),
			'IT' => array( 'IT', '00184', '' ),
			'AU' => array( 'AU', '2000', '' ),

			// CA needs a destination state to clear `validate_taxjar_request()`; the
			// store's own state stays blank, which is what this test is about.
			'CA' => array( 'CA', 'M5H 2N2', 'ON' ),
		);
	}

	/**
	 * The US keeps the strict requirement, because there a blank state loses the tax.
	 *
	 * This is the one country the relaxation must not reach. US nexus is derived from
	 * the origin state, and TaxJar does not reject a blank one — it returns HTTP 200
	 * with `has_nexus: false` and zero tax, so an accepted blank-state US nexus is a
	 * silent under-collection. Rejecting it here falls back to the store's `from_*`
	 * address, which carries the real state.
	 *
	 * The old schema skipped blank fields, so this nexus was accepted, forwarded
	 * with a blank state, and the US cross-state gate then dropped the calculation
	 * without a request. This test fails on the base branch for that reason.
	 */
	public function test_blank_state_is_still_rejected_for_a_us_nexus() {
		$body = $this->capture_taxjar_request_json(
			$this->in_state_options(),
			function ( $nexus_address ) {
				$nexus_address['state'] = '';

				return $nexus_address;
			}
		);

		$this->assertIsArray( $body, 'A blank nexus state aborted the request instead of falling back.' );
		$this->assertArrayNotHasKey(
			'nexus_addresses',
			$body,
			'A US nexus address with a blank state was forwarded.'
		);
	}
}
