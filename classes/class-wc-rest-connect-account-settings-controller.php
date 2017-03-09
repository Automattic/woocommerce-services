<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Account_Settings_Controller' ) ) {
	return;
}

class WC_REST_Connect_Account_Settings_Controller extends WC_REST_Connect_Base_Controller {

	protected $method = 'POST';
	protected $rest_base = 'connect/account/settings';

	public function run( $request ) {
		$settings = $request->get_json_params();
		$result   = $this->settings_store->update_account_settings( $settings );

		if ( is_wp_error( $result ) ) {
			$error = new WP_Error( 'save_failed',
				sprintf(
					__( 'Unable to update settings. %s', 'woocommerce-services' ),
					$result->get_error_message()
				),
				array_merge(
					array( 'status' => 400 ),
					$result->get_error_data()
				)
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
