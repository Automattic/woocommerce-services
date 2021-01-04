<?php

class WP_Test_WC_REST_Connect_Post_Print_Notification_Settings_Controller extends WC_Unit_Test_Case {

	/** @var WC_Connect_Service_Settings_Store $settings_store_mock */
	protected $settings_store_mock;

	/** @var WC_Connect_Logger $connect_logger_mock */
	protected $connect_logger_mock;

	/** @var WC_REST_Connect_Notification_Settings_Controller $controller */
	protected $controller;

	/**
	 * @inherit
	 */
	public static function setupBeforeClass() {
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-api-client-live.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-service-settings-store.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-logger.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-base-controller.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-post-print-notification-settings-controller.php';
	}

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function setUp() {
		$api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings_store_mock = $this->createMock( WC_Connect_Service_Settings_Store::class );
		$this->connect_logger_mock = $this->createMock( WC_Connect_Logger::class );

		$this->controller = new WC_REST_Connect_Post_Print_Notification_Settings_Controller( $api_client_mock, $this->settings_store_mock, $this->connect_logger_mock );
	}

	public function test_it_updates_the_setting() {
		$request = array(
			'name'    => 'foo',
			'enabled' => true,
		);

		$this->settings_store_mock->expects( $this->once() )
			->method( 'set_post_print_notification_setting' )
			->with( 'foo', true );

		$result = $this->controller->post( $request );

		$this->assertEquals( new WP_REST_Response( array( 'success' => true ), 200 ), $result );
	}

	public function test_it_returns_wp_error_if_error_occurs() {
		$request = array(
			'name'    => 'foo',
			'enabled' => true,
		);

		$store_wp_error = new WP_Error(
			'some code',
			'some message'
		);

		$this->settings_store_mock->expects( $this->once() )
			->method( 'set_post_print_notification_setting' )
			->willReturn( $store_wp_error );

		$expected_wp_error = new WP_Error(
			'save_failed',
			sprintf(
				__( 'Unable to update notification setting. %s', 'woocommerce-services' ),
				$store_wp_error->get_error_message()
			),
			array( 'status' => 400 )
		);

		$result = $this->controller->post( $request );

		$this->assertEquals( $expected_wp_error, $result );
	}

	public function test_it_logs_wp_error_if_it_occurs() {
		$request = array(
			'name'    => 'foo',
			'enabled' => true,
		);

		$store_wp_error = new WP_Error(
			'some code',
			'some message'
		);

		$this->settings_store_mock->expects( $this->once() )
			->method( 'set_post_print_notification_setting' )
			->willReturn( $store_wp_error );

		$expected_logged_wp_error = new WP_Error(
			'save_failed',
			sprintf(
				__( 'Unable to update notification setting. %s', 'woocommerce-services' ),
				$store_wp_error->get_error_message()
			),
			array( 'status' => 400 )
		);

		$this->connect_logger_mock->expects( $this->once() )
			->method( 'log' )
			->with( $expected_logged_wp_error, WC_REST_Connect_Post_Print_Notification_Settings_Controller::class );

		$this->controller->post( $request );
	}
}
