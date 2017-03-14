<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Packages_Controller' ) ) {
	return;
}

class WC_REST_Connect_Packages_Controller extends WC_REST_Connect_Base_Controller {

	protected $method = 'POST';
	protected $rest_base = 'connect/packages';

	public function run( $request ) {
		$packages = $request->get_json_params();

		$this->settings_store->update_packages( $packages[ 'custom' ] );
		$this->settings_store->update_predefined_packages( $packages[ 'predefined' ] );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
