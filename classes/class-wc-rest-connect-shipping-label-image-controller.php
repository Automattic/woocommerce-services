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
	protected $rest_base = 'connect/label/pdf';

	/**
	 * @var WC_Connect_API_Client
	 */
	protected $api_client;

	/**
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Logger $logger ) {
		$this->api_client = $api_client;
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
		$params = $request->get_params();
		$params[ 'carrier' ] = 'usps';
		$raw_response = $this->api_client->get_label_pdf( $params );

		if ( is_wp_error( $raw_response ) ) {
			$this->logger->log( $raw_response, __CLASS__ );
			return $raw_response;
		}

		header( 'content-type: ' . $raw_response[ 'headers' ][ 'content-type' ] );
		echo $raw_response[ 'body' ];
		die();
	}

	/**
	 * Validate the requester's permissions
	 */
	public function get_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
