<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Services_Dismiss_Service_Notice_Controller' ) ) {
	return;
}

class WC_REST_Connect_Services_Dismiss_Service_Notice_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/services/dismiss_notice';

	/*
	 * @var WC_Connect_Nux
	 */
	protected $nux;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Nux $nux ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->nux = $nux;
	}

	public function post() {
		$this->nux->dismiss_notice( 'service_settings' );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
