<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Preview_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Preview_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/preview';

	public function get( $request ) {
		$raw_params = $request->get_params();
		$params     = array();

		$params['paper_size'] = $raw_params['paper_size'];
		$this->settings_store->set_preferred_paper_size( $params['paper_size'] );
		$params['carrier'] = 'usps';
		$params['labels']  = array();
		$captions          = empty( $raw_params['caption_csv'] ) ? array() : explode( ',', $raw_params['caption_csv'] );

		foreach ( $captions as $caption ) {
			$params['labels'][] = array( 'caption' => urldecode( $caption ) );
		}

		$raw_response = $this->api_client->get_labels_preview_pdf( $params );

		if ( is_wp_error( $raw_response ) ) {
			$this->logger->log( $raw_response, __CLASS__ );
			return $raw_response;
		}

		header( 'content-type: ' . $raw_response['headers']['content-type'] );
		echo $raw_response['body'];
		die();
	}

}
