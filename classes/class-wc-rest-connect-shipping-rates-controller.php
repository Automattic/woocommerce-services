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
	 * Expected in request body:
	 * array(
	 *	'instance' => 30,
	 *	'contents' => array(
	 *		array(
	 * 			'data' => array(
	 *				'id' => 1,
	 *				'variation_id' => 2,
	 *			),
	 *			'quantity' => 1,
	 *		),
	 * 	),
	 *	'destination' => array(
	 *		'address' => '1550 Snow Creek Dr',
	 *		'address_2' => '',
	 *		'city' => 'Park City',
	 *		'state' => 'UT',
	 *		'postcode' => '84060',
	 *		'country' => 'US',
	 *	),
	 * )
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function update_items( $request ) {
		$request_body = $request->get_body();
		$settings     = json_decode( $request_body, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );

		// Get shipping method instance id
		$instance_id  = absint( $settings[ 'instance' ] );

		// WC_Shipping_Method::calculate_rates() expects 'data' to be an object
		foreach ( $settings[ 'contents' ] as $i => $content ) {
			$settings[ 'contents' ][ $i ][ 'data' ] = (object) $settings[ 'contents' ][ $i ][ 'data' ];
		}

		// Instantiate Connect Shipping Method and get rates
		$connect_shipping_method = new WC_Connect_Shipping_Method( $instance_id );
		$rates = $connect_shipping_method->get_rates_for_package( $settings );

		$processed_rates = array();

		// Add `service_id` to rates for selection purposes
		foreach ( $rates as $rate ) {
			$rate_meta = $rate->get_meta_data();
			$rate_packages = json_decode( $rate_meta[ 'wc_connect_packages' ], true );

			$processed_rates[] = array(
				'id'         => $rate->id,
				'label'      => $rate->label,
				'cost'       => $rate->cost,
				'method_id'  => $rate->method_id,
				'service_id' => $rate_packages[ 0 ][ 'service_id' ],
			);
		}

		return array(
			'success' => true,
			'rates'   => $processed_rates,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
