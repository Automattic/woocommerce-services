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
}
