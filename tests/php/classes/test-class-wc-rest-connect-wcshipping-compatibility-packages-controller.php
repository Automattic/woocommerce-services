<?php

/**
 * Tests for WC_REST_Connect_WCShipping_Compatibility_Packages_Controller
 */
class WP_Test_WC_REST_Connect_WCShipping_Compatibility_Packages_Controller extends WC_REST_Unit_Test_Case {

	/** @var WC_Connect_API_Client_Live $api_client_mock */
	protected $api_client_mock;

	/** @var WC_Connect_Logger $connect_logger_mock */
	protected $connect_logger_mock;

	/** @var WC_Connect_Service_Schemas_Store $service_schemas_store_mock */
	protected $service_schemas_store_mock;

	/** @var WC_Connect_Service_Settings_Store $settings_store */
	protected $settings_store;

	/**
	 * @inherit
	 */
	public static function set_up_before_class() {
		require_once __DIR__ . '/../../../classes/class-wc-connect-api-client-live.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-service-settings-store.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-service-schemas-store.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-package-settings.php';
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-base-controller.php';
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-packages-controller.php';
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-wcshipping-compatibility-packages-controller.php';
	}

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function set_up() {
		parent::set_up();

		// Creating a mock class and override protected request method so that we can mock the API response.
		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
										->disableOriginalConstructor()
										->setMethods( array( 'request' ) )
										->getMock();

		$this->connect_logger_mock        = $this->createMock( WC_Connect_Logger::class );
		$this->service_schemas_store_mock = $this->createMock( WC_Connect_Service_Schemas_Store::class );
		$this->settings_store             = new WC_Connect_Service_Settings_Store( $this->service_schemas_store_mock, $this->api_client_mock, $this->connect_logger_mock );
	}

	/**
	 * Test that creating custom packages updates the custom packages in settings store while predefined packages remain the same.
	 */
	public function test_it_returns_packages_from_wc_connect_options() {
		// Given.
		$controller = new WC_REST_Connect_WCShipping_Compatibility_Packages_Controller(
			$this->api_client_mock,
			$this->settings_store,
			$this->connect_logger_mock,
			$this->service_schemas_store_mock,
		);

		update_option(
			'wc_connect_options',
			array(
				'packages'            => array( array( 'name' => 'foo' ) ),
				'predefined_packages' => array( 'bar' ),
			)
		);

		// When.
		$request  = new WP_REST_Request( 'GET', '/wc/v1/connect/wcservices/packages' );
		$response = $controller->get();

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( 'foo', $response->get_data()['formData']['custom'][0]['name'] );
		$this->assertEquals( 'bar', $response->get_data()['formData']['predefined'][0] );
	}
}
