<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Tos_Controller' ) ) {
	return;
}

/**
 * REST controller for reading and accepting the Terms of Service.
 */
class WC_REST_Connect_Tos_Controller extends WC_REST_Connect_Base_Controller {

	/**
	 * The REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/tos';

	/**
	 * Get the current Terms of Service acceptance status.
	 *
	 * @return WP_REST_Response The response containing the acceptance status.
	 */
	public function get() {
		return new WP_REST_Response(
			array(
				'success'  => true,
				'accepted' => WC_Connect_Options::get_option( 'tos_accepted' ),
			),
			200
		);
	}

	/**
	 * Record acceptance of the Terms of Service.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return WP_REST_Response|WP_Error The response containing the acceptance status, or an error.
	 */
	public function post( $request ) {
		$settings = $request->get_json_params();

		if ( ! $settings || ! isset( $settings['accepted'] ) || ! $settings['accepted'] ) {
			return new WP_Error( 'bad_request', __( 'Bad request', 'woocommerce-services' ), array( 'status' => 400 ) );
		}

		WC_Connect_Options::update_option( 'tos_accepted', true );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'accepted' => WC_Connect_Options::get_option( 'tos_accepted' ),
			),
			200
		);
	}

	/**
	 * Validate the requester's permissions.
	 *
	 * @param WP_REST_Request $request The REST request.
	 * @return bool Whether the requester has permission.
	 */
	public function check_permission( $request ) {
		return current_user_can( 'manage_woocommerce' ) &&
			current_user_can( 'install_plugins' ) &&
			current_user_can( 'activate_plugins' );
	}
}
