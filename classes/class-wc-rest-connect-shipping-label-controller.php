<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)';

	/*
	 * @var WC_Connect_Shipping_Label
	 */
	protected $shipping_label;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Shipping_Label $shipping_label ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->shipping_label = $shipping_label;
	}

	public function get( $request ) {
		$order_id = $request[ 'order_id' ];
		$payload = $this->shipping_label->get_label_payload( $order_id );
		if ( ! $payload ) {
			return new WP_Error( 'not_found', __( 'Order not found', 'woocommerce-services' ), array( 'status' => 404 ) );
		}
		$payload[ 'success' ] = true;
		return new WP_REST_Response( $payload, 200 );
	}

	public function post( $request ) {
		$settings = $request->get_json_params();
		$order_id = $request[ 'order_id' ];
		$settings[ 'order_id' ] = $order_id;

		if ( empty( $settings[ 'payment_method_id' ] ) || ! $this->settings_store->can_user_manage_payment_methods() ) {
			$settings[ 'payment_method_id' ] = $this->settings_store->get_selected_payment_method_id();
		} else {
			$this->settings_store->set_selected_payment_method_id( $settings[ 'payment_method_id' ] );
		}

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
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		$label_ids = array();
		$purchased_labels_meta = array();
		$package_lookup = $this->settings_store->get_package_lookup();
		foreach ( $response->labels as $index => $label_data ) {
			if ( isset( $label_data->error ) ) {
				$error = new WP_Error(
					$label_data->error->code,
					$label_data->error->message,
					array( 'message' => $label_data->error->message )
				);
				$this->logger->log( $error, __CLASS__ );
				return $error;
			}
			$label_ids[] = $label_data->label->label_id;

			$label_meta = array(
				'label_id' => $label_data->label->label_id,
				'tracking' => $label_data->label->tracking_id,
				'refundable_amount' => $label_data->label->refundable_amount,
				'created' => $label_data->label->created,
				'carrier_id' => $label_data->label->carrier_id,
				'service_name' => $service_names[ $index ],
				'status' => $label_data->label->status,
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
				$product = wc_get_product( $product_id );

				if ( $product ) {
					$product_names[] = $product->get_title();
				} else {
					$order = wc_get_order( $order_id );
					$product_names[] = WC_Connect_Compatibility::instance()->get_product_name_from_order( $product_id, $order );
				}
			}

			$label_meta[ 'product_names' ] = $product_names;

			array_unshift( $purchased_labels_meta, $label_meta );
		}

		$this->settings_store->add_labels_to_order( $order_id, $purchased_labels_meta );

		return array(
			'labels' => $purchased_labels_meta,
			'success' => true,
		);
	}

}
