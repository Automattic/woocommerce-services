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
		 * Gets woocommerce store options that are useful for all connect services
		 *
		 * @return object|array
		 */
		public function get_store_options() {
			$currency_symbol = sanitize_text_field( html_entity_decode( get_woocommerce_currency_symbol() ) );
			$dimension_unit = sanitize_text_field( strtolower( get_option( 'woocommerce_dimension_unit' ) ) );
			$weight_unit = sanitize_text_field( strtolower( get_option( 'woocommerce_weight_unit' ) ) );

			return array(
				'currency_symbol' => $currency_symbol,
				'dimension_unit' => $this->translate_unit( $dimension_unit ),
				'weight_unit' => $this->translate_unit( $weight_unit ),
			);
		}

		/**
		 * Gets connect account settings (e.g. payment method)
		 *
		 * @return array
		 */
		public function get_account_settings() {
			$default = array(
				'selected_payment_method_id' => 0
			);
			return get_option( 'wc_connect_account_settings', $default );
		}

		/**
		 * Updates connect account settings (e.g. payment method)
		 *
		 * @param array $settings
		 *
		 * @return true
		 */
		public function update_account_settings( $settings ) {
			// simple validation for now
			if ( ! is_array( $settings ) ) {
				$this->logger->log( 'Array expected but not received', __FUNCTION__ );
				return false;
			}

			return update_option( 'wc_connect_account_settings', $settings );;
		}

		public function get_selected_payment_method_id() {
			$account_settings = $this->get_account_settings();
			return intval( $account_settings[ 'selected_payment_method_id' ] );
		}

		public function set_selected_payment_method_id( $new_payment_method_id ) {
			$new_payment_method_id = intval( $new_payment_method_id );
			$account_settings = $this->get_account_settings();
			$old_payment_method_id = intval( $account_settings[ 'selected_payment_method_id' ] );
			if ( $old_payment_method_id === $new_payment_method_id ) {
				return;
			}
			$account_settings[ 'selected_payment_method_id' ] = $new_payment_method_id;
			$this->update_account_settings( $account_settings );
		}

		public function get_origin_address() {
			$wc_address_fields = array();
			$wc_address_fields[ 'company' ] = get_bloginfo( 'name' );
			$wc_address_fields[ 'name' ] = wp_get_current_user()->display_name;
			$base_location = wc_get_base_location();
			$wc_address_fields[ 'country' ] = $base_location[ 'country' ];
			$wc_address_fields[ 'state' ] = $base_location[ 'state' ];
			$wc_address_fields[ 'address' ] = '';
			$wc_address_fields[ 'address_2' ] = '';
			$wc_address_fields[ 'city' ] = '';
			$wc_address_fields[ 'postcode' ] = '';

			$stored_address_fields = get_option( 'wc_connect_origin_address', array() );
			return array_merge( $wc_address_fields, $stored_address_fields );
		}

		public function get_preferred_paper_size() {
			return get_option( 'wc_connect_paper_size', '' );
		}

		public function set_preferred_paper_size( $size ) {
			return update_option( 'wc_connect_paper_size', $size );
		}

		public function update_label_order_meta_data( $order_id, $new_label_data ) {
			$raw_labels_data = get_post_meta( ( int ) $order_id, 'wc_connect_labels', true );
			$labels_data = json_decode( $raw_labels_data, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );
			foreach( $labels_data as $index => $label_data ) {
				if ( $label_data[ 'label_id' ] === $new_label_data->label_id ) {
					$labels_data[ $index ] = array_merge( $label_data, (array) $new_label_data );
				}
			}
			update_post_meta( $order_id, 'wc_connect_labels', json_encode( $labels_data ) );
		}

		public function update_origin_address( $address ) {
			return update_option( 'wc_connect_origin_address', $address );
		}

		protected function sort_services( $a, $b ) {

			if ( $a->zone_order === $b->zone_order ) {
				return ( $a->instance_id > $b->instance_id ) ? 1 : -1;
			}

			if ( is_null( $a->zone_order ) ) {
				return 1;
			}

			if ( is_null( $b->zone_order ) ) {
				return -1;
			}

			return ( $a->instance_id > $b->instance_id ) ? 1 : -1;

		}

		/**
		 * Returns the service type and id for each enabled Connect for WooCommerce service
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
				"LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zones " .
				"ON {$wpdb->prefix}woocommerce_shipping_zone_methods.zone_id = {$wpdb->prefix}woocommerce_shipping_zones.zone_id " .
				"WHERE method_id IN ({$escaped_list}) " .
				"ORDER BY zone_order, instance_id;"
			);

			if ( empty( $methods ) ) {
				return $enabled_services;
			}

			foreach ( (array) $methods as $method ) {
				$service_schema = $this->service_schemas_store->get_service_schema_by_id( $method->method_id );
				$service_settings = $this->get_service_settings( $method->method_id, $method->instance_id );
				if ( is_object( $service_settings ) && property_exists( $service_settings, 'title' ) ) {
					$title = $service_settings->title;
				} else if ( is_object( $service_schema ) && property_exists( $service_schema, 'method_title' ) ) {
					$title = $service_schema->method_title;
				} else {
					$title = _x( 'Unknown', 'A service with an unknown title and unknown method_title', 'connectforwoocommerce' );
				}
				$method->service_type = 'shipping';
				$method->title = $title;
				$method->zone_name = empty( $method->zone_name ) ? __( 'Rest of the World', 'connectforwoocommerce' ) : $method->zone_name;
				$enabled_services[] = $method;
			}

			usort( $enabled_services, array( $this, 'sort_services' ) );
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
							'message' => __( 'An invalid service instance was received.', 'connectforwoocommerce' )
						)
					);
				}
			} else {
				$service_schema = $this->service_schemas_store->get_service_schema_by_id( $id );
				if ( ! $service_schema ) {
					wp_send_json_error(
						array(
							'error' => 'bad_service_id',
							'message' => __( 'An invalid service ID was received.', 'connectforwoocommerce' )
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

		/**
		 * Based on the service id and optional instance, generates the options key that
		 * should be used to get/set the service's last failure timestamp
		 *
		 * @param string $service_id
		 * @param integer $service_instance
		 *
		 * @return string
		 */
		public function get_service_failure_timestamp_key( $service_id, $service_instance = false ) {
			if ( ! $service_instance ) {
				return 'woocommerce_' . $service_id . '_failure_timestamp';
			}

			return 'woocommerce_' . $service_id . '_' . $service_instance . '_failure_timestamp';
		}

		/**
		 * Returns a global list of packages
		 *
		 * @return array
		 */
		public function get_packages() {
			return get_option( 'wc_connect_packages', array() );
		}

		/**
		 * Updates the global list of packages
		 *
		 * @param array packages
		 */
		public function update_packages( $packages ) {
			update_option( 'wc_connect_packages', $packages );
		}

		/**
		 * Returns a global list of enabled predefined packages for all services
		 *
		 * @return array
		 */
		public function get_predefined_packages() {
			return get_option( 'wc_connect_predefined_packages', array() );
		}

		/**
		 * Returns a list of enabled predefined packages for the specified service
		 *
		 * @param $service_id
		 * @return array
		 */
		public function get_predefined_packages_for_service( $service_id ) {
			$packages = $this->get_predefined_packages();
			if ( ! isset( $packages[ $service_id ] ) ) {
				return array();
			}

			return $packages[ $service_id ];
		}

		/**
		 * Updates the global list of enabled predefined packages for all services
		 *
		 * @param array packages
		 */
		public function update_predefined_packages( $packages ) {
			update_option( 'wc_connect_predefined_packages', $packages );
		}

		public function get_package_lookup_for_service( $service_id ) {
			$lookup = array();

			$custom_packages =  $this->get_packages();
			foreach ( $custom_packages as $custom_package ) {
				$lookup[ $custom_package[ 'name' ] ] = $custom_package;
			}

			$predefined_packages_schema = $this->service_schemas_store->get_predefined_packages_schema_for_service( $service_id );
			if ( is_null( $predefined_packages_schema ) ) {
				return $lookup;
			}

			foreach ( $predefined_packages_schema as $group ) {
				foreach ( $group->definitions as $predefined ) {
					$lookup[ $predefined->id ] = ( array ) $predefined;
				}
			}

			return $lookup;
		}

		private function translate_unit( $value ) {
			switch ( $value ) {
				case 'kg':
					return __('kg', 'connectforwoocommerce');
				case 'g':
					return __('g', 'connectforwoocommerce');
				case 'lbs':
					return __('lbs', 'connectforwoocommerce');
				case 'oz':
					return __('oz', 'connectforwoocommerce');
				case 'm':
					return __('m', 'connectforwoocommerce');
				case 'cm':
					return __('cm', 'connectforwoocommerce');
				case 'mm':
					return __('mm', 'connectforwoocommerce');
				case 'in':
					return __('in', 'connectforwoocommerce');
				case 'yd':
					return __('yd', 'connectforwoocommerce');
				default:
					$this->logger->log( 'Unexpected measurement unit: ' . $value, __FUNCTION__ );
					return $value;
			}
		}
	}
}
