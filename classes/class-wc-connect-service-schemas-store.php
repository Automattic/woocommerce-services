<?php

if ( ! class_exists( 'WC_Connect_Service_Schemas_Store' ) ) {

	/**
	 * Stores and retrieves the service schemas fetched from the Connect server.
	 */
	class WC_Connect_Service_Schemas_Store {

		/**
		 * The API client used to communicate with the Connect server.
		 *
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * The logger instance.
		 *
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * WC_Connect_Service_Schemas_Store constructor.
		 *
		 * @param WC_Connect_API_Client $api_client The API client used to communicate with the Connect server.
		 * @param WC_Connect_Logger     $logger     The logger instance.
		 */
		public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Logger $logger ) {

			$this->api_client = $api_client;
			$this->logger     = $logger;
		}

		/**
		 * Fetches the service schemas from the Connect server and stores them.
		 *
		 * @return bool True on success, false on failure.
		 */
		public function fetch_service_schemas_from_connect_server() {

			$response_body = $this->api_client->get_service_schemas();

			if ( is_wp_error( $response_body ) ) {
				$error_data = $response_body->get_error_data();
				if ( isset( $error_data['response_status_code'] ) ) {
					$this->update_last_fetch_result_code( $error_data['response_status_code'] );
				}
				$this->logger->log( $response_body, __FUNCTION__ );
				return false;
			}
			$this->update_last_fetch_result_code( '200' );

			$this->logger->log( 'Successfully loaded service schemas from server response.', __FUNCTION__ );
			$this->update_last_fetch_timestamp();
			$this->maybe_update_heartbeat();
			$this->maybe_mark_as_legacy_site( $response_body );

			$old_schemas = $this->get_service_schemas();
			if ( $old_schemas == $response_body ) {
				// Schemas weren't changed, but were fetched without problems.
				return true;
			}

			// If we made it this far, it is safe to store the object.
			return $this->update_service_schemas( $response_body );
		}

		/**
		 * Returns the stored service schemas.
		 *
		 * @return object|null The stored service schemas, or null if none are stored.
		 */
		public function get_service_schemas() {
			return WC_Connect_Options::get_option( 'services', null );
		}

		/**
		 * Stores the given service schemas.
		 *
		 * @param object $service_schemas The service schemas to store.
		 * @return bool True if the option was updated, false otherwise.
		 */
		protected function update_service_schemas( $service_schemas ) {
			return WC_Connect_Options::update_option( 'services', $service_schemas );
		}

		/**
		 * Returns the timestamp of the last successful fetch.
		 *
		 * @return int|null The last fetch timestamp, or null if never fetched.
		 */
		public function get_last_fetch_timestamp() {
			return WC_Connect_Options::get_option( 'services_last_update', null );
		}

		/**
		 * Stores the current time as the last fetch timestamp.
		 *
		 * @return void
		 */
		protected function update_last_fetch_timestamp() {
			WC_Connect_Options::update_option( 'services_last_update', time() );
		}

		/**
		 * Returns the result code of the last fetch.
		 *
		 * @return mixed The last fetch result code.
		 */
		public function get_last_fetch_result_code() {
			return WC_Connect_Options::get_option( 'services_last_result_code' );
		}

		/**
		 * Stores the result code of the last fetch.
		 *
		 * @param int $result_status_code The result status code to store.
		 * @return void
		 */
		protected function update_last_fetch_result_code( $result_status_code ) {
			WC_Connect_Options::update_option( 'services_last_result_code', $result_status_code );
		}

		/**
		 * Updates the heartbeat timestamp if more than a day has elapsed.
		 *
		 * @return void
		 */
		protected function maybe_update_heartbeat() {
			$last_heartbeat = WC_Connect_Options::get_option( 'last_heartbeat' );
			$now            = time();

			if ( ! $last_heartbeat ) {
				$should_update = true;
			} else {
				$last_heartbeat = absint( $last_heartbeat );
				if ( $last_heartbeat > $now ) {
					// Last heartbeat in the future? Wacky.
					$should_update = true;
				} else {
					$elapsed       = $now - $last_heartbeat;
					$should_update = $elapsed > DAY_IN_SECONDS;
				}
			}

			if ( $should_update ) {
				WC_Connect_Options::update_option( 'last_heartbeat', $now );
			}
		}

		/**
		 * Marks the site as legacy (tax only) when the schema indicates a first install.
		 *
		 * @param object $service_schemas The service schemas to inspect.
		 * @return void
		 */
		protected function maybe_mark_as_legacy_site( $service_schemas ) {
			if ( isset( $service_schemas->features->first_install ) && true === $service_schemas->features->first_install ) {
				WC_Connect_Options::update_option( 'only_tax', '1' );
			}
		}

		/**
		 * Returns all service ids of a specific type (e.g. shipping)
		 *
		 * @param string $type The type of services to return.
		 *
		 * @return array An array of that type's service ids, or an empty array if no such type is known.
		 */
		public function get_all_service_ids_of_type( $type ) {

			if ( empty( $type ) ) {
				return array();
			}

			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) || ! property_exists( $service_schemas, $type ) || ! is_array( $service_schemas->$type ) ) {
				return array();
			}

			$service_schema_ids = array();
			foreach ( $service_schemas->$type as $service_schema ) {
				$service_schema_ids[] = $service_schema->id;
			}

			return $service_schema_ids;
		}

		/**
		 * Returns all shipping method ids
		 *
		 * @return array|bool An array of supported shipping method ids or false if schema does not support method_id
		 */
		public function get_all_shipping_method_ids() {
			$shipping_method_ids = array();
			$service_schemas     = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) || ! property_exists( $service_schemas, 'shipping' ) || ! is_array( $service_schemas->shipping ) ) {
				return $shipping_method_ids;
			}

			foreach ( $service_schemas->shipping as $service_schema ) {
				if ( ! property_exists( $service_schema, 'method_id' ) ) {
					continue;
				}

				$shipping_method_ids[] = $service_schema->method_id;
			}

			return $shipping_method_ids;
		}

		/**
		 * Returns a particular service's schema given its id
		 *
		 * @param string $service_id The service id for which to return the schema.
		 *
		 * @return object|null The service schema or null if no such id was found.
		 */
		public function get_service_schema_by_id( $service_id ) {
			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) ) {
				return null;
			}

			foreach ( $service_schemas as $service_type => $service_type_service_schemas ) {
				$matches = wp_filter_object_list( $service_type_service_schemas, array( 'id' => $service_id ) );
				if ( $matches ) {
					return array_shift( $matches );
				}
			}

			return null;
		}

		/**
		 * Returns a particular service's schema given its method_id
		 *
		 * @param string $method_id The method id for which to return the schema.
		 *
		 * @return object|null The service schema or null if no such id was found.
		 */
		public function get_service_schema_by_method_id( $method_id ) {
			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) ) {
				return null;
			}

			foreach ( $service_schemas as $service_type => $service_type_service_schemas ) {
				$matches = wp_filter_object_list( $service_type_service_schemas, array( 'method_id' => $method_id ) );
				if ( $matches ) {
					return array_shift( $matches );
				}
			}

			return null;
		}

		/**
		 * Returns a service's schema given its shipping zone instance
		 *
		 * @param string $instance_id The shipping zone instance id for which to return the schema.
		 *
		 * @return object|null The service schema or null if no such instance was found.
		 */
		public function get_service_schema_by_instance_id( $instance_id ) {
			global $wpdb;
			$method_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d;",
					$instance_id
				)
			);

			return $this->get_service_schema_by_method_id( $method_id );
		}

		/**
		 * Returns a service's schema given an id or shipping zone instance.
		 *
		 * @param string $id_or_instance_id String ID or numeric instance ID.
		 * @return object|null Service schema on success, null on failure
		 */
		public function get_service_schema_by_id_or_instance_id( $id_or_instance_id ) {

			if ( is_numeric( $id_or_instance_id ) ) {
				return $this->get_service_schema_by_instance_id( $id_or_instance_id );
			}

			if ( ! empty( $id_or_instance_id ) ) {
				return $this->get_service_schema_by_method_id( $id_or_instance_id );
			}

			return null;
		}

		/**
		 * Returns packages schema
		 *
		 * @return object|null Packages schema on success, null on failure
		 */
		public function get_packages_schema() {
			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) || ! property_exists( $service_schemas, 'boxes' ) ) {
				return null;
			}

			return $service_schemas->boxes;
		}

		/**
		 * Returns the predefined packages schema keyed by service id.
		 *
		 * @return object|array|null An array of predefined packages, or null if no schema is stored.
		 */
		public function get_predefined_packages_schema() {
			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) ) {
				return null;
			}

			$predefined_packages = array();
			foreach ( $service_schemas->shipping as $service_schema ) {
				if ( ! isset( $service_schema->packages ) ) {
					continue;
				}

				$predefined_packages[ $service_schema->id ] = $service_schema->packages;
			}

			return $predefined_packages;
		}

		/**
		 * Returns the WooCommerce Shipping and WooCommerce Tax migration enabled status
		 *
		 * @return bool
		 */
		public function is_wcship_wctax_migration_enabled() {
			$service_schemas = $this->get_service_schemas();

			return is_object( $service_schemas ) && property_exists( $service_schemas, 'features' ) && property_exists( $service_schemas->features, 'wcshippingtax_upgrade_banner' ) && ! empty( $service_schemas->features->wcshippingtax_upgrade_banner );
		}

		/**
		 * Returns the WooCommerce Shipping and WooCommerce Tax upgrade banners
		 *
		 * @return object|null The banners schema or null if no such id was found
		 */
		public function get_wcship_wctax_upgrade_banner() {
			$service_schemas = $this->get_service_schemas();

			if ( $this->is_wcship_wctax_migration_enabled() ) {
				return $service_schemas->features->wcshippingtax_upgrade_banner;
			}

			return null;
		}
	}
}//end if
