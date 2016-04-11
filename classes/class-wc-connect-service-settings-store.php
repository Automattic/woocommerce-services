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
			return get_home_url( null, '/wc-api/wc_connect_loader' );
		}

		/**
		 * Implements a WC_API compliant endpoint for validating and saving settings.
		 * Emits JSON.
		 */
		public function handle_wc_api() {
			$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
			$id = isset( $_POST['id'] ) ? sanitize_text_field( $_POST['id'] ) : '';
			$instance = isset( $_POST['instance'] ) ? absint( sanitize_text_field( $_POST['instance'] ) ) : '';

			if ( empty( $nonce ) ) {
				wp_send_json_error(
					array(
						'error' => 'nonce_missing',
						'message' => __( 'The request is invalid. No nonce was given.', 'woocommerce' )
					)
				);
			}

			if ( empty( $id ) ) {
				wp_send_json_error(
					array(
						'error' => 'service_id_missing',
						'message' => __( 'The request is invalid. No service ID was given.', 'woocommerce' )
					)
				);
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error(
					array(
						'error' => 'missing_capabilities',
						'message' => __( 'You do not have sufficient permissions for this endpoint.', 'woocommerce' )
					)
				);
			}

			$action = $this->get_wc_api_callback_nonce_action( $id, $instance );
			if ( ! wp_verify_nonce( $nonce, $action ) ) {
				wp_send_json_error(
					array(
						'error' => 'bad_nonce',
						'message' => __( 'An invalid nonce was received. Please refresh the page and try again.', 'woocommerce' )
					)
				);
			}

			// Validate instance or at least id if no instance is given
			if ( ! empty( $instance ) ) {
				$service_schema = $this->service_schemas_store->get_service_schema_by_instance_id( $instance );
				if ( ! $service_schema ) {
					wp_send_json_error(
						array(
							'error' => 'bad_instance_id',
							'message' => __( 'An invalid service instance was received. Please refresh the page and try again.', 'woocommerce' )
						)
					);
				}
			} else {
				$service_schema = $this->service_schemas_store->get_service_schema_by_id( $id );
				if ( ! $service_schema ) {
					wp_send_json_error(
						array(
							'error' => 'bad_service_id',
							'message' => __( 'An invalid service ID was received. Please refresh the page and try again.', 'woocommerce' )
						)
					);
				}
			}

			$settings = array();
			$settings_schema = $service_schema->service_settings;

			// Whitelist settings to be sent to the validation endpoint using the schema
			// in the service's service_settings
			if ( isset( $settings_schema->properties ) ) {
				foreach ( (array) $settings_schema->properties as $field_name => $properties ) {
					// Special handling is needed to turn checkboxes back into booleans
					// since our form returns 'on' for checkboxes if they are checked and omits
					// the key if they are not checked
					if ( 'boolean' === $properties->type ) {
						$settings[ $field_name ] = isset( $_POST[ $field_name ] );
					} elseif ( isset( $_POST[ $field_name ] ) ) {
						$settings[ $field_name ] = sanitize_text_field( $_POST[ $field_name ] );
					}
				}
			}

			// Validate settings with WCC server
			$response_body = $this->api_client->validate_service_settings( $id, $settings );

			if ( is_wp_error( $response_body ) ) {
				// TODO - handle multiple error messages when the validation endpoint can return them
				wp_send_json_error(
					array(
						'error' => 'validation_failure',
					 	'message' => $response_body->get_error_message()
					)
				);
			}

			// On success, save the settings to the database and exit
			update_option( $this->get_service_settings_key( $id, $instance ), $settings );
			wp_send_json_success();
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
	}
}
