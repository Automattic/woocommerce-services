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
		$carrier  = 'usps'; //TODO: remove hardcoding
		$settings = $request->get_json_params();
		$order_id = $settings[ 'order_id' ];

		$settings[ 'payment_method_id' ] = $this->settings_store->get_selected_payment_method_id();
		$settings[ 'carrier' ] = $carrier;
		$settings[ 'label_size' ] = 'default';
		$settings[ 'ship_date' ] = date( 'Y-m-d', time() + 86400 ); // tomorrow

		$service_names = array();
		foreach ( $settings[ 'packages' ] as $index => $package ) {
			$service_names[] = $package[ 'service_name' ];
			unset( $package[ 'service_name' ] );
			$settings[ 'packages' ][ $index ] = $package;
		}

		$response = $this->api_client->send_shipping_label_request( $settings );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		$label_ids = array();
		$labels_order_meta = array();
		$purchased_labels_meta = array();
		$existing_labels_data = get_post_meta( $order_id, 'wc_connect_labels', true );
		if ( $existing_labels_data ) {
			$labels_order_meta = json_decode( $existing_labels_data, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );
		}
		$package_lookup = $this->settings_store->get_package_lookup_for_service( $carrier );
		foreach ( $response->labels as $index => $label_data ) {
			if ( isset( $label_data->error ) ) {
				$error = new WP_Error(
					$label_data->error->code,
					$label_data->error->message,
					array( 'message' => $label_data->error->message )
				);
				$this->logger->debug( $error, __CLASS__ );
				return $error;
			}
			$label_ids[] = $label_data->label->label_id;

			$label_meta = array(
				'label_id' => $label_data->label->label_id,
				'tracking' => $label_data->label->tracking_id,
				'refundable_amount' => $label_data->label->refundable_amount,
				'created' => $label_data->label->created,
				'carrier_id' => $settings[ 'carrier' ],
				'service_name' => $service_names[ $index ],
			);

			$package = $settings[ 'packages' ][ $index ];
			$box_id = $package[ 'box_id' ];
			if ( 'individual' === $box_id ) {
				$label_meta[ 'package_name' ] = __( 'Individual packaging', 'woocommerce-services' );
			} else if ( isset( $package_lookup[ $box_id ] ) ) {
				$label_meta[ 'package_name' ] = $package_lookup[ $box_id ][ 'name' ];
			} else {
				$label_meta[ 'package_name' ] = __( 'Unknown package', 'woocommerce-services' );
			}

			$product_names = array();
			foreach ( $package[ 'products' ] as $product_id ) {
				$product =  wc_get_product( $product_id );
				if ( ! isset( $product ) ) {
					continue;
				}

				$product_names[] = $product->get_title();
			}

			$label_meta[ 'product_names' ] = $product_names;

			array_unshift( $labels_order_meta, $label_meta );
			array_unshift( $purchased_labels_meta, $label_meta );
		}

		update_post_meta( $order_id, 'wc_connect_labels', json_encode( $labels_order_meta ) );

		return array(
			'labels' => $purchased_labels_meta,
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
