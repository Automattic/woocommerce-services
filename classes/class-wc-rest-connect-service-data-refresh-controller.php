<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Service_Data_Refresh_Controller' ) ) {
	return;
}

/**
 * REST controller that refreshes the cached service schemas from the Connect server.
 */
class WC_REST_Connect_Service_Data_Refresh_Controller extends WC_REST_Connect_Base_Controller {
	/**
	 * The REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/service-data-refresh';

	/**
	 * The service schemas store.
	 *
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $services_schemas_store;

	/**
	 * Set the service schemas store.
	 *
	 * @param WC_Connect_Service_Schemas_Store $services_schemas_store The service schemas store.
	 */
	public function set_service_schemas_store( $services_schemas_store ) {
		$this->services_schemas_store = $services_schemas_store;
	}

	/**
	 * Refresh the service schemas from the Connect server.
	 *
	 * @return WP_REST_Response The response indicating success and schema availability.
	 */
	public function post() {
		$result = $this->services_schemas_store->fetch_service_schemas_from_connect_server();
		if ( false === $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
				),
				500
			);
		}

		$schemas = $this->services_schemas_store->get_service_schemas();

		return new WP_REST_Response(
			array(
				'success'             => true,
				'timestamp'           => $this->services_schemas_store->get_last_fetch_timestamp(),
				'has_service_schemas' => ! is_null( $schemas ),
			),
			200
		);
	}
}
