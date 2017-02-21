<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Packages_Controller' ) ) {
	return;
}

class WC_REST_Connect_Packages_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/packages';

	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $service_settings_store;

	/**
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	public function __construct( WC_Connect_Service_Settings_Store $service_settings_store, WC_Connect_Logger $logger ) {
		$this->service_settings_store = $service_settings_store;
		$this->logger = $logger;
	}

	/**
	 * Register the routes for settings.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'update_items_permissions_check' ),
			),
		) );
	}

	public function update_items( $request ) {
		$packages = $request->get_json_params();

		$this->service_settings_store->update_packages( $packages[ 'custom' ] );
		$this->service_settings_store->update_predefined_packages( $packages[ 'predefined' ] );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
