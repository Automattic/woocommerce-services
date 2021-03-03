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

		$custom_packages = isset($packages['custom']) ? $packages['custom'] : array();
		$predefined_packages = isset($packages['predefined']) ? $packages['predefined'] : array();

		// Handle new custom packages. The custom packages are structured as an array of packages as dictionaries.
		if (!empty($custom_packages)) {
			// Validate that no custom package has duplicate name.
			$existing_custom_packages = $this->settings_store->get_packages();
			$map_package_name = function($package) {
				return $package['name'];
			};
			$existing_custom_package_names = array_map($map_package_name, $existing_custom_packages);
			$custom_package_names = array_map($map_package_name, $custom_packages);
			$duplicate_package_names = array_intersect($existing_custom_package_names, $custom_package_names);

			if (!empty($duplicate_package_names)) {
				return new WP_Error(
					400,
					'invalid_custom_packages',
					array('duplicate_custom_package_names' => array_values($duplicate_package_names))
				);
			}

			// If no duplicate custom packages, create the given packages.
			$this->settings_store->create_packages( $custom_packages );
		}

		// Handle new predefined packages. The predefined packages are structured as a dictionary from carrier name to
		// an array of package names.
		if (!empty($predefined_packages)) {
			// Validate that no predefined package has duplicate name for the carrier.
			$existing_predefined_packages = $this->settings_store->get_predefined_packages();
			$duplicate_package_names_by_carrier = array();

			if (!empty($existing_predefined_packages)) {
				foreach ($existing_predefined_packages as $carrier => $existing_package_names) {
					$new_package_names = isset($predefined_packages[$carrier]) ? $predefined_packages[$carrier]: array();
					$duplicate_package_names = array_intersect($existing_package_names, $new_package_names);
					if (!empty($duplicate_package_names)) {
						$duplicate_package_names_by_carrier[$carrier] = array_values($duplicate_package_names);
					}
				}
			}

			if (!empty($duplicate_package_names_by_carrier)) {
				return new WP_Error(
					400,
					'invalid_predefined_packages',
					array('duplicate_predefined_package_names_by_carrier' => $duplicate_package_names_by_carrier)
				);
			}

			// If no duplicate predefined packages, create the given packages.
			$this->settings_store->create_predefined_packages( $predefined_packages );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
