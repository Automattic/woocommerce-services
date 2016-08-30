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
	 *	'contents' => array(
	 *		array(
	 * 			'product_id' => 403,
	 *			'height' => 10,
	 *			'length' => 10,
	 *			'width' => 10,
	 *			'weight' => 10,
	 *			'quantity' => 1,
	 *		),
	 *		...
	 * 	),
	 *	'origin' => array(
	 *		'address' => '132 Hawthorne St',
	 *		'address_2' => '',
	 *		'city' => 'San Francisco',
	 *		'state' => 'CA',
	 *		'postcode' => '94107',
	 *		'country' => 'US',
	 *	),
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

		// WC_Connect_API_Client::get_shipping_rates() expects 'data' to be an object
		foreach ( $settings[ 'contents' ] as $i => $content ) {
			$settings[ 'contents' ][ $i ][ 'data' ] = (object) $settings[ 'contents' ][ $i ][ 'data' ];
		}

		// Force USPS for now
		$services = array(
			array(
				'id'               => 'usps',
				'instance'         => -1,
				'service_settings' => array(
					'title'      => 'USPS rates (for labels)',
					'account_id' => '',
					'origin'     => (string) $settings[ 'origin' ][ 'postcode' ]
				),
			),
		);

		$response        = $this->api_client->get_shipping_rates( $services, $settings );
		$processed_rates = array();

		// Add `service_id` to rates for selection purposes
		foreach ( (array) $response->rates as $instance ) {
			if ( ! property_exists( $instance, 'rates' ) ) {
				continue;
			}

			foreach ( (array) $instance->rates as $rate_idx => $rate ) {
				$rate_id    = WC_Connect_Shipping_Method::format_rate_id( $instance->id, $instance->instance, $rate_idx );
				$rate_label = WC_Connect_Shipping_Method::format_rate_title( $rate->title );

				$processed_rates[] = array(
					'id'         => $rate_id,
					'label'      => $rate_label,
					'cost'       => $rate->rate,
					'method_id'  => 'usps',
					'service_id' => $rate->packages[ 0 ]->service_id,
				);
			}
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
