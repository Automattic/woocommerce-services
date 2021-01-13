<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Post_Print_Notification_Settings_Controller' ) ) {
	return;
}

class WC_REST_Connect_Post_Print_Notification_Settings_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/post-print-notification-settings';

	public function post( $request ) {
		$result = $this->settings_store->set_post_print_notification_setting( $request['name'], $request['enabled'] );

		if ( is_wp_error( $result ) ) {
			$error = new WP_Error(
				'save_failed',
				sprintf(
					__( 'Unable to update notification setting. %s', 'woocommerce-services' ),
					$result->get_error_message()
				),
				array( 'status' => 400 )
			);

			$this->logger->log( $error, __CLASS__ );

			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
