<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Migration_Flag_Controller' ) ) {
	return;
}

class WC_REST_Connect_Migration_Flag_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/migration-flag';

	public function post() {
		WC_Connect_Options::update_option( 'wcshipping_migrated', true );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
