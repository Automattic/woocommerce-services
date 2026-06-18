<?php

/**
 * Unit test for WC_Connect_API_Client_Live
 */
class WP_Test_WC_Connect_API_Client extends WC_Unit_Test_Case {

	/** @var WC_Connect_API_Client_Live $api_client */
	protected $api_client;
	/** @var WC_Product $product */
	protected $product;

	/**
	 * Undocumented function
	 */
	public static function set_up_before_class() {
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client-live.php';
	}

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function set_up() {
		$this->api_client = $this->getMockBuilder( 'WC_Connect_API_Client_Live' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();
	}

	/**
	 * Clean up the test case.
	 *
	 * @see WC_Unit_Test_Case::tearDown()
	 */
	public function tear_down() {
		// empty the test cart.
		WC()->cart->empty_cart();

		// release the test client instance.
		unset( $this->api_client );

		// delete test product if it exists.
		if ( $this->product ) {
			WC_Helper_Product::delete_product( $this->product->get_id() );
		}
	}

	/**
	 * Test simple product but missing weight
	 */
	public function test_build_shipment_contents_simple_product_no_weight() {
		$this->product = WC_Helper_Product::create_simple_product();
		$product_id    = $this->product->get_id();
		update_post_meta( $product_id, '_weight', '' );

		WC()->cart->add_to_cart( $product_id, 1 );

		$actual = $this->api_client->build_shipment_contents( array( 'contents' => WC()->cart->get_cart() ) );

		$this->assertWPError( $actual );
		$this->assertEquals( 'product_missing_weight', $actual->get_error_code() );
	}

	/**
	 * Test simple product
	 */
	public function test_build_shipment_contents_simple_product() {
		$this->product = WC_Helper_Product::create_simple_product();
		$product_id    = $this->product->get_id();

		// set base product dimensions.
		update_post_meta( $product_id, '_weight', '2' );
		update_post_meta( $product_id, '_height', '5' );
		update_post_meta( $product_id, '_width', '6' );
		update_post_meta( $product_id, '_length', '7' );

		WC()->cart->add_to_cart( $product_id, 1 );

		$actual = $this->api_client->build_shipment_contents( array( 'contents' => WC()->cart->get_cart() ) );

		$expected = array(
			array(
				'height'     => 5,
				'product_id' => $product_id,
				'length'     => 7,
				'width'      => 6,
				'quantity'   => 1,
				'weight'     => 2,
			),
		);

		$this->assertEquals( $actual, $expected );
	}

	/**
	 * Test variable product
	 */
	public function test_build_shipment_contents_variable_product() {
		$this->product = WC_Helper_Product::create_variation_product();

		$all_variations     = $this->product->get_available_variations();
		$first_variation_id = $all_variations[2]['variation_id'];
		$product_id         = $this->product->get_id();

		// set base product dimensions.
		update_post_meta( $product_id, '_weight', '2' );
		update_post_meta( $product_id, '_height', '5' );
		update_post_meta( $product_id, '_width', '6' );
		update_post_meta( $product_id, '_length', '7' );

		// set variation dimensions.
		update_post_meta( $first_variation_id, '_weight', '5' );
		update_post_meta( $first_variation_id, '_height', '2' );
		update_post_meta( $first_variation_id, '_width', '3' );
		update_post_meta( $first_variation_id, '_length', '4' );

		WC()->cart->add_to_cart( $product_id, 1, $first_variation_id );

		$actual = $this->api_client->build_shipment_contents( array( 'contents' => WC()->cart->get_cart() ) );

		$expected = array(
			array(
				'height'     => 2,
				'product_id' => $first_variation_id,
				'length'     => 4,
				'width'      => 3,
				'quantity'   => 1,
				'weight'     => 5,
			),
		);

		$this->assertEquals( $actual, $expected );
	}

	/**
	 * Test get_sift_configuration returns cached config.
	 */
	public function test_get_sift_configuration_returns_cached() {
		$expected_config = array( 'beacon_key' => 'test_key_123' );

		set_transient( WC_Connect_API_Client::SIFT_CONFIG_TRANSIENT_KEY, $expected_config );

		$actual = $this->api_client->get_sift_configuration();

		$this->assertEquals( $expected_config, $actual );
	}

	/**
	 * Test get_sift_configuration returns error when not cached and non-blocking.
	 */
	public function test_get_sift_configuration_non_blocking_returns_error_when_not_cached() {
		delete_transient( WC_Connect_API_Client::SIFT_CONFIG_TRANSIENT_KEY );

		$actual = $this->api_client->get_sift_configuration( false );

		$this->assertWPError( $actual );
		$this->assertEquals( 'sift_not_cached', $actual->get_error_code() );
	}

	/**
	 * The currency reported to the WooCommerce Tax / Connect server must be the store's
	 * configured base currency, never a per-session display currency injected by a
	 * currency-switcher plugin through the `woocommerce_currency` filter.
	 *
	 * Regression test for WOOTAX-49: a scheduled service-schema fetch that ran inside a
	 * customer session (where the currency had been filtered, e.g. to USD) reported the
	 * filtered currency to the server, which then returned a currency-limited service
	 * list and hid the store's configured shipping methods until settings were re-saved.
	 */
	public function test_get_settings_values_currency_ignores_woocommerce_currency_filter() {
		$original_currency = get_option( 'woocommerce_currency' );
		update_option( 'woocommerce_currency', 'CAD' );

		$force_usd = static function () {
			return 'USD';
		};
		add_filter( 'woocommerce_currency', $force_usd );

		try {
			// Precondition: a currency switcher really is filtering the display currency.
			$this->assertSame( 'USD', get_woocommerce_currency(), 'Expected the woocommerce_currency filter to be active.' );

			// Stub the loader collaborator so get_settings_values() can run in isolation.
			$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
				->disableOriginalConstructor()
				->setMethods( array( 'get_active_services' ) )
				->getMock();
			$loader->method( 'get_active_services' )->willReturn( array() );

			$loader_prop = new ReflectionProperty( 'WC_Connect_API_Client', 'wc_connect_loader' );
			$loader_prop->setAccessible( true );
			$loader_prop->setValue( $this->api_client, $loader );

			$get_settings_values = new ReflectionMethod( 'WC_Connect_API_Client', 'get_settings_values' );
			$get_settings_values->setAccessible( true );
			$values = $get_settings_values->invoke( $this->api_client );

			$this->assertSame( 'CAD', $values['currency'], 'Settings currency must be the unfiltered store base currency.' );
		} finally {
			remove_filter( 'woocommerce_currency', $force_usd );
			update_option( 'woocommerce_currency', $original_currency );
		}
	}
}
