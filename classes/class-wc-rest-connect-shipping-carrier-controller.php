<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Shipping_Carrier_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Carrier_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/shipping/carrier';

	public function get( $request ) {
		$response = $this->api_client->get_carrier_accounts( $request );
		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		return array( 'success' => true, 'carriers' => $response->carriers );
	}

	public function post( $request ) {
		$settings = $request->get_json_params();

		$response = $this->api_client->create_shipping_carrier_account( $settings );
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

	public function delete( $request ) {
		$payload = array( 'carrier' => $request['carrier'] );

		$response = $this->api_client->disconnect_carrier_account( $payload );
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
