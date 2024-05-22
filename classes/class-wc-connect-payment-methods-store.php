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

		/**
		 * Fetch stored payment methods from server and store in options.
		 *
		 * @return bool Were payment methods successfully retrieved?
		 */
		public function fetch_payment_methods_from_connect_server() {

			$response_body = $this->api_client->get_payment_methods();

			if ( is_wp_error( $response_body ) ) {
				$this->logger->log( $response_body, __FUNCTION__ );
				return false;
			}

			$validation = $this->validate_payment_methods_response( $response_body );
			if ( is_wp_error( $validation ) ) {
				$this->logger->log( sprintf( '[%s] %s', $validation->get_error_code(), $validation->get_error_message() ), __FUNCTION__ );
				return false;
			}

			// Get add payment method url from response body.
			$add_payment_method_url = $this->get_add_payment_method_url_from_response_body( $response_body );
			$payment_methods        = $this->get_payment_methods_from_response_body( $response_body );

			// Store the payment methods and add payment method url.
			$this->update_add_payment_method_url( $add_payment_method_url );
			$this->update_payment_methods( $payment_methods );

			$this->potentially_update_selected_payment_method_from_payment_methods( $payment_methods );
			return true;
		}

		protected function potentially_update_selected_payment_method_from_payment_methods( $payment_methods ) {
			$payment_method_ids = array();

			foreach ( (array) $payment_methods as $payment_method ) {
				$payment_method_id = intval( $payment_method->payment_method_id );

				if ( 0 !== $payment_method_id ) {
					$payment_method_ids[] = $payment_method_id;
				}
			}

			// No payment methods at all? Clear anything we have stored.
			if ( 0 === count( $payment_method_ids ) ) {
				$this->service_settings_store->set_selected_payment_method_id( 0 );
				return;
			}

			// Has the stored method ID been removed, or is there only one available? Select the first available one.
			$selected_payment_method_id = $this->service_settings_store->get_selected_payment_method_id();
			if (
				( $selected_payment_method_id || 1 === count( $payment_method_ids ) ) &&
				! in_array( $selected_payment_method_id, $payment_method_ids )
			) {
				$this->service_settings_store->set_selected_payment_method_id( $payment_method_ids[0] );
			}
		}

		public function get_payment_methods() {
			return WC_Connect_Options::get_option( 'payment_methods', array() );
		}

		protected function update_payment_methods( $payment_methods ) {
			WC_Connect_Options::update_option( 'payment_methods', $payment_methods );
		}

		/**
		 * Validate that the response body is valid and contains the correct properties.
		 *
		 * @param object $response_body The response body object.
		 * @return true|WP_Error Whether the response body is valid.
		 */
		protected function validate_payment_methods_response( $response_body ) {
			if ( ! is_object( $response_body ) ) {
				return new WP_Error( 'payment_method_response_body_type', __( 'Expected but did not receive object for response body.', 'woocommerce-services' ) );
			}

			if ( ! property_exists( $response_body, 'payment_methods' ) ) {
				return new WP_Error( 'payment_method_response_body_missing_payment_methods', __( 'Expected but did not receive payment_methods in response body.', 'woocommerce-services' ) );
			}

			if ( ! property_exists( $response_body, 'add_payment_method_url' ) ) {
				return new WP_Error( 'payment_method_response_body_missing_add_payment_method_url', __( 'Expected but did not receive add_payment_method_url in response body.', 'woocommerce-services' ) );
			}

			return true;
		}

		protected function get_payment_methods_from_response_body( $response_body ) {
			$payment_methods = $response_body->payment_methods;
			if ( ! is_array( $payment_methods ) ) {
				return new WP_Error( 'payment_methods_type', 'Expected but did not receive array for payment_methods.' );
			}

			foreach ( $payment_methods as $payment_method ) {
				$required_keys = array( 'payment_method_id', 'name', 'card_type', 'card_digits', 'expiry' );
				foreach ( $required_keys as $required_key ) {
					if ( ! property_exists( $payment_method, $required_key ) ) {
						return new WP_Error( 'payment_methods_key_missing', 'Payment method is missing a required property' );
					}
				}
			}

			return $payment_methods;
		}

		/**
		 * Get the URL to add a payment method from the response body.
		 *
		 * @param object $response_body The response body object.
		 * @return string The URL to add a payment method.
		 */
		protected function get_add_payment_method_url_from_response_body( $response_body ) {
			return $response_body->add_payment_method_url;
		}

		/**
		 * Get the URL to add a payment method.
		 *
		 * @return string The URL to add a payment method.
		 */
		public function get_add_payment_method_url() {
			return WC_Connect_Options::get_option( 'add_payment_method_url', '' );
		}

		/**
		 * Update the URL to add a payment method.
		 *
		 * @param string $add_payment_method_url The URL to add a payment method.
		 * @return void
		 */
		protected function update_add_payment_method_url( $add_payment_method_url ) {
			WC_Connect_Options::update_option( 'add_payment_method_url', esc_url_raw( $add_payment_method_url ) );
		}
	}
}
