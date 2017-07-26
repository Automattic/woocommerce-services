<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)';

	public function post( $request ) {
		$settings = $request->get_json_params();
		$order_id = $request[ 'order_id' ];

		$settings[ 'payment_method_id' ] = $this->settings_store->get_selected_payment_method_id();
		$settings[ 'ship_date' ] = date( 'Y-m-d', time() + 86400 ); // tomorrow
		$settings[ 'order_id' ] = $order_id;

		$labels_meta = array();
		$package_lookup = $this->settings_store->get_package_lookup();
		foreach ( $settings[ 'packages' ] as $index => $package ) {
			$service_name = $package[ 'service_name' ];
			unset( $package[ 'service_name' ] );
			$settings[ 'packages' ][ $index ] = $package;

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

			$box_id = $package[ 'box_id' ];
			if ( 'individual' === $box_id ) {
				$package_name = __( 'Individual packaging', 'woocommerce-services' );
			} else if ( isset( $package_lookup[ $box_id ] ) ) {
				$package_name = $package_lookup[ $box_id ][ 'name' ];
			} else {
				$package_name = __( 'Unknown package', 'woocommerce-services' );
			}

			$labels_meta[] = array(
				'service_name' => $service_name,
				'product_names' => $product_names,
				'package_name' => $package_name,
			);
		}

		$settings[ 'async' ] = true; // TODO: only make it async if the request comes from the Jetpack proxy?
		$response = $this->api_request( 'send_shipping_label_request', $settings );

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

			$labels_meta[ $index ] = array_merge( $labels_meta[ $index ], array(
				'label_id' => $label_data->label->label_id,
				'tracking' => $label_data->label->tracking_id,
				'refundable_amount' => $label_data->label->refundable_amount,
				'created' => $label_data->label->created,
				'carrier_id' => $label_data->label->carrier_id,
			 ) );
		}

		$this->settings_store->add_labels_to_order( $order_id, $labels_meta );

		return array(
			'labels' => $labels_meta,
			'success' => true,
		);
	}

}
