<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Scale_Controller' ) ) {
	return;
}

class WC_REST_Connect_Scale_Controller extends WC_REST_Connect_Base_Controller {

	protected $rest_base = 'connect/scale';

	public function get() {
		return new WP_REST_Response(
			array(
				'success'  => true,
				'weight' => WC_Connect_Options::get_option( 'scale_1_weight', 0 ),
				'datetime' => WC_Connect_Options::get_option( 'scale_1_weight_datetime', 0 ),
			),
			200
		);
	}

	public function post( $request ) {
		$settings = $request->get_json_params();

		if ( ! $settings || ! isset( $settings['weight'] ) || ! $settings['weight'] ) {
			return new WP_Error( 'bad_request', __( 'Bad request', 'woocommerce-services' ), array( 'status' => 400 ) );
		}

		WC_Connect_Options::update_option( 'scale_1_weight', $settings['weight'] );
		WC_Connect_Options::update_option( 'scale_1_weight_datetime', current_time( 'timestamp', true ) );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'weight' => WC_Connect_Options::get_option( 'scale_1_weight', 0 ),
				'datetime' => WC_Connect_Options::get_option( 'scale_1_weight_datetime', 0 ),
			),
			200
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function check_permission( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}
}
