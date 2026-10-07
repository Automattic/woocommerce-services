<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Status_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Status_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)/(?P<label_ids>(\d+)(,\d+)*)';

	public function get( $request ) {
		$order = $this->get_order_for_label_request( $request['order_id'] );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$label_ids = explode( ',', $request['label_ids'] );

		// Every label must be this order's before any status is fetched.
		foreach ( $label_ids as $label_id ) {
			if ( ! $this->is_label_on_order( $order, $label_id ) ) {
				return new WP_Error( 'not_found', __( 'Shipping label not found', 'woocommerce-services' ), array( 'status' => 404 ) );
			}
		}

		$labels = array();
		foreach ( $label_ids as $label_id ) {
			$response = $this->api_client->get_label_status( $label_id );
			if ( is_wp_error( $response ) ) {
				$error = new WP_Error(
					$response->get_error_code(),
					$response->get_error_message(),
					array( 'message' => $response->get_error_message() )
				);
				$this->logger->log( $error, __CLASS__ );
				return $error;
			}

			$label    = $this->settings_store->update_label_order_meta_data( $order->get_id(), $response->label );
			$labels[] = $label;
		}

		return array(
			'success' => true,
			'labels'  => $labels,
		);
	}
}
