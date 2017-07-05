<?php

if ( ! class_exists( 'WC_Connect_Payment_Methods_Store' ) ) {

	class WC_Connect_Payment_Methods_Store {

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		public function __construct( WC_Connect_Service_Settings_Store $service_settings_store,
			WC_Connect_API_Client $api_client, WC_Connect_Logger $logger ) {

			$this->service_settings_store = $service_settings_store;
			$this->api_client             = $api_client;
			$this->logger                 = $logger;

		}

		public function fetch_payment_methods_from_connect_server() {

			$response_body = $this->api_client->get_payment_methods();

			if ( is_wp_error( $response_body ) ) {
				$this->logger->debug( $response_body, __FUNCTION__ );
				return;
			}

			$payment_methods = $this->get_payment_methods_from_response_body( $response_body );
			if ( is_wp_error( $payment_methods ) ) {
				$this->logger->debug( $payment_methods, __FUNCTION__ );
				return;
			}

			// If we made it this far, it is safe to store the object
			$this->update_payment_methods( $payment_methods );

			$this->potentially_update_selected_payment_method_from_payment_methods( $payment_methods );
		}

		protected function potentially_update_selected_payment_method_from_payment_methods( $payment_methods ) {
			$payment_method_ids = array();

			foreach ( (array) $payment_methods as $payment_method ) {
				$payment_method_id = intval( $payment_method->payment_method_id );

				if ( 0 !== $payment_method_id ) {
					$payment_method_ids[] = $payment_method_id;
				}
			}

			// No payment methods at all? Clear anything we have stored
			if ( 0 === count( $payment_method_ids ) ) {
				$this->service_settings_store->set_selected_payment_method_id( 0 );
				return;
			}

			// Has the stored method ID been removed? Select the first available one
			$selected_payment_method_id = $this->service_settings_store->get_selected_payment_method_id();
			if (
				$selected_payment_method_id &&
				! in_array( $selected_payment_method_id, $payment_method_ids )
			) {
				$this->service_settings_store->set_selected_payment_method_id( $payment_method_ids[ 0 ] );
			}
		}

		public function get_payment_methods() {
			return WC_Connect_Options::get_option( 'payment_methods', array() );
		}

		protected function update_payment_methods( $payment_methods ) {
			WC_Connect_Options::update_option( 'payment_methods', $payment_methods );
		}

		protected function get_payment_methods_from_response_body( $response_body ) {
			if ( ! is_object( $response_body ) ) {
				return new WP_Error( 'payment_method_response_body_type', 'Expected but did not receive object for response body.' );
			}

			if ( ! property_exists( $response_body, 'payment_methods' ) ) {
				return new WP_Error( 'payment_method_response_body_missing_payment_methods', 'Expected but did not receive payment_methods in response body.' );
			}

			$payment_methods = $response_body->payment_methods;
			if ( ! is_array( $payment_methods ) ) {
				return new WP_Error( 'payment_methods_type', 'Expected but did not receive array for payment_methods.' );
			}

			foreach ( (array) $payment_methods as $payment_method ) {
				$required_keys = array( 'payment_method_id', 'name', 'card_type', 'card_digits', 'expiry' );
				foreach ( (array) $required_keys as $required_key ) {
					if ( ! array_key_exists( $required_key, $payment_method ) ) {
						return new WP_Error( 'payment_methods_key_missing', 'Payment method array element is missing a required key' );
					}
				}
			}

			return $payment_methods;
		}
	}
}
