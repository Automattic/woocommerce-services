<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Rates_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Rates_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/shipping-rates';

	/**
	 * @var WC_Connect_API_Client
	 */
	protected $api_client;

	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store ) {
		$this->api_client = $api_client;
		$this->settings_store = $settings_store;
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

	/**
	 *
	 * @param WP_REST_Request $request - See WC_Connect_API_Client::get_label_rates()
	 * @return array
	 */
	public function update_items( $request ) {
		$request_body = $request->get_body();
		$payload      = json_decode( $request_body, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );

		// Hardcode USPS rates for now
		$payload[ 'carrier' ] = 'usps';

		// Exclude extraneous package fields
		$whitelist = array_fill_keys( array( 'id', 'length', 'width', 'height', 'weight', 'template' ), true );

		foreach ( $payload[ 'packages' ] as $idx => $package ) {
			$payload[ 'packages' ][ $idx ] = array_intersect_key( $package, $whitelist );
		}

		$response = $this->api_client->get_label_rates( $payload );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'success' => true,
			'rates'   => property_exists( $response, 'rates' ) ? $response->rates : new stdClass(),
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
