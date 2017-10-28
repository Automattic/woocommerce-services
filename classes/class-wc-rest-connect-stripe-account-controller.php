<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Stripe_Account_Controller' ) ) {
	return;
}

class WC_REST_Connect_Stripe_Account_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/stripe/account';
	private $stripe;

	public function __construct( WC_Connect_Stripe $stripe, WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->stripe = $stripe;
	}

	public function get( $request ) {
		$response = $this->stripe->get_account_details();

		if ( is_wp_error( $response ) ) {
			$this->logger->debug( $response, __CLASS__ );

			return new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array(
					'status' => 400
				)
			);
		}

		return array(
			'success'         => true,
			'account_id'      => $response->accountId,
			'display_name'    => $response->displayName,
			'email'           => $response->email,
			'business_logo'   => $response->businessLogo,
			'legal_entity'    => array(
				'first_name'      => $response->legalEntity->firstName,
				'last_name'       => $response->legalEntity->lastName
			),
			'payouts_enabled' => $response->payoutsEnabled
		);

	}

	public function post( $request ) {
		$data = $request->get_json_params();

		$response = $this->stripe->create_account( $data['email'], $data['country'] );

		if ( is_wp_error( $response ) ) {
			$this->logger->debug( $response, __CLASS__ );

			return new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array(
					'status' => 400
				)
			);
		}

		return array(
			'success'         => true,
			'account_id'      => $response->accountId,
		);
	}
}
