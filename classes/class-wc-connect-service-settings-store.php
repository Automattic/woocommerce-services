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
			$this->api_client            = $api_client;
			$this->logger                = $logger;
		}

		/**
		 * Gets woocommerce store options that are useful for all connect services
		 *
		 * @return object|array
		 */
		public function get_store_options() {
			$currency_symbol = sanitize_text_field( html_entity_decode( get_woocommerce_currency_symbol() ) );
			$dimension_unit  = sanitize_text_field( strtolower( get_option( 'woocommerce_dimension_unit' ) ) );
			$weight_unit     = sanitize_text_field( strtolower( get_option( 'woocommerce_weight_unit' ) ) );
			$base_location   = wc_get_base_location();

			return array(
				'currency_symbol' => $currency_symbol,
				'dimension_unit'  => $this->translate_unit( $dimension_unit ),
				'weight_unit'     => $this->translate_unit( $weight_unit ),
				'origin_country'  => $base_location['country'],
			);
		}

		/**
		 * Gets connect account settings (e.g. payment method)
		 *
		 * @return array
		 */
		public function get_account_settings() {
			$default = array(
				'selected_payment_method_id' => 0,
				'enabled'                    => true,
			);

			$result               = WC_Connect_Options::get_option( 'account_settings', $default );
			$result['paper_size'] = $this->get_preferred_paper_size();
			$result               = array_merge( $default, $result );

			if ( ! isset( $result['email_receipts'] ) ) {
				$result['email_receipts'] = true;
			}

			if ( ! isset( $result['use_last_service'] ) ) {
				$result['use_last_service'] = false;
			}

			if ( ! isset( $result['use_last_package'] ) ) {
				$result['use_last_package'] = true;
			}

			return $result;
		}

		/**
		 * Updates connect account settings (e.g. payment method)
		 *
		 * @param array $settings
		 *
		 * @return true
		 */
		public function update_account_settings( $settings ) {
			// simple validation for now.
			if ( ! is_array( $settings ) ) {
				$this->logger->log( 'Array expected but not received', __FUNCTION__ );
				return false;
			}

			$paper_size = $settings['paper_size'];
			$this->set_preferred_paper_size( $paper_size );
			unset( $settings['paper_size'] );

			return WC_Connect_Options::update_option( 'account_settings', $settings );
		}

		public function get_selected_payment_method_id() {
			$account_settings = $this->get_account_settings();
			return intval( $account_settings['selected_payment_method_id'] );
		}

		public function set_selected_payment_method_id( $new_payment_method_id ) {
			$new_payment_method_id = intval( $new_payment_method_id );
			$account_settings      = $this->get_account_settings();
			$old_payment_method_id = intval( $account_settings['selected_payment_method_id'] );
			if ( $old_payment_method_id === $new_payment_method_id ) {
				return;
			}
			$account_settings['selected_payment_method_id'] = $new_payment_method_id;
			$this->update_account_settings( $account_settings );
		}

		public function can_user_manage_payment_methods() {
			return WC_Connect_Jetpack::is_offline_mode() || WC_Connect_Jetpack::is_current_user_connection_owner();
		}

		public function get_origin_address() {
			$wc_address_fields            = array();
			$wc_address_fields['company'] = html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ); // HTML entities may be saved in the option.
			$wc_address_fields['name']    = wp_get_current_user()->display_name;
			$wc_address_fields['phone']   = '';

			$wc_countries = WC()->countries;
			// WC 3.2 introduces ability to configure a full address in the settings
			// Use it for address defaults if available
			if ( method_exists( $wc_countries, 'get_base_address' ) ) {
				$wc_address_fields['country']   = $wc_countries->get_base_country();
				$wc_address_fields['state']     = $wc_countries->get_base_state();
				$wc_address_fields['address']   = $wc_countries->get_base_address();
				$wc_address_fields['address_2'] = $wc_countries->get_base_address_2();
				$wc_address_fields['city']      = $wc_countries->get_base_city();
				$wc_address_fields['postcode']  = $wc_countries->get_base_postcode();
			} else {
				$base_location                  = wc_get_base_location();
				$wc_address_fields['country']   = $base_location['country'];
				$wc_address_fields['state']     = $base_location['state'];
				$wc_address_fields['address']   = '';
				$wc_address_fields['address_2'] = '';
				$wc_address_fields['city']      = '';
				$wc_address_fields['postcode']  = '';
			}

			$stored_address_fields    = WC_Connect_Options::get_option( 'origin_address', array() );
			$merged_fields            = is_array( $stored_address_fields ) ? array_merge( $wc_address_fields, $stored_address_fields ) : $wc_address_fields;
			$merged_fields['company'] = html_entity_decode( $merged_fields['company'], ENT_QUOTES ); // Decode again for any existing stores that had some html entities saved in the option.
			return $merged_fields;
		}

		public function get_preferred_paper_size() {
			$paper_size = WC_Connect_Options::get_option( 'paper_size', '' );
			if ( $paper_size ) {
				return $paper_size;
			}
			// According to https://en.wikipedia.org/wiki/Letter_(paper_size) US, Mexico, Canada and Dominican Republic
			// use "Letter" size, and pretty much all the rest of the world use A4, so those are sensible defaults.
			$base_location = wc_get_base_location();
			if ( in_array( $base_location['country'], array( 'US', 'CA', 'MX', 'DO' ), true ) ) {
				return 'letter';
			}
			return 'a4';
		}

		public function set_preferred_paper_size( $size ) {
			return WC_Connect_Options::update_option( 'paper_size', $size );
		}

		/**
		 * Attempts to recover faulty json string fields that might contain strings with unescaped quotes
		 *
		 * @param string $field_name
		 * @param string $json
		 *
		 * @return string
		 */
		public function try_recover_invalid_json_string( $field_name, $json ) {
			$regex = '/"' . $field_name . '":"(.+?)","/';
			preg_match_all( $regex, $json, $match_groups );
			if ( 2 === count( $match_groups ) ) {
				foreach ( $match_groups[0] as $idx => $match ) {
					$value         = $match_groups[1][ $idx ];
					$escaped_value = preg_replace( '/(?<!\\\)"/', '\\"', $value );
					$json          = str_replace( $match, '"' . $field_name . '":"' . $escaped_value . '","', $json );
				}
			}
			return $json;
		}

		/**
		 * Attempts to recover faulty json string array fields that might contain strings with unescaped quotes
		 *
		 * @param string $field_name
		 * @param string $json
		 *
		 * @return string
		 */
		public function try_recover_invalid_json_array( $field_name, $json ) {
			$regex = '/"' . $field_name . '":\["(.+?)"\]/';
			preg_match_all( $regex, $json, $match_groups );
			if ( 2 === count( $match_groups ) ) {
				foreach ( $match_groups[0] as $idx => $match ) {
					$array         = $match_groups[1][ $idx ];
					$escaped_array = preg_replace( '/(?<![,\\\])"(?!,)/', '\\"', $array );
					$json          = str_replace( '["' . $array . '"]', '["' . $escaped_array . '"]', $json );
				}
			}
			return $json;
		}

		public function try_deserialize_labels_json( $label_data ) {
			// attempt to decode the JSON (legacy way of storing the labels data).
			$decoded_labels = json_decode( $label_data, true );
			if ( $decoded_labels ) {
				return $decoded_labels;
			}

			$label_data     = $this->try_recover_invalid_json_string( 'package_name', $label_data );
			$decoded_labels = json_decode( $label_data, true );
			if ( $decoded_labels ) {
				return $decoded_labels;
			}

			$label_data     = $this->try_recover_invalid_json_array( 'product_names', $label_data );
			$decoded_labels = json_decode( $label_data, true );
			if ( $decoded_labels ) {
				return $decoded_labels;
			}

			return array();
		}

		/**
		 * Returns labels for the specific order ID
		 *
		 * @param $order_id
		 *
		 * @return array
		 */
		public function get_label_order_meta_data( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				return array();
			}

			$label_data = $order->get_meta( 'wc_connect_labels', true );
			// return an empty array if the data doesn't exist.
			if ( ! $label_data ) {
				return array();
			}

			// labels stored as an array, return.
			if ( is_array( $label_data ) ) {
				return $label_data;
			}

			return $this->try_deserialize_labels_json( $label_data );
		}

		/**
		 * Updates the existing label data
		 *
		 * @param $order_id
		 * @param $new_label_data
		 *
		 * @return array updated label info
		 */
		public function update_label_order_meta_data( $order_id, $new_label_data ) {
			$result      = $new_label_data;
			$order       = wc_get_order( $order_id );
			$labels_data = $this->get_label_order_meta_data( $order_id );
			foreach ( $labels_data as $index => $label_data ) {
				if ( $label_data['label_id'] === $new_label_data->label_id ) {
					$result                = array_merge( $label_data, (array) $new_label_data );
					$labels_data[ $index ] = $result;

					if ( ! isset( $label_data['tracking'] )
						&& isset( $result['tracking'] ) ) {
							WC_Connect_Extension_Compatibility::on_new_tracking_number( $order_id, $result['carrier_id'], $result['tracking'] );
					}
				}
			}
			$order->update_meta_data( 'wc_connect_labels', $labels_data );
			$order->save();
			return $result;
		}

		/**
		 * Adds new labels to the order
		 *
		 * @param $order_id
		 * @param array    $new_labels - labels to be added
		 */
		public function add_labels_to_order( $order_id, $new_labels ) {
			$labels_data = $this->get_label_order_meta_data( $order_id );
			$labels_data = array_merge( $new_labels, $labels_data );
			$order       = wc_get_order( $order_id );
			$order->update_meta_data( 'wc_connect_labels', $labels_data );
			$order->save();
		}

		public function update_origin_address( $address ) {
			return WC_Connect_Options::update_option( 'origin_address', $address );
		}

		public function update_destination_address( $order_id, $api_address ) {
			$order      = wc_get_order( $order_id );
			$wc_address = $order->get_address( 'shipping' );

			$new_address = array_merge( array(), (array) $wc_address, (array) $api_address );
			// rename address to address_1.
			$new_address['address_1'] = $new_address['address'];
			// remove api-specific fields.
			unset( $new_address['address'], $new_address['name'] );

			foreach ( $new_address as $key => $value ) {
				if ( method_exists( $order, 'set_shipping_' . $key ) ) {
					call_user_func( array( $order, 'set_shipping_' . $key ), $value );
				}
			}

			$order->update_meta_data( '_wc_connect_destination_normalized', true );
			$order->save();
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
		 * Returns the service type and id for each enabled WooCommerce Shipping & Tax service
		 *
		 * Shipping services also include instance_id and shipping zone id
		 *
		 * Note that at this time, only shipping services exist, but this method will
		 * return other services in the future
		 *
		 * @return array
		 */
		public function get_enabled_services() {
			$shipping_services = $this->service_schemas_store->get_all_shipping_method_ids();
			if ( empty( $shipping_services ) ) {
				return array();
			}
			return $this->get_enabled_services_by_ids( $shipping_services );
		}

		public function get_enabled_services_by_ids( $service_ids ) {
			if ( empty( $service_ids ) ) {
				return array();
			}

			$enabled_services = array();

			// Note: We use esc_sql here instead of prepare because we are using WHERE IN
			// https://codex.wordpress.org/Function_Reference/esc_sql.

			$escaped_list = '';
			foreach ( $service_ids as $shipping_service ) {
				if ( ! empty( $escaped_list ) ) {
					$escaped_list .= ',';
				}
				$escaped_list .= "'" . esc_sql( $shipping_service ) . "'";
			}

			global $wpdb;
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared --- Need to use interpolated for the `IN()` condition
			$methods = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods " .
				"LEFT JOIN {$wpdb->prefix}woocommerce_shipping_zones " .
				"ON {$wpdb->prefix}woocommerce_shipping_zone_methods.zone_id = {$wpdb->prefix}woocommerce_shipping_zones.zone_id " .
				"WHERE method_id IN ({$escaped_list}) " .
				'ORDER BY zone_order, instance_id;'
			);
			// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( empty( $methods ) ) {
				return $enabled_services;
			}

			foreach ( (array) $methods as $method ) {
				$service_schema   = $this->service_schemas_store->get_service_schema_by_method_id( $method->method_id );
				$service_settings = $this->get_service_settings( $method->method_id, $method->instance_id );
				if ( is_object( $service_settings ) && property_exists( $service_settings, 'title' ) ) {
					$title = $service_settings->title;
				} elseif ( is_object( $service_schema ) && property_exists( $service_schema, 'method_title' ) ) {
					$title = $service_schema->method_title;
				} else {
					$title = _x( 'Unknown', 'A service with an unknown title and unknown method_title', 'woocommerce-services' );
				}
				$method->service_type = 'shipping';
				$method->title        = $title;
				$method->zone_name    = empty( $method->zone_name ) ? __( 'Rest of the World', 'woocommerce-services' ) : $method->zone_name;
				$enabled_services[]   = $method;
			}

			usort( $enabled_services, array( $this, 'sort_services' ) );
			return $enabled_services;
		}

		/**
		 * Checks if the shipping method ids have been migrated to the "wc_services_*" format and migrates them
		 */
		public function migrate_legacy_services() {
			if ( WC_Connect_Options::get_option( 'shipping_methods_migrated', false ) ) { // check if the method have already been migrated.
				return;
			}

			if ( ! $this->service_schemas_store->fetch_service_schemas_from_connect_server() ) { // ensure the latest schemas are fetched.
				// No schemes exist this is a site that has nothing to migrate.
				WC_Connect_Options::update_option( 'shipping_methods_migrated', true );
				return;
			}

			global $wpdb;

			// old services used the id field instead of method_id.
			$shipping_service_ids = $this->service_schemas_store->get_all_service_ids_of_type( 'shipping' );
			$legacy_services      = $this->get_enabled_services_by_ids( $shipping_service_ids );

			foreach ( $legacy_services as $legacy_service ) {
				$service_id       = $legacy_service->method_id;
				$instance_id      = $legacy_service->instance_id;
				$service_schema   = $this->service_schemas_store->get_service_schema_by_id( $service_id );
				$service_settings = $this->get_service_settings( $service_id, $instance_id );
				if ( ( is_array( $service_settings ) && ! $service_settings ) // check for an empty array.
					|| ( ! is_array( $service_settings ) && ! is_object( $service_settings ) ) ) { // settings are neither an array nor an object.
					continue;
				}

				$new_method_id = $service_schema->method_id;

				$wpdb->update(
					"{$wpdb->prefix}woocommerce_shipping_zone_methods",
					array( 'method_id' => $new_method_id ),
					array(
						'instance_id' => $instance_id,
						'method_id'   => $service_id,
					),
					array( '%s' ),
					array( '%d', '%s' )
				);

				// update the migrated service settings.
				WC_Connect_Options::update_shipping_method_option( 'form_settings', $service_settings, $new_method_id, $instance_id );
				// delete the old service settings.
				WC_Connect_Options::delete_shipping_method_options( $service_id, $instance_id );
			}

			WC_Connect_Options::update_option( 'shipping_methods_migrated', true );
		}

		/**
		 * Given a service's id and optional instance, returns the settings for that
		 * service or an empty array
		 *
		 * @param string  $service_id
		 * @param integer $service_instance
		 *
		 * @return object|array
		 */
		public function get_service_settings( $service_id, $service_instance = false ) {
			return WC_Connect_Options::get_shipping_method_option( 'form_settings', array(), $service_id, $service_instance );
		}

		/**
		 * Given id and possibly instance, validates the settings and, if they validate, saves them to options
		 *
		 * @return bool|WP_Error
		 */
		public function validate_and_possibly_update_settings( $settings, $id, $instance = false ) {

			// Validate instance or at least id if no instance is given.
			if ( ! empty( $instance ) ) {
				$service_schema = $this->service_schemas_store->get_service_schema_by_instance_id( $instance );
				if ( ! $service_schema ) {
					return new WP_Error( 'bad_instance_id', __( 'An invalid service instance was received.', 'woocommerce-services' ) );
				}
			} else {
				$service_schema = $this->service_schemas_store->get_service_schema_by_method_id( $id );
				if ( ! $service_schema ) {
					return new WP_Error( 'bad_service_id', __( 'An invalid service ID was received.', 'woocommerce-services' ) );
				}
			}

			// Validate settings with WCC server.
			$response_body = $this->api_client->validate_service_settings( $service_schema->id, $settings );

			if ( is_wp_error( $response_body ) ) {
				// TODO - handle multiple error messages when the validation endpoint can return them
				return $response_body;
			}

			// On success, save the settings to the database and exit.
			WC_Connect_Options::update_shipping_method_option( 'form_settings', $settings, $id, $instance );
			// Invalidate shipping rates session cache.
			WC_Cache_Helper::get_transient_version( 'shipping', /* $refresh = */ true );
			do_action( 'wc_connect_saved_service_settings', $id, $instance, $settings );

			return true;
		}

		/**
		 * Returns a global list of packages
		 *
		 * @return array
		 */
		public function get_packages() {
			return WC_Connect_Options::get_option( 'packages', array() );
		}

		/**
		 * Extends the global list of packages with a list of new packages
		 *
		 * @param array new_packages - packages to extend
		 */
		public function create_packages( $new_packages ) {
			if ( is_null( $new_packages ) ) {
				return;
			}
			$packages = $this->get_packages();
			$packages = array_merge( $packages, $new_packages );
			WC_Connect_Options::update_option( 'packages', $packages );
		}

		/**
		 * Updates the global list of packages
		 *
		 * @param array packages
		 */
		public function update_packages( $packages ) {
			WC_Connect_Options::update_option( 'packages', $packages );
		}

		/**
		 * Returns a global list of enabled predefined packages for all services
		 *
		 * @return array
		 */
		public function get_predefined_packages() {
			return WC_Connect_Options::get_option( 'predefined_packages', array() );
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
		 * Extends the global list of enabled predefined packages with a list of new packages
		 *
		 * @param array new_packages - packages to extend
		 */
		public function create_predefined_packages( $new_packages ) {
			if ( is_null( $new_packages ) ) {
				return;
			}
			$packages = $this->get_predefined_packages();
			$packages = array_merge_recursive( $packages, $new_packages );
			WC_Connect_Options::update_option( 'predefined_packages', $packages );
		}

		/**
		 * Updates the global list of enabled predefined packages for all services
		 *
		 * @param array packages
		 */
		public function update_predefined_packages( $packages ) {
			WC_Connect_Options::update_option( 'predefined_packages', $packages );
		}

		public function get_package_lookup() {
			$lookup = array();

			$custom_packages = $this->get_packages();
			foreach ( $custom_packages as $custom_package ) {
				$lookup[ $custom_package['name'] ] = $custom_package;
			}

			$predefined_packages_schema = $this->service_schemas_store->get_predefined_packages_schema();
			if ( is_null( $predefined_packages_schema ) ) {
				return $lookup;
			}

			foreach ( $predefined_packages_schema as $service_id => $groups ) {
				foreach ( $groups as $group ) {
					foreach ( $group->definitions as $predefined ) {
						$lookup[ $predefined->id ] = (array) $predefined;
					}
				}
			}

			return $lookup;
		}

		public function is_eligible_for_migration() {
			$migration_state = intval( get_option( 'wcshipping_migration_state', 0 ) );

			// If the migration state is greater than "COMPLETED", then we can assume that the next part of the migration
			// state is being handled by WooCommerce Shipping.
			if ( $migration_state > WC_Connect_WCST_To_WCShipping_Migration_State_Enum::COMPLETED ) {
				return false;
			}

			// Hide the migration notification if the site has any active shipping methods defined by WCS&T.
			if ( ! empty( $this->get_enabled_services() ) ) {
				return false;
			}

			$migration_dismissed = false;
			if ( isset( $_COOKIE[ WC_Connect_Loader::MIGRATION_DISMISSAL_COOKIE_KEY ] ) && (int) $_COOKIE[ WC_Connect_Loader::MIGRATION_DISMISSAL_COOKIE_KEY ] === 1 ) {
				$migration_dismissed = true;
			}

			$migration_pending = ! $migration_state || WC_Connect_WCST_To_WCShipping_Migration_State_Enum::COMPLETED !== $migration_state;
			$migration_enabled = $this->service_schemas_store->is_wcship_wctax_migration_enabled();

			return $migration_pending && $migration_enabled && ! $migration_dismissed;
		}

		private function translate_unit( $value ) {
			switch ( $value ) {
				case 'kg':
					return __( 'kg', 'woocommerce-services' );
				case 'g':
					return __( 'g', 'woocommerce-services' );
				case 'lbs':
					return __( 'lbs', 'woocommerce-services' );
				case 'oz':
					return __( 'oz', 'woocommerce-services' );
				case 'm':
					return __( 'm', 'woocommerce-services' );
				case 'cm':
					return __( 'cm', 'woocommerce-services' );
				case 'mm':
					return __( 'mm', 'woocommerce-services' );
				case 'in':
					return __( 'in', 'woocommerce-services' );
				case 'yd':
					return __( 'yd', 'woocommerce-services' );
				default:
					$this->logger->log( 'Unexpected measurement unit: ' . $value, __FUNCTION__ );
					return $value;
			}
		}
	}
}
