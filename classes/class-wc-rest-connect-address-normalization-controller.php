<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Address_Normalization_Controller' ) ) {
	return;
}

/**
 * Normalizes a shipping label address through the Connect server.
 */
class WC_REST_Connect_Address_Normalization_Controller extends WC_REST_Connect_Base_Controller {
	/**
	 * Endpoint path.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/normalize-address';

	/**
	 * Normalize the address in the request body.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return array|WP_Error
	 */
	public function post( $request ) {
		$data = $request->get_json_params();

		if ( empty( $data['address'] ) || ! is_array( $data['address'] ) ) {
			$error = new WP_Error(
				'bad_form_data',
				__( 'Unable to normalize the address. The form data could not be read.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		$address = $data['address'];
		// The phone is echoed back on the response untouched, so only a scalar is carried through.
		$phone = isset( $address['phone'] ) && is_scalar( $address['phone'] ) ? (string) $address['phone'] : '';

		unset( $address['phone'] );

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

		if ( ! isset( $response->normalized ) ) {
			$response->normalized = new stdClass();
		}

		$response->normalized->phone = $phone;
		$is_trivial_normalization    = isset( $response->is_trivial_normalization ) ? $response->is_trivial_normalization : false;

		return array(
			'success'                  => true,
			'normalized'               => $response->normalized,
			'is_trivial_normalization' => $is_trivial_normalization,
		);
	}

	/**
	 * Validate the requester's permissions.
	 *
	 * Both the origin and the destination address are only normalized from the
	 * shipping label form in wp-admin, so every request needs the labels capability.
	 *
	 * This is deliberately kept even though it currently matches the base class:
	 * the requirement belongs to this route, not to whatever default the base
	 * controller happens to carry, and third-party subclasses override it here.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return bool
	 */
	public function check_permission( $request ) {
		return WC_Connect_Functions::user_can_manage_labels();
	}
}
