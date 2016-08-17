<?php

if ( ! class_exists( 'WC_Connect_Payment_Methods_Store' ) ) {

	class WC_Connect_Payment_Methods_Store {

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Logger $logger ) {

			$this->api_client = $api_client;
			$this->logger     = $logger;

		}

		public function fetch_payment_methods_from_connect_server() {

			$response_body = $this->api_client->get_payment_methods();

			if ( is_wp_error( $response_body ) ) {
				$this->logger->log( $response_body, __FUNCTION__ );
				return;
			}

			$payment_methods = $this->get_payment_methods_from_response_body( $response_body );
			if ( is_wp_error( $payment_methods ) ) {
				$this->logger->log( $payment_methods, __FUNCTION__ );
				return;
			}

			$this->logger->log( 'Successfully loaded payment methods from server response.', __FUNCTION__ );
			$this->update_last_fetch_timestamp();

			// If we made it this far, it is safe to store the object
			$this->update_payment_methods( $payment_methods );
		}

		public function get_payment_methods() {
			return get_option( 'wc_connect_payment_methods', array() );
		}

		protected function update_payment_methods( $payment_methods ) {
			update_option( 'wc_connect_payment_methods', $payment_methods );
		}

		public function get_last_fetch_timestamp() {
			return get_option( 'wc_connect_payment_methods_last_update', null );
		}

		protected function update_last_fetch_timestamp() {
			update_option( 'wc_connect_payment_methods_last_update', time() );
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
