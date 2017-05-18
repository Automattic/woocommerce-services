<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Packages_Controller' ) ) {
	return;
}

class WC_REST_Connect_Packages_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/packages';

	/*
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $service_schemas_store;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Service_Schemas_Store $service_schemas_store ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->service_schemas_store = $service_schemas_store;
	}

	public function get() {
		return new WP_REST_Response( array(
			'success'  => true,
			'storeOptions' => $this->settings_store->get_store_options(),
			'formSchema'   => array(
				'custom' => $this->service_schemas_store->get_packages_schema(),
				'predefined' => $this->service_schemas_store->get_predefined_packages_schema()
			),
			'formData'     => array(
				'custom' => $this->settings_store->get_packages(),
				'predefined' => $this->settings_store->get_predefined_packages()
			)
		), 200 );
	}

	public function post( $request ) {
		$packages = $request->get_json_params();

		$this->settings_store->update_packages( $packages[ 'custom' ] );
		$this->settings_store->update_predefined_packages( $packages[ 'predefined' ] );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
