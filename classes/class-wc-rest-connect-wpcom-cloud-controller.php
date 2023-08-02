<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_WPCOM_Cloud_Controller' ) ) {
	return;
}

class WC_REST_Connect_WPCOM_Cloud_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/wpcom-cloud';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/disconnect',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'disconnect' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	public function disconnect() {
		WC_Connect_Jetpack::disconnect();

		return new WP_REST_Response( array( 'success' => true ) );
	}

}
