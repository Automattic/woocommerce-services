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
				$this->logger->log( $response_body, __FUNCTION__ );
				return;
			}

			$this->logger->log( 'Successfully loaded service schemas from server response.', __FUNCTION__ );

			// If we made it this far, it is safe to store the object

			$this->update_service_schemas( $response_body );
		}

		public function get_service_schemas() {
			return get_option( 'wc_connect_services', null );
		}

		protected function update_service_schemas( $service_schemas ) {
			update_option( 'wc_connect_services', $service_schemas );
		}

		/**
		 * Returns all service ids of a specific type (e.g. shipping)
		 *
		 * @param string $type The type of services to return
		 *
		 * @return array An array of that type's service ids, or an empty array if no such type is known
		 */
		public function get_all_service_ids_of_type( $type ) {
			$service_schema_ids = array();

			if ( empty( $type ) ) {
				return $service_schema_ids;
			}

			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) ) {
				return $service_schema_ids;
			}

			if ( ! property_exists( $service_schemas, $type ) ) {
				return $service_schema_ids;
			}

			if ( ! is_array( $service_schemas->$type ) ) {
				return $service_schema_ids;
			}

			foreach ( $service_schemas->$type as $service_schema ) {
				$service_schema_ids[] = $service_schema->id;
			}

			return $service_schema_ids;
		}

		/**
		 * Returns a particular service's schema given its id
		 *
		 * @param string service_id The service id for which to return the schema
		 *
		 * @return object|null The service schema or null if no such id was found
		 */
		public function get_service_schema_by_id( $service_id ) {
			$service_schemas = $this->get_service_schemas();
			if ( ! is_object( $service_schemas ) ) {
				return null;
			}

			foreach ( $service_schemas as $service_type => $service_type_service_schemas ) {
				foreach ( $service_type_service_schemas as $service_schema ) {
					if ( $service_schema->id === $service_id ) {
						return $service_schema;
					}
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
			$service_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d;",
					$instance_id
				)
			);

			return $this->get_service_schema_by_id( $service_id );
		}

		/**
		 * Returns a service's schema given an id or shipping zone instance.
		 *
		 * @param string $id_or_instance_id String ID or numeric instance ID.
		 * @return bool|object Service schema on success, boolean False on failure.
		 */
		public function get_service_schema_by_id_or_instance_id( $id_or_instance_id ) {

			if ( is_numeric( $id_or_instance_id ) ) {
				return $this->get_service_schema_by_instance_id( $id_or_instance_id );
			}

			if ( ! empty( $id_or_instance_id ) ) {
				return $this->get_service_schema_by_id( $id_or_instance_id );
			}

			return false;
		}
	}
}
