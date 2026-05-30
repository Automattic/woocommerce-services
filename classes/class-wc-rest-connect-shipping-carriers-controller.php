<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Shipping_Carriers_Controller' ) ) {
	return;
}

/**
 * REST controller for retrieving the available shipping carriers.
 */
class WC_REST_Connect_Shipping_Carriers_Controller extends WC_REST_Connect_Base_Controller {
	/**
	 * The REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/shipping/carriers';

	/**
	 * Retrieve all available shipping carriers.
	 *
	 * @return array|WP_Error The shipping carriers response, or a WP_Error on failure.
	 */
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
