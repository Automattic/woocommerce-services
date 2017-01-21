<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Refund_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Refund_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/label/(?P<order_id>\d+)-(?P<label_id>\d+)/refund';

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
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'update_items_permissions_check' ),
			),
		) );
	}

	public function update_items( $request ) {
		$response = $this->api_client->send_shipping_label_refund_request( $request[ 'label_id' ] );

		if ( isset( $response->error ) ) {
			$response = new WP_Error(
				property_exists( $response->error, 'code' ) ? $response->error->code : 'refund_error',
				property_exists( $response->error, 'message' ) ? $response->error->message : ''
			);
		}

		if ( is_wp_error( $response ) ) {
			$response->add_data( array(
				'message' => $response->get_error_message(),
			), $response->get_error_code() );

			$this->logger->debug( $response, __CLASS__ );
			return $response;
		}

		$label_refund = (object) array(
			'label_id' => (int) $response->label->id,
			'refund'   => $response->refund ,
		);
		$this->settings_store->update_label_order_meta_data( $request[ 'order_id' ], $label_refund );

		return array(
			'success' => true,
			'refund'   => $response->refund,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
