<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Packages_Controller' ) ) {
	return;
}

class WC_REST_Connect_Packages_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/packages';

	/**
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

	/**
	 * Update the existing custom and predefined packages.
	 *
	 * @param  WP_REST_Request $request The request body contains the custom/predefined packages to replace.
	 * @return WP_REST_Response
	 */
	public function put( $request ) {
		$packages = $request->get_json_params();

		$this->settings_store->update_packages( $packages['custom'] );
		$this->settings_store->update_predefined_packages( $packages['predefined'] );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Create custom and/or predefined packages.
	 *
	 * @param  WP_REST_Request $request The request body contains the custom/predefined packages to create.
	 * @return WP_Error|WP_REST_Response
	 */
	public function post( $request ) {
		$packages = $request->get_json_params();

		$custom_packages     = isset( $packages['custom'] ) ? $packages['custom'] : array();
		$predefined_packages = isset( $packages['predefined'] ) ? $packages['predefined'] : array();

		// Handle new custom packages. The custom packages are structured as an array of packages as dictionaries.
		if ( ! empty( $custom_packages ) ) {
			// Validate that the new custom packages have unique names.
			$map_package_name            = function( $package ) {
				return $package['name'];
			};
			$custom_package_names        = array_map( $map_package_name, $custom_packages );
			$unique_custom_package_names = array_unique( $custom_package_names );

			if ( count( $unique_custom_package_names ) < count( $custom_package_names ) ) {
				$duplicate_package_names = array_diff_assoc( $custom_package_names, $unique_custom_package_names );
				$error                   = array(
					'code'    => 'duplicate_custom_package_names',
					'message' => __( 'The new custom package names are not unique.' ),
					'data'    => array( 'package_names' => array_values( $duplicate_package_names ) ),
				);
				return new WP_REST_Response( $error, 400 );
			}

			// Validate that the new custom packages do not have the same names as existing custom packages.
			$existing_custom_packages      = $this->settings_store->get_packages();
			$existing_custom_package_names = array_map( $map_package_name, $existing_custom_packages );
			$duplicate_package_names       = array_intersect( $existing_custom_package_names, $custom_package_names );

			if ( ! empty( $duplicate_package_names ) ) {
				$error = array(
					'code'    => 'duplicate_custom_package_names_of_existing_packages',
					'message' => __( 'At least one of the new custom packages has the same name as existing packages.' ),
					'data'    => array( 'package_names' => array_values( $duplicate_package_names ) ),
				);
				return new WP_REST_Response( $error, 400 );
			}

			// If no duplicate custom packages, create the given packages.
			$this->settings_store->create_packages( $custom_packages );
		}

		// Handle new predefined packages. The predefined packages are structured as a dictionary from carrier name to
		// an array of package names.
		if ( ! empty( $predefined_packages ) ) {
			$duplicate_package_names_by_carrier = array();

			// Validate that the new predefined packages have unique names for each carrier.
			foreach ( $predefined_packages as $carrier => $package_names ) {
				$unique_package_names = array_unique( $package_names );
				if ( count( $unique_package_names ) < count( $package_names ) ) {
					$duplicate_package_names                        = array_diff_assoc( $package_names, $unique_package_names );
					$duplicate_package_names_by_carrier[ $carrier ] = array_values( $duplicate_package_names );
				}
			}

			if ( ! empty( $duplicate_package_names_by_carrier ) ) {
				$error = array(
					'code'    => 'duplicate_predefined_package_names',
					'message' => __( 'The new predefined package names are not unique.' ),
					'data'    => array( 'package_names_by_carrier' => $duplicate_package_names_by_carrier ),
				);
				return new WP_REST_Response( $error, 400 );
			}

			// Validate that the new predefined packages for each carrier do not have the same names as existing predefined packages.
			$existing_predefined_packages = $this->settings_store->get_predefined_packages();
			if ( ! empty( $existing_predefined_packages ) ) {
				foreach ( $existing_predefined_packages as $carrier => $existing_package_names ) {
					$new_package_names       = isset( $predefined_packages[ $carrier ] ) ? $predefined_packages[ $carrier ] : array();
					$duplicate_package_names = array_intersect( $existing_package_names, $new_package_names );
					if ( ! empty( $duplicate_package_names ) ) {
						$duplicate_package_names_by_carrier[ $carrier ] = array_values( $duplicate_package_names );
					}
				}
			}

			if ( ! empty( $duplicate_package_names_by_carrier ) ) {
				$error = array(
					'code'    => 'duplicate_predefined_package_names_of_existing_packages',
					'message' => __( 'At least one of the new predefined packages has the same name as existing packages.' ),
					'data'    => array( 'package_names_by_carrier' => $duplicate_package_names_by_carrier ),
				);
				return new WP_REST_Response( $error, 400 );
			}

			// If no duplicate predefined packages, create the given packages.
			$this->settings_store->create_predefined_packages( $predefined_packages );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
