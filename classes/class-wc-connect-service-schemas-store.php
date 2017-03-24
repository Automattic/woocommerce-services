<?php

if ( ! class_exists( 'WC_Connect_Service_Schemas_Store' ) ) {

	class WC_Connect_Service_Schemas_Store {

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

		public function fetch_service_schemas_from_connect_server() {

			$response_body = $this->api_client->get_service_schemas();

			if ( is_wp_error( $response_body ) ) {
				$this->logger->debug( $response_body, __FUNCTION__ );
				return false;
			}

			$this->logger->debug( 'Successfully loaded service schemas from server response.', __FUNCTION__ );
			$this->update_last_fetch_timestamp();
			$this->maybe_update_heartbeat();

			$old_schemas = $this->get_service_schemas();
			if ( $old_schemas == $response_body ) {
				//schemas weren't changed, but were fetched without problems
				return true;
			}

			// If we made it this far, it is safe to store the object
			return $this->update_service_schemas( $response_body );
		}

		public function get_service_schemas() {
			return WC_Connect_Options::get_option( 'services', null );
		}

		protected function update_service_schemas( $service_schemas ) {
			return WC_Connect_Options::update_option( 'services', $service_schemas );
		}

		public function get_last_fetch_timestamp() {
			return WC_Connect_Options::get_option( 'services_last_update', null );
		}

		protected function update_last_fetch_timestamp() {
			WC_Connect_Options::update_option( 'services_last_update', time() );
		}

		protected function maybe_update_heartbeat() {
			$last_heartbeat = WC_Connect_Options::get_option( 'last_heartbeat' );
			$now = time();

			if ( ! $last_heartbeat ) {
				$should_update = true;
			} else {
				$last_heartbeat = absint( $last_heartbeat );
				if ( $last_heartbeat > $now ) {
					// last heartbeat in the future? wacky
					$should_update = true;
				} else {
					$elapsed = $now - $last_heartbeat;
					$should_update = $elapsed > DAY_IN_SECONDS;
				}
			}

			if ( $should_update ) {
				WC_Connect_Options::update_option( 'last_heartbeat', $now );
			}
		}

		/**
		 * Returns all service ids of a specific type (e.g. shipping)
		 *
		 * @param string $type The type of services to return
		 *
		 * @return array An array of that type's service ids, or an empty array if no such type is known
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
			$service_schemas = $this->get_service_schemas();
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
		 * @param string $service_id The service id for which to return the schema
		 *
		 * @return object|null The service schema or null if no such id was found
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
		 * @param $method_id
		 *
		 * @return object|null The service schema or null if no such id was found
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
		 * @param string $instance_id The shipping zone instance id for which to return the schema
		 *
		 * @return object|null The service schema or null if no such instance was found
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

		public function get_predefined_packages_schema() {
			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) ) {
				return null;
			}

			$predefined_packages = array();
			foreach( $service_schemas->shipping as $service_schema ) {
				if ( ! isset( $service_schema->packages ) ) {
					continue;
				}

				$predefined_packages[ $service_schema->id ] = $service_schema->packages;
			}

			return $predefined_packages;
		}
	}
}
