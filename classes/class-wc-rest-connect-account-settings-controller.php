<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Account_Settings_Controller' ) ) {
	return;
}

class WC_REST_Connect_Account_Settings_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/account/settings';

	/**
	 * @var WC_Connect_API_Client
	 */
	protected $api_client;

	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	/**
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger ) {
		$this->api_client = $api_client;
		$this->settings_store = $settings_store;
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

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
