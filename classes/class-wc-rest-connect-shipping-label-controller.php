<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/shipping-label';

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

	private function starts_with( $haystack, $needle ) {
		 return $needle === substr( $haystack, 0, strlen( $needle ) );
	}

	private function store_origin_address( $settings ) {
		$address = array();
		foreach( $settings as $key => $value ) {
			if ( $this->starts_with( $key, 'orig_' ) ) {
				$raw_field_name = substr( $key, strlen( 'orig_' ) );
				$address[ $raw_field_name ] = $value;
			}
		}
		$this->settings_store->update_origin_address( $address );
	}

	public function update_items( $request ) {
		$request_body = $request->get_body();
		$settings = json_decode( $request_body, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );

		$response = $this->api_client->send_shipping_label_request( $settings );

		if ( true /* TODO: Trigger this only if the origin address correctly validates */ ) {
			$this->store_origin_address( $settings );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$labels_data = array();
		foreach ( $response->labels as $index => $label_data ) {
			if ( isset( $label_data->error ) ) {
				return new WP_Error(
					$label_data->error->code,
					$label_data->error->message,
					array( 'message' => $label_data->error->message )
				);
			}
			$labels_data[] = $label_data->label;
		}

		return array(
			'labels' => $labels_data,
			'success' => true,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
