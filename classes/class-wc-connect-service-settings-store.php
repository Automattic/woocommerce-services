<?php

if ( ! class_exists( 'WC_Connect_Service_Settings_Store' ) ) {

	class WC_Connect_Service_Settings_Store {

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		public function __construct( WC_Connect_Service_Schemas_Store $service_schemas_store, WC_Connect_API_Client $api_client, WC_Connect_Logger $logger ) {
			$this->service_schemas_store = $service_schemas_store;
			$this->api_client = $api_client;
			$this->logger     = $logger;
		}

		/**
		 * Gets woocommerce settings useful for all connect services
		 *
		 * @return object|array
		 */
		public function get_shared_settings() {
			$currency_symbol = sanitize_text_field( html_entity_decode( get_woocommerce_currency_symbol() ), array() );
			$dimension_unit = sanitize_text_field( strtolower( get_option( 'woocommerce_dimension_unit' ) ), array() );
			$weight_unit = sanitize_text_field( strtolower( get_option( 'woocommerce_weight_unit' ) ), array() );

			return array(
				'currency_symbol' => $currency_symbol,
				'dimension_unit' => $this->translate_unit( $dimension_unit ),
				'weight_unit' => $this->translate_unit( $weight_unit ),
			);
		}

		/**
		 * Given a service's id and optional instance, returns the settings for that
		 * service or an empty array
		 *
		 * @param string $service_id
		 * @param integer $service_instance
		 *
		 * @return object|array
		 */
		public function get_service_settings( $service_id, $service_instance = false ) {
			return get_option( $this->get_service_settings_key( $service_id, $service_instance ), array() );
		}

		/**
		 * Returns a nonce action based on the service id and optional instance,
		 * suitable for use in generating or validating nonces
		 *
		 * @param string $service_id
		 * @param integer $service_instance
		 *
		 * @return string
		 */
		protected function get_wc_api_callback_nonce_action( $service_id, $service_instance = false ) {
			if ( ! $service_instance ) {
				return 'wc_connect_' . $service_id;
			}
			return 'wc_connect_' . $service_id . '_' . $service_instance;
		}

		/**
		 * Returns a nonce based on the service id and optional instance
		 *
		 * @param string $service_id
		 * @param integer $service_instance
		 *
		 * @return string
		 */
		public function get_wc_api_callback_nonce( $service_id, $service_instance = false ) {
			return wp_create_nonce( $this->get_wc_api_callback_nonce_action( $service_id, $service_instance ) );
		}

		/**
		 * Returns a WC_API compliant URL to our endpoint based on the class name that WC
		 * should load to ensure our endpoint is hooked
		 *
		 * @return string
		 */
		public function get_wc_api_callback_url() {
			return get_home_url( null, '/wc-api/wc_connect_loader', is_ssl() );
		}

		/**
		 * Given id and possibly instance, validates the settings and, if they validate, saves them to options
		 *
		 * @return bool|WP_Error
		 */
		public function validate_and_possibly_update_settings( $settings, $id, $instance = false ) {

			// Validate instance or at least id if no instance is given
			if ( ! empty( $instance ) ) {
				$service_schema = $this->service_schemas_store->get_service_schema_by_instance_id( $instance );
				if ( ! $service_schema ) {
					wp_send_json_error(
						array(
							'error' => 'bad_instance_id',
							'message' => __( 'An invalid service instance was received.', 'woocommerce' )
						)
					);
				}
			} else {
				$service_schema = $this->service_schemas_store->get_service_schema_by_id( $id );
				if ( ! $service_schema ) {
					wp_send_json_error(
						array(
							'error' => 'bad_service_id',
							'message' => __( 'An invalid service ID was received.', 'woocommerce' )
						)
					);
				}
			}

			// Validate settings with WCC server
			$response_body = $this->api_client->validate_service_settings( $id, $settings );

			if ( is_wp_error( $response_body ) ) {
				// TODO - handle multiple error messages when the validation endpoint can return them
				wp_send_json_error(
					array(
						'error'   => 'validation_failure',
					 	'message' => $response_body->get_error_message(),
						'data'    => $response_body->get_error_data(),
					)
				);
			}

			// On success, save the settings to the database and exit
			update_option( $this->get_service_settings_key( $id, $instance ), $settings );
			do_action( 'wc_connect_saved_service_settings', $id, $instance, $settings );

			return true;
		}

		/**
		 * Based on the service id and optional instance, generates the options key that
		 * should be used to get/set the service's settings
		 *
		 * @param string $service_id
		 * @param integer $service_instance
		 *
		 * @return string
		 */
		protected function get_service_settings_key( $service_id, $service_instance = false ) {
			if ( ! $service_instance ) {
				return 'woocommerce_' . $service_id . '_form_settings';
			}

			return 'woocommerce_' . $service_id . '_' . $service_instance . '_form_settings';
		}

		private function translate_unit( $value ) {
			switch ( $value ) {
				case 'kg':
					return __('kg', 'woocommerce');
				case 'g':
					return __('g', 'woocommerce');
				case 'lbs':
					return __('lbs', 'woocommerce');
				case 'oz':
					return __('oz', 'woocommerce');
				case 'm':
					return __('m', 'woocommerce');
				case 'cm':
					return __('cm', 'woocommerce');
				case 'mm':
					return __('mm', 'woocommerce');
				case 'in':
					return __('in', 'woocommerce');
				case 'yd':
					return __('yd', 'woocommerce');
				default:
					error_log( 'Unexpected measurement unit: ' . $value );
					return $value;
			}
		}
	}
}
