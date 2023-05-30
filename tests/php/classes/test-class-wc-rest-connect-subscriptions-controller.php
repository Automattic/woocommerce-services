<?php



/**
 * Unit test for WC_Connect_API_Client_Live
 */
class WP_Test_WC_REST_Connect_Subscriptions_Controller extends WC_Unit_Test_Case {

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
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-subscriptions-controller.php';
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
	 * Test GET request on connect/subscriptions end point.
	 */
	public function test_get() {
		$api_response        = '{
			"subscriptions": [
				{
					"product_key": "W00-5705e69b-4cbe-85ae-bb62bf204675",
					"product_keys_all": [
						"W00-5705e69b-4cbe-85ae-bb62bf204675"
					],
					"product_id": 1770503,
					"product_name": "UPS Live Rates",
					"product_url": "https://woocommerce.com/products/UPS-live-rates/",
					"key_type": "single",
					"key_type_label": "Single site",
					"key_parent_order_item_id": null,
					"autorenew": true,
					"connections": [
						742089
					],
					"legacy_connections": [],
					"shares": [],
					"lifetime": false,
					"expires": 1634774400,
					"expired": false,
					"expiring": false,
					"sites_max": 1,
					"sites_active": 1,
					"maxed": false,
					"product_status": "publish",
					"usage_limit": 50,
					"usage_count": 8
				},
				{
					"product_key": "W00-5705e69b-4cbe-85ae-bb62bf204675",
					"product_keys_all": [
						"W00-5705e69b-4cbe-85ae-bb62bf204675"
					],
					"product_id": 1770504,
					"product_name": "DHL Express Live Rates",
					"product_url": "https://woocommerce.com/products/DHLExpress-live-rates/",
					"key_type": "single",
					"key_type_label": "Single site",
					"key_parent_order_item_id": null,
					"autorenew": true,
					"connections": [
						742089
					],
					"legacy_connections": [],
					"shares": [],
					"lifetime": false,
					"expires": 1634774400,
					"expired": false,
					"expiring": false,
					"sites_max": 1,
					"sites_active": 1,
					"maxed": false,
					"product_status": "publish",
					"usage_limit": 1000,
					"usage_count": 250
				}
			]
		}';
		$api_client_response = json_decode( $api_response ); // assumes api_client->request() successfully returns the JSON decoded response body.

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', '/subscriptions' )
			->willReturn( $api_client_response );

		$wc_connect_subscriptions_controller = new WC_REST_Connect_Subscriptions_Controller( $this->api_client_mock, $this->setting_store_mock, $this->connect_logger_mock );
		$actual                              = $wc_connect_subscriptions_controller->post();
		$expected                            = $api_client_response->subscriptions;

		$expected = [
			'success'       => true,
			'subscriptions' => $api_client_response->subscriptions,
		];

		$this->assertEquals( 200, $actual->status );
		$this->assertEquals( $expected, $actual->data );
	}

	/**
	 * Test error thrown during a GET request on connect/subscriptions end point.
	 */
	public function test_get_with_error() {
		$api_response = new WP_Error( 'error_code_123', 'something is wrong' );

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', '/subscriptions' )
			->willReturn( $api_response );

		$this->connect_logger_mock->expects( $this->once() )
			->method( 'log' )
			->with( $api_response, WC_REST_Connect_Subscriptions_Controller::class );

		$wc_connect_subscriptions_controller = new WC_REST_Connect_Subscriptions_Controller( $this->api_client_mock, $this->setting_store_mock, $this->connect_logger_mock );
		$actual                              = $wc_connect_subscriptions_controller->post();

		$this->assertInstanceOf( WP_Error::class, $actual );
		$this->assertEquals( $api_response, $actual );
	}
}
