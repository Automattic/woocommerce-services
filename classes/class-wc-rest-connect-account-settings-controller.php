<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Account_Settings_Controller' ) ) {
	return;
}

class WC_REST_Connect_Account_Settings_Controller extends WC_REST_Connect_Base_Controller {

	protected $rest_base = 'connect/account/settings';

	/*
	 * @var WC_Connect_Payment_Methods_Store
	 */
	protected $payment_methods_store;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Payment_Methods_Store $payment_methods_store ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->payment_methods_store = $payment_methods_store;
	}

	public function get() {
		// Always get a fresh copy when hitting this endpoint
		$payment_methods_fetched = $this->payment_methods_store->fetch_payment_methods_from_connect_server();

		$master_user = WC_Connect_Jetpack::get_master_user();
		if ( is_a( $master_user, 'WP_User' ) ) {
			$connected_data = WC_Connect_Jetpack::get_connected_user_data( $master_user->ID );
			$email = $connected_data['email'];
		} else {
			$email = '';
		}

		return new WP_REST_Response( array(
			'success'  => true,
			'storeOptions' => $this->settings_store->get_store_options(),
			'formData' => $this->settings_store->get_account_settings(),
			'formMeta' => array(
				'can_manage_payments' => $this->can_user_manage_payment_methods(),
				'can_edit_settings' => true,
				'master_user_name' => is_a( $master_user, 'WP_User' ) ? $master_user->display_name : '',
				'master_user_login' => is_a( $master_user, 'WP_User' ) ? $master_user->user_login : '',
				'master_user_email' => $email,
				'payment_methods' => $this->payment_methods_store->get_payment_methods(),
				'warnings' => array( 'payment_methods' => $payment_methods_fetched ? false : __( 'There was a problem fetching your saved credit cards.', 'woocommerce-services' ) ),
			),
		), 200 );
	}

	public function post( $request ) {
		$settings = $request->get_json_params();

		if ( ! $this->can_user_manage_payment_methods() ) {
			// Ignore the user-provided payment method ID if he doesn't have permission to change it
			$old_settings = $this->settings_store->get_account_settings();
			$settings['selected_payment_method_id'] = $old_settings['selected_payment_method_id'];
		}

		$result = $this->settings_store->update_account_settings( $settings );

		if ( is_wp_error( $result ) ) {
			$error = new WP_Error( 'save_failed',
				sprintf(
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

	private function can_user_manage_payment_methods() {
		global $current_user;
		$master_user = WC_Connect_Jetpack::get_master_user();
		return WC_Connect_Jetpack::is_development_mode() ||
			( is_a( $master_user, 'WP_User' ) && $current_user->ID === $master_user->ID );
	}
}
