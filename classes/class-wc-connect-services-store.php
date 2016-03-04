<?php

if ( ! class_exists( 'WC_Connect_Services_Store' ) ) {

	class WC_Connect_Services_Store {

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @var WC_Connect_Services_Validator
		 */
		protected $validator;

		public function __construct(
			WC_Connect_API_Client $api_client,
			WC_Connect_Logger $logger,
			WC_Connect_Services_Validator $validator
		) {

			$this->api_client = $api_client;
			$this->logger     = $logger;
			$this->validator  = $validator;

		}

		public function fetch_services_from_connect_server() {

			$response = $this->api_client->get_services();

			if ( is_wp_error( $response ) ) {
				$this->logger->log(
					$response,
					__FUNCTION__
				);

				return;
			}

			if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
				$this->logger->log(
					$this->api_client->decode_error_from_response( $response ),
					__FUNCTION__
				);

				return;
			}

			$body = $this->api_client->decode_body_from_response( $response );

			if ( ! $body ) {
				$this->logger->log(
					'Server response did not include body. Service schemas not updated.',
					__FUNCTION__
				);

				return;
			}

			if ( ! $this->validator->validate_services( $body ) ) {
				$this->logger->log(
					'One or more service schemas failed to validate. Will not store services in options.',
					__FUNCTION__
				);

				return;
			}

			$this->logger->log(
				'Successfully loaded service schemas from server response.',
				__FUNCTION__
			);

			// If we made it this far, it is safe to store the object
			$this->update_services( $body );
		}

		public function get_services() {
			return get_option( 'wc_connect_services', null );
		}

		protected function update_services( $services ) {
			update_option( 'wc_connect_services', $services );
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

			$services = $this->get_services();
			if ( ! is_object( $services ) ) {
				return array();
			}

			if ( ! property_exists( $services, $type ) ) {
				return array();
			}

			if ( ! is_array( $services->$type ) ) {
				return array();
			}

			$service_ids = array();

			foreach ( $services->$type as $service ) {
				$service_ids[] = $service->id;
			}

			return $service_ids;
		}

		/**
		 * Returns a particular service's properties given its id
		 *
		 * @param string service_id The service id for which to return properties
		 *
		 * @return object|null The service properties or null if no such id was found
		 */
		public function get_service_by_id( $service_id ) {
			$services = $this->get_services();
			if ( ! is_object( $services ) ) {
				return null;
			}

			foreach ( $services as $service_type => $service_type_services ) {
				foreach ( $service_type_services as $service ) {
					if ( $service->id === $service_id ) {
						return $service;
					}
				}
			}

			return null;
		}

		/**
		 * Returns a service's properties given its shipping zone instance
		 *
		 * @param string $instance_id The shipping zone instance id for which to return properties
		 *
		 * @return object|null The service properties or null if no such instance was found
		 */
		public function get_service_by_instance_id( $instance_id ) {
			global $wpdb;
			$service_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d;",
					$instance_id
				)
			);

			return $this->get_service_by_id( $service_id );
		}

		/**
		 * Returns a service's properties given an id or shipping zone instance.
		 *
		 * @param string $id_or_instance_id String ID or numeric instance ID.
		 * @return bool|object Service object on success, boolean False on failure.
		 */
		public function get_service_by_id_or_instance_id( $id_or_instance_id ) {

			if ( is_numeric( $id_or_instance_id ) ) {

				return $this->get_service_by_instance_id( $id_or_instance_id );

			}

			if ( ! empty( $id_or_instance_id ) ) {

				return $this->get_service_by_id( $id_or_instance_id );

			}

			return false;

		}

	}

}
