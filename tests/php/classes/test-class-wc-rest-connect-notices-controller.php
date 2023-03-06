<?php

/**
 * Unit test for WP_Test_WC_REST_Connect_Notices_Controller
 */
class WP_Test_WC_REST_Connect_Notices_Controller extends WC_REST_Unit_Test_Case {

	/** @var WC_Connect_API_Client_Live $api_client_mock */
	protected $api_client_mock;

	/** @var WC_Connect_Service_Settings_Store $settings_store_mock */
	protected $settings_store_mock;

	/** @var WC_Connect_Logger $logger_mock */
	protected $logger_mock;

	/** @var WC_Connect_Notice_Manager $notice_manager_mock */
	protected $notice_manager_mock;

	/**
	 * @var WC_REST_Connect_Notices_Controller
	 */
	private $rest_controller;

	/**
	 * @inherit
	 */
	public static function set_up_before_class() {
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-api-client-live.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-service-settings-store.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-logger.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-notice-manager.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-base-controller.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-notices-controller.php';
	}

	public function set_up() {
		parent::set_up();

		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->getMock();

		$this->settings_store_mock = $this->createMock( WC_Connect_Service_Settings_Store::class );
		$this->logger_mock         = $this->createMock( WC_Connect_Logger::class );
		$this->notice_manager_mock = $this->getMockBuilder( WC_Connect_Notice_Manager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->rest_controller = new WC_REST_Connect_Notices_Controller(
			$this->api_client_mock,
			$this->settings_store_mock,
			$this->logger_mock,
			$this->notice_manager_mock
		);
	}

	public function test_it_dismisses_notice_and_returns_ok() {
		$this->notice_manager_mock->expects( $this->once() )
		                          ->method( 'dismiss' )
		                          ->with( 'foo' );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/connect/notices' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'id' => 'foo',
				)
			)
		);
		$response = $this->rest_controller->dismiss( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertEquals( array( 'success' => true ), $response->get_data() );
	}

	public function test_it_returns_and_logs_error_if_notice_manager_throws_invalid_argument_exception() {
		$this->notice_manager_mock->method( 'dismiss' )
		                          ->willThrowException( new InvalidArgumentException() );
		$this->logger_mock->expects( $this->once() )
		                  ->method( 'log' )
		                  ->with( 'Unable to dismiss notice - notice ID is invalid or notice is not dismissible.' );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/connect/notices' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'id' => 'foo',
				)
			)
		);
		$response = $this->rest_controller->dismiss( $request );

		$this->assertEquals( 'notice_id_invalid', $response->get_error_code() );
		$this->assertEquals( 'Unable to dismiss notice - notice ID is invalid or notice is not dismissible.', $response->get_error_message() );
		$this->assertEquals( array( 'status' => 400 ), $response->get_error_data() );
	}
}
