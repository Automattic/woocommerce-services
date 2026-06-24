<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Account_Settings_Controller' ) ) {
	return;
}

/**
 * Handles the connect account settings REST endpoint.
 */
class WC_REST_Connect_Account_Settings_Controller extends WC_REST_Connect_Base_Controller {

	/**
	 * The REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/account/settings';

	/**
	 * The payment methods store instance.
	 *
	 * @var WC_Connect_Payment_Methods_Store
	 */
	protected $payment_methods_store;

	/**
	 * The account settings instance.
	 *
	 * @var WC_Connect_Account_Settings
	 */
	protected $account_settings;

	/**
	 * Constructor.
	 *
	 * @param WC_Connect_API_Client             $api_client            The API client instance.
	 * @param WC_Connect_Service_Settings_Store $settings_store        The service settings store instance.
	 * @param WC_Connect_Logger                 $logger                The logger instance.
	 * @param WC_Connect_Payment_Methods_Store  $payment_methods_store The payment methods store instance.
	 */
	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Payment_Methods_Store $payment_methods_store ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->payment_methods_store = $payment_methods_store;

		$this->account_settings = new WC_Connect_Account_Settings(
			$settings_store,
			$payment_methods_store
		);
	}

	/**
	 * Get the account settings.
	 *
	 * @return WP_REST_Response The account settings response.
	 */
	public function get() {
		return new WP_REST_Response(
			array_merge(
				array( 'success' => true ),
				$this->account_settings->get()
			),
			200
		);
	}

	/**
	 * Update the account settings.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_Error|WP_REST_Response The response on success, or a WP_Error on failure.
	 */
	public function post( $request ) {
		$settings = $request->get_json_params();

		if ( ! $this->settings_store->can_user_manage_payment_methods() ) {
			// Ignore the user-provided payment method ID if they don't have permission to change it.
			$old_settings                           = $this->settings_store->get_account_settings();
			$settings['selected_payment_method_id'] = $old_settings['selected_payment_method_id'];
		}

		$result = $this->settings_store->update_account_settings( $settings );

		if ( is_wp_error( $result ) ) {
			$error = new WP_Error(
				'save_failed',
				sprintf(
					/* translators: %s: error message */
					__( 'Unable to update settings. %s', 'woocommerce-services' ),
					$result->get_error_message()
				),
				array_merge(
					array( 'status' => 400 ),
					$result->get_error_data()
				)
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
