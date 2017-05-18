<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Print_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Print_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/print';

	public function get( $request ) {
		$raw_params = $request->get_params();
		$params = array();

		$params[ 'paper_size' ] = $raw_params[ 'paper_size' ];
		$this->settings_store->set_preferred_paper_size( $params[ 'paper_size' ] );

		$n_label_ids = isset( $raw_params[ 'label_ids' ] ) ? count( $raw_params[ 'label_ids' ] ) : 0;
		$n_captions = isset( $raw_params[ 'captions' ] ) ? count( $raw_params[ 'captions' ] ) : 0;
		// Either there are the same number of captions as labels, or no captions at all
		if ( ! $n_label_ids || ( $n_captions && $n_captions !== $n_label_ids ) ) {
			$message = __( 'Invalid PDF request.', 'woocommerce-services' );
			$error = new WP_Error(
				'invalid_pdf_request',
				$message,
				array(
					'message' => $message,
					'status' => 400
				)
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}
		$params[ 'labels' ] = array();
		for ( $i = 0; $i < $n_label_ids; $i++ ) {
			$params[ 'labels' ][ $i ] = array();
			$params[ 'labels' ][ $i ][ 'label_id' ] = (int) $raw_params[ 'label_ids' ][ $i ];

			if ( $n_captions ) {
				$params[ 'labels' ][ $i ][ 'caption' ] = $raw_params[ 'captions' ][ $i ];
			}
		}

		$raw_response = $this->api_client->get_labels_print_pdf( $params );

		if ( is_wp_error( $raw_response ) ) {
			$this->logger->debug( $raw_response, __CLASS__ );
			return $raw_response;
		}

		header( 'content-type: ' . $raw_response[ 'headers' ][ 'content-type' ] );
		echo $raw_response[ 'body' ];
		die();
	}

}
