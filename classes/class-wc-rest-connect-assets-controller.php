<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Assets_Controller' ) ) {
	return;
}

class WC_REST_Connect_Assets_Controller extends WC_REST_Connect_Base_Controller {

	protected $rest_base = 'connect/assets';

	public function get() {

		return new WP_REST_Response(
			array(
				'success' => true,
				'assets'  => array(
					'wc_connect_admin_script' => WC_Connect_Loader::get_wcs_admin_script_url(),
					'wc_connect_admin_style'  => WC_Connect_Loader::get_wcs_admin_style_url(),
				),
			),
			200
		);
	}
}
