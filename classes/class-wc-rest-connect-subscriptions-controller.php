<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Subscriptions_Controller' ) ) {
	return;
}

class WC_REST_Connect_Subscriptions_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/subscriptions';

	public function post() {
		$response = $this->api_client->get_wccom_subscriptions();
		if ( is_wp_error( $response ) ) {
			$this->logger->log( $response, __CLASS__ );
			return $response;
		}

		return new WP_REST_Response(
			array(
				'success'       => true,
				'subscriptions' => $response->subscriptions,
			)
		);
	}
}
