<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Image_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Image_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/label/(?P<order_id>\d+)-(?P<label_id>\d+)/image';

	/**
	 * @var WC_Connect_API_Client
	 */
	protected $api_client;

	public function __construct( WC_Connect_API_Client $api_client ) {
		$this->api_client = $api_client;
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
		$raw_response = $this->api_client->get_label_image( $request[ 'label_id' ] );

		if ( is_wp_error( $raw_response ) ) {
			return $raw_response;
		}

		$response = new WP_REST_Response( $raw_response[ 'body' ] );
		$response->header( 'content-type', $raw_response[ 'headers' ][ 'content-type' ] );
		return $response;
	}

	/**
	 * Validate the requester's permissions
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
