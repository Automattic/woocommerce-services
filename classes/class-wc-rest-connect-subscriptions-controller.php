<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

if ( class_exists( 'WC_REST_Connect_Subscriptions_Controller' ) ) {
	return;
}

/**
 * REST controller for retrieving the WooCommerce.com subscriptions.
 */
class WC_REST_Connect_Subscriptions_Controller extends WC_REST_Connect_Base_Controller {
	/**
	 * The REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/subscriptions';

	/**
	 * Retrieve the WooCommerce.com subscriptions for the connected account.
	 *
	 * @return WP_REST_Response|WP_Error The subscriptions response, or a WP_Error on failure.
	 */
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
