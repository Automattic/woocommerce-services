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
	protected $rest_base = 'connect/label/purchase';

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
		$request_body = $request->get_body();
		$settings = json_decode( $request_body, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );
		$order_id = $settings[ 'order_id' ];

		$settings[ 'payment_method_id' ] = $this->settings_store->get_selected_payment_method_id();
		$settings[ 'carrier' ] = 'usps';
		$settings[ 'label_size' ] = 'default';
		$settings[ 'ship_date' ] = date( 'Y-m-d', time() + 86400 ); // tomorrow

		$response = $this->api_client->send_shipping_label_request( $settings );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
		}

		$labels_order_meta = array();
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
			$labels_order_meta[] = array(
				'label_id' => $label_data->label->label_id,
				'provider_label_id' => $label_data->label->provider_label_id,
				'refundable_amount' => $label_data->label->refundable_amount,
				'created' => $label_data->label->created,
				'carrier_id' => $settings[ 'carrier' ],
				'service_name' => 'TODO: Not implemented yet',
			);
		}

		update_post_meta( $order_id, 'wc_connect_labels', json_encode( $labels_order_meta ) );

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
