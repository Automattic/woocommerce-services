<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Self_Help_Controller' ) ) {
	return;
}

class WC_REST_Connect_Self_Help_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/self-help';

	public function post( $request ) {
		$settings = $request->get_json_params();

		if ( empty( $settings ) || ! array_key_exists( 'wcc_debug_on', $settings ) ) {
			$error = new WP_Error( 'bad_form_data',
				__( 'Unable to update settings. The form data could not be read.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		if ( 1 == $settings['wcc_debug_on'] ) {
			$this->logger->enable_logging();
		} else {
			$this->logger->disable_logging();
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
