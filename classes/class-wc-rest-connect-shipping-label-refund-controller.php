<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Refund_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Refund_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)/(?P<label_id>\d+)/refund';

	public function post( $request ) {
		$order = $this->get_order_for_label_request( $request['order_id'] );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		// Never refund a label that is not this order's.
		if ( ! $this->is_label_on_order( $order, $request['label_id'] ) ) {
			return new WP_Error( 'not_found', __( 'Shipping label not found', 'woocommerce-services' ), array( 'status' => 404 ) );
		}

		$response = $this->api_client->send_shipping_label_refund_request( $request['label_id'] );

		if ( isset( $response->error ) ) {
			$response = new WP_Error(
				property_exists( $response->error, 'code' ) ? $response->error->code : 'refund_error',
				property_exists( $response->error, 'message' ) ? $response->error->message : ''
			);
		}

		if ( is_wp_error( $response ) ) {
			$response->add_data(
				array(
					'message' => $response->get_error_message(),
				),
				$response->get_error_code()
			);

			$this->logger->log( $response, __CLASS__ );
			return $response;
		}

		$label_refund = (object) array(
			'label_id' => (int) $response->label->id,
			'refund'   => $response->refund,
		);
		$this->settings_store->update_label_order_meta_data( $order->get_id(), $label_refund );

		return array(
			'success' => true,
			'refund'  => $response->refund,
		);
	}

}
