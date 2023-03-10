<?php

namespace WCShip\Notifications;

use InvalidArgumentException;
use WC_Connect_API_Client;
use WC_Connect_Logger;
use WC_Connect_Service_Settings_Store;
use WC_REST_Connect_Base_Controller;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Notifications_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/notifications';

	/**
	 * @var Notification_Manager
	 */
	protected $notification_manager;

	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Logger $logger,
		Notification_Manager $notice_manager
	) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->notification_manager = $notice_manager;
	}

	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'dismiss' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	public function dismiss( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		try {
			$this->notification_manager->dismiss( $id );
		} catch ( InvalidArgumentException $e ) {
			$error = new WP_Error(
				'notification_id_invalid',
				__( 'Unable to dismiss notification - notification ID is invalid or it has already been dismissed.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->log( $error->get_error_message(), __CLASS__ );
			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
