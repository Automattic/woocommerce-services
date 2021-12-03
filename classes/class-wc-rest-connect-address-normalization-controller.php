<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Address_Normalization_Controller' ) ) {
	return;
}

class WC_REST_Connect_Address_Normalization_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/normalize-address';

	public function post( $request ) {
		$data    = $request->get_json_params();
		$address = $data['address'];
		$company = $address['company'];
		$phone   = $address['phone'];

		unset( $address['company'], $address['phone'] );

		/**
		 * We only want to validate the name field if the API
		 * version is greater than 5. If it's 5 or less
		 * don't validate the name field.
		 */
		if ( WOOCOMMERCE_CONNECT_SERVER_API_VERSION <= 5 ) {
			$name = $address['name'];
			unset( $address['name'] );
		}

		$body     = array(
			'destination' => $address,
		);
		$response = $this->api_client->send_address_normalization_request( $body );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		if ( isset( $response->field_errors ) ) {
			$this->logger->log( 'Address validation errors: ' . implode( '; ', array_values( (array) $response->field_errors ) ), __CLASS__ );
			return array(
				'success'      => true,
				'field_errors' => $response->field_errors,
			);
		}

		/**
		 * We only want to validate the name field if the API
		 * version is greater than 5. If it's 5 or less
		 * don't validate the name field.
		 */
		if ( WOOCOMMERCE_CONNECT_SERVER_API_VERSION <= 5 ) {
			$response->normalized->name = $name;
		}

		$response->normalized->company = $company;
		$response->normalized->phone   = $phone;
		$is_trivial_normalization      = isset( $response->is_trivial_normalization ) ? $response->is_trivial_normalization : false;

		return array(
			'success'                  => true,
			'normalized'               => $response->normalized,
			'is_trivial_normalization' => $is_trivial_normalization,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function check_permission( $request ) {
		$data = $request->get_json_params();

		if ( 'origin' === $data['type'] ) {
			return WC_Connect_Functions::user_can_manage_labels(); // Only an admin can normalize the origin address
		}

		return true; // non-authenticated service for the 'destination' address
	}
}
