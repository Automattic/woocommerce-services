<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Status_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Status_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)/(?P<label_id>\d+)';

	public function get( $request ) {
		$response = $this->api_client->get_label_status( $request[ 'label_id' ] );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		$label = $this->settings_store->update_label_order_meta_data( $request[ 'order_id' ], $response->label );

		return array(
			'success' => true,
			'label' => $label,
		);
	}

}
