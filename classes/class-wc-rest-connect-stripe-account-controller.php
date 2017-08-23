<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Stripe_Account_Controller' ) ) {
	return;
}

class WC_REST_Connect_Stripe_Account_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/stripe/account';

	public function post( $request ) {
		$data = $request->get_json_params();

		$response = $this->api_client->create_stripe_account( $data['email'], $data['country'] );

		if ( is_wp_error( $response ) ) {
			$this->logger->debug( $response, __CLASS__ );
			return $response;
		}

		return new WP_REST_Response( array(
			'success'         => true,
			'account_id'      => $response->accountId,
			'publishable_key' => $response->publishableKey,
			'secret_key'      => $response->secretKey,
		), 200 );
	}
}
