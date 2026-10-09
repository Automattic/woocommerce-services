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

	/**
	 * Subscriptions belong to the whole store, so the label capability alone is not enough.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		return $this->check_store_permission( $request );
	}
}
