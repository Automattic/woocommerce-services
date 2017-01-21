<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Status_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Status_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/label/(?P<order_id>\d+)-(?P<label_id>\d+)';

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
	 * Register the routes for shipping labels printing.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
		) );
	}

	public function get_item( $request ) {
		$response = $this->api_client->get_label_status( $request[ 'label_id' ] );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		$this->settings_store->update_label_order_meta_data( $request[ 'order_id' ], $response->label );

		return array(
			'success' => true,
			'label' => $response->label,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
