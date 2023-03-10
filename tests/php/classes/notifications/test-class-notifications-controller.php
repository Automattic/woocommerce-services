<?php

namespace WCShip\Notifications;

use InvalidArgumentException;
use WC_Connect_API_Client_Live;
use WC_Connect_Logger;
use WC_Connect_Service_Settings_Store;
use WP_REST_Request;

/**
 * Unit test for WP_Test_WC_REST_Connect_Notices_Controller
 */
class WP_Test_Notifications_Controller extends \WC_REST_Unit_Test_Case {

	/** @var WC_Connect_API_Client_Live $api_client_mock */
	protected $api_client_mock;

	/** @var WC_Connect_Service_Settings_Store $settings_store_mock */
	protected $settings_store_mock;

	/** @var WC_Connect_Logger $logger_mock */
	protected $logger_mock;

	/** @var Notification_Manager $notification_manager_mock */
	protected $notification_manager_mock;

	/**
	 * @var Notifications_Controller
	 */
	private $rest_controller;

	/**
	 * @inherit
	 */
	public static function set_up_before_class() {
		global $wcship_root;

		require_once $wcship_root . '/classes/class-wc-connect-api-client-live.php';
		require_once $wcship_root . '/classes/class-wc-connect-service-settings-store.php';
		require_once $wcship_root . '/classes/class-wc-connect-logger.php';
		require_once $wcship_root . '/classes/notifications/class-notification-manager.php';
		require_once $wcship_root . '/classes/class-wc-rest-connect-base-controller.php';
		require_once $wcship_root . '/classes/notifications/class-notifications-controller.php';
	}

	public function set_up() {
		parent::set_up();

		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->getMock();
		$this->settings_store_mock       = $this->createMock( WC_Connect_Service_Settings_Store::class );
		$this->logger_mock               = $this->createMock( WC_Connect_Logger::class );
		$this->notification_manager_mock = $this->getMockBuilder( Notification_Manager::class )
		                                        ->disableOriginalConstructor()
		                                        ->getMock();

		$this->rest_controller = new Notifications_Controller(
			$this->api_client_mock,
			$this->settings_store_mock,
			$this->logger_mock,
			$this->notification_manager_mock
		);
	}

	public function test_it_dismisses_notification_and_returns_ok() {
		$this->notification_manager_mock->expects( $this->once() )
		                          ->method( 'dismiss' )
		                          ->with( 'foo' );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/connect/notifications' );
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

	public function test_it_returns_and_logs_error_if_notification_manager_throws_invalid_argument_exception() {
		$this->notification_manager_mock->method( 'dismiss' )
		                                ->willThrowException( new InvalidArgumentException() );
		$this->logger_mock->expects( $this->once() )
		                  ->method( 'log' )
		                  ->with( 'Unable to dismiss notification - notification ID is invalid or it has already been dismissed.' );

		$request = new WP_REST_Request( 'PUT', '/wc/v1/connect/notifications' );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'id' => 'foo',
				)
			)
		);
		$response = $this->rest_controller->dismiss( $request );

		$this->assertEquals( 'notification_id_invalid', $response->get_error_code() );
		$this->assertEquals( 'Unable to dismiss notification - notification ID is invalid or it has already been dismissed.', $response->get_error_message() );
		$this->assertEquals( array( 'status' => 400 ), $response->get_error_data() );
	}
}
