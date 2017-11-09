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

		$label_ids = ! empty( $raw_params[ 'label_id_csv' ] ) ? explode( ',', $raw_params[ 'label_id_csv' ] ) : array();
		$n_label_ids = count( $label_ids );
		$captions = ! empty( $raw_params[ 'caption_csv' ] ) ? explode( ',', $raw_params[ 'caption_csv' ] ) : array();
		$n_captions = count( $captions );
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
			$params[ 'labels' ][ $i ][ 'label_id' ] = (int) $label_ids[ $i ];

			if ( $n_captions ) {
				$params[ 'labels' ][ $i ][ 'caption' ] = urldecode( $captions[ $i ] );
			}
		}

		$raw_response = $this->api_client->get_labels_print_pdf( $params );

		if ( is_wp_error( $raw_response ) ) {
			$this->logger->debug( $raw_response, __CLASS__ );
			return $raw_response;
		}

		if ( isset( $raw_params[ 'json' ] ) && $raw_params[ 'json' ] ) {
			return array(
				'mimeType' => $raw_response[ 'headers' ][ 'content-type' ],
				'b64Content' => base64_encode( $raw_response[ 'body' ] ),
				'success' => true,
			);
		} else {
			header( 'content-type: ' . $raw_response[ 'headers' ][ 'content-type' ] );
			echo $raw_response[ 'body' ];
			die();
		}
	}

}
