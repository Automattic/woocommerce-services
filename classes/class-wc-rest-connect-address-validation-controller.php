<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Address_Validation_Controller' ) ) {
	return;
}

class WC_REST_Connect_Address_Validation_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/validate-address';

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

	public function update_items( $request ) {
		$request = json_decode( $request->get_body(), false, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );
		$name = $request->address->name;
		unset( $request->address->name );
		$company = $request->address->company;
		unset( $request->address->company );

		$body = array(
			'destination' => $request->address,
			'carrier' => 'usps',
		);
		$response = $this->api_client->send_address_validation_request( $body );

		if ( isset( $response->error ) ) {
			return new WP_Error(
				$response->error->code,
				$response->error->message,
				array( 'message' => $response->error->message )
			);
		}

		$response->normalized->name = $name;
		$response->normalized->company = $company;

		if ( 'origin' === $request->type ) {
			$this->settings_store->update_origin_address( $response->normalized );
		}

		return array(
			'success' => true,
			'normalized' => $response->normalized,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return true; // non-authenticated service
	}

}
