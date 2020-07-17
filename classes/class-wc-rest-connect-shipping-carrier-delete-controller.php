<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Shipping_Carrier_Delete_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Carrier_Delete_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/shipping/carrier/(?P<carrier_id>.+)';

	public function delete( $request ) {
		$carrier_id = $request['carrier_id'];

		$response = $this->api_client->disconnect_carrier_account( $carrier_id );
		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		return array( 'success' => true );
	}
}
