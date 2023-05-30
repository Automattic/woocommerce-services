<?php



/**
 * Unit test for WC_Connect_API_Client_Live
 */
class WP_Test_WC_REST_Connect_Shipping_Carrier_Types_Controller extends WC_Unit_Test_Case {

	/** @var WC_Connect_API_Client_Live $api_client_mock */
	protected $api_client_mock;

	/** @var WC_Connect_Service_Settings_Store $setting_store_mock */
	protected $setting_store_mock;

	/** @var WC_Connect_Logger $connect_logger_mock */
	protected $connect_logger_mock;

	/**
	 * @inherit
	 */
	public static function set_up_before_class() {
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-api-client-live.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-service-settings-store.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-logger.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-base-controller.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-shipping-carrier-types-controller.php';
	}

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function set_up() {
		// Creating a mock class and overide protected request method so that we can mock the API response.
		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->setMethods( [ 'request' ] )
			->getMock();

		$this->setting_store_mock  = $this->createMock( WC_Connect_Service_Settings_Store::class );
		$this->connect_logger_mock = $this->createMock( WC_Connect_Logger::class );
	}

	/**
	 * Test GET request on connect/shipping/carrier-types end point.
	 */
	public function test_get() {
		$api_response        = '{"carriers":[{"type":"DhlExpressAccount","name":"DHL Express","fields":{"account_number":{"visibility":"visible","label":"DHL Account Number"},"country":{"visibility":"visible","label":"Account Country Code (2 Letter)"},"is_reseller":{"visibility":"checkbox","label":"Reseller Account? (check if yes)"}}},{"type":"UpsAccount","name":"UPS","fields":{}}]}';
		$api_client_response = json_decode( $api_response ); // assumes api_client->request() successfully returns the JSON decoded response body.

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'GET', '/shipping/carrier-types' )
			->willReturn( $api_client_response );

		$wc_connect_shipping_carrier_types_controller = new WC_REST_Connect_Shipping_Carrier_Types_Controller( $this->api_client_mock, $this->setting_store_mock, $this->connect_logger_mock );
		$actual                                       = $wc_connect_shipping_carrier_types_controller->get();
		$expected                                     = [
			'success'  => true,
			'carriers' => $api_client_response->carriers,
		];

		$this->assertEquals( 200, $actual->status );
		$this->assertEquals( $expected, $actual->data );
	}

	/**
	 * Test error thrown during a GET request on connect/shipping/carrier-types end point.
	 */
	public function test_get_with_error() {
		$api_response = new WP_Error( 'error_code_123', 'something is wrong' );

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'GET', '/shipping/carrier-types' )
			->willReturn( $api_response );

		$this->connect_logger_mock->expects( $this->once() )
			->method( 'log' )
			->with( $api_response, WC_REST_Connect_Shipping_Carrier_Types_Controller::class );

		$wc_connect_shipping_carrier_types_controller = new WC_REST_Connect_Shipping_Carrier_Types_Controller( $this->api_client_mock, $this->setting_store_mock, $this->connect_logger_mock );
		$actual                                       = $wc_connect_shipping_carrier_types_controller->get();

		$this->assertInstanceOf( WP_Error::class, $actual );
		$this->assertEquals( $api_response, $actual );
	}
}
