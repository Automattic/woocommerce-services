<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Shipping_Carriers_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Carriers_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/shipping/carriers';

	public function get() {
		$response = $this->api_client->get_all_shipping_carriers();
		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		return $response;
	}
}
