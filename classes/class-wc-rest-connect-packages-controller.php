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
	 * @var WC_Connect_Package_Settings
	 */
	protected $package_settings;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Service_Schemas_Store $service_schemas_store ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->package_settings = new WC_Connect_Package_Settings(
			$settings_store,
			$service_schemas_store
		);
	}

	public function get() {
		return new WP_REST_Response(
			array_merge(
				array( 'success' => true ),
				$this->package_settings->get()
			),
			200
		);
	}

	public function put( $request ) {
		$packages = $request->get_json_params();

		$this->settings_store->update_packages( $packages['custom'] );
		$this->settings_store->update_predefined_packages( $packages['predefined'] );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function post( $request ) {
		$packages = $request->get_json_params();

		$custom_packages = isset($packages['custom']) ? $packages['custom'] : null;
		$predefined_packages = isset($packages['predefined']) ? $packages['predefined'] : null;
		if (!is_null($custom_packages)) {
			$this->settings_store->create_packages( $custom_packages );
		}
		if (!is_null($predefined_packages)) {
			$this->settings_store->create_predefined_packages( $predefined_packages );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
