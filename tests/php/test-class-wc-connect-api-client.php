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
	public static function setupBeforeClass() {
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client-live.php';

	}

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function setUp() {
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
	public function tearDown() {
		// empty the test cart.
		WC()->cart->empty_cart();

		// release the test client instance.
		unset( $this->api_client );

		// delete test product.
		WC_Helper_Product::delete_product( $this->product->get_id() );
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
}
