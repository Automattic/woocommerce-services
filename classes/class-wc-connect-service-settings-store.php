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
		 * Returns the service type and id for each enabled WooCommerce Connect service
		 *
		 * Shipping services also include instance_id and shipping zone id
		 *
		 * Note that at this time, only shipping services exist, but this method will
		 * return other services in the future
		 *
		 * @return array
		 */
		public function get_enabled_services() {

			$enabled_services = array();

			$shipping_services = $this->service_schemas_store->get_all_service_ids_of_type( 'shipping' );
			if ( empty( $shipping_services ) ) {
				return $enabled_services;
			}

			// Note: We use esc_sql here instead of prepare because we are using WHERE IN
			// https://codex.wordpress.org/Function_Reference/esc_sql

			$escaped_list = '';
			foreach ( $shipping_services as $shipping_service ) {
				if ( ! empty( $escaped_list ) ) {
					$escaped_list .= ',';
				}
				$escaped_list .= "'" . esc_sql( $shipping_service ) . "'";
			}

			global $wpdb;
			$methods = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods " .
				"INNER JOIN {$wpdb->prefix}woocommerce_shipping_zones " .
				"ON {$wpdb->prefix}woocommerce_shipping_zone_methods.zone_id = {$wpdb->prefix}woocommerce_shipping_zones.zone_id " .
				"WHERE method_id IN ({$escaped_list}) " .
				"ORDER BY zone_order, method_order " .
				";"
			);

			if ( empty( $methods ) ) {
				return $enabled_services;
			}

			foreach ( (array) $methods as $method ) {
				$method->service_type = 'shipping';
				$enabled_services[] = $method;
				// TODO - as a convenience to the caller, add method_title and title too
			}

			error_log( print_r( $enabled_services, true ) );

			return $enabled_services;

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
					$this->logger->log( 'Unexpected measurement unit: ' . $value, __FUNCTION__ );
					return $value;
			}
		}
	}
}
