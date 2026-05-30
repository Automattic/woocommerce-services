<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Services_Controller' ) ) {
	return;
}

/**
 * Handles the connect service settings REST endpoint.
 */
class WC_REST_Connect_Services_Controller extends WC_REST_Connect_Base_Controller {
	/**
	 * The REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/services/(?P<id>[a-z_]+)\/(?P<instance>[\d]+)';

	/**
	 * The service schemas store instance.
	 *
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $service_schemas_store;

	/**
	 * Constructor.
	 *
	 * @param WC_Connect_API_Client             $api_client     The API client instance.
	 * @param WC_Connect_Service_Settings_Store $settings_store The service settings store instance.
	 * @param WC_Connect_Logger                 $logger         The logger instance.
	 * @param WC_Connect_Service_Schemas_Store  $schemas_store  The service schemas store instance.
	 */
	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Logger $logger,
		WC_Connect_Service_Schemas_Store $schemas_store
	) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->service_schemas_store = $schemas_store;
	}

	/**
	 * Get the settings for a particular service and instance.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_Error|WP_REST_Response The response on success, or a WP_Error on failure.
	 */
	public function get( $request ) {
		$method_id   = $request['id'];
		$instance_id = isset( $request['instance'] ) ? $request['instance'] : false;

		$service_schema = $this->service_schemas_store->get_service_schema_by_id_or_instance_id(
			$instance_id
			? $instance_id
			: $method_id
		);

		if ( ! $service_schema ) {
			return new WP_Error( 'schemas_not_found', __( 'Service schemas were not loaded', 'woocommerce-services' ), array( 'status' => 500 ) );
		}

		/**
		 * Filters the shipping service settings payload returned for a service instance.
		 *
		 * @since 3.6.3
		 *
		 * @param array  $payload     The service settings payload.
		 * @param string $method_id   The shipping method ID.
		 * @param int    $instance_id The shipping method instance ID.
		 */
		$payload = apply_filters(
			'wc_connect_shipping_service_settings',
			array(
				'success' => true,
			),
			$method_id,
			$instance_id
		);

		return new WP_REST_Response( $payload, 200 );
	}

	/**
	 * Attempts to update the settings on a particular service and instance.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return WP_Error|WP_REST_Response The response on success, or a WP_Error on failure.
	 */
	public function post( $request ) {
		$request_params = $request->get_params();

		$id       = array_key_exists( 'id', $request_params ) ? $request_params['id'] : '';
		$instance = array_key_exists( 'instance', $request_params ) ? absint( $request_params['instance'] ) : false;

		if ( empty( $id ) ) {
			$error = new WP_Error(
				'service_id_missing',
				__( 'Unable to update service settings. Form data is missing service ID.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		$settings = (object) $request->get_json_params();

		if ( empty( $settings ) ) {
			$error = new WP_Error(
				'bad_form_data',
				__( 'Unable to update service settings. The form data could not be read.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		$validation_result = $this->settings_store->validate_and_possibly_update_settings( $settings, $id, $instance );

		if ( is_wp_error( $validation_result ) ) {
			$error = new WP_Error(
				'validation_failed',
				sprintf(
					/* translators: %s: validation error message */
					__( 'Unable to update service settings. Validation failed. %s', 'woocommerce-services' ),
					$validation_result->get_error_message()
				),
				array( 'status' => 400 )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}
