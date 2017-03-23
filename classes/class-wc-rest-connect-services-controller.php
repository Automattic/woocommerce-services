<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Services_Controller' ) ) {
	return;
}

class WC_REST_Connect_Services_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/services';

	/**
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $service_schemas_store;

	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $service_settings_store;

	/**
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	/*
	 * @var WC_Connect_Nux
	 */
	protected $nux;

	public function __construct( WC_Connect_Service_Schemas_Store $schemas_store, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Nux $nux, WC_Connect_Logger $logger ) {
		$this->service_schemas_store  = $schemas_store;
		$this->service_settings_store = $settings_store;
		$this->nux = $nux;
		$this->logger = $logger;
	}

	/**
	 * Register the routes for order notes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[a-z_]+)\/(?P<instance>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE, // registers the U (PUT) in CRUD
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
		) );
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/dismiss_notice', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE, // registers the U (PUT) in CRUD
				'callback'            => array( $this, 'dismiss_notice' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
			),
		) );
	}

	/**
	 * Attempts to update the settings on a particular service and instance
	 */
	public function update_item( $request ) {
		$request_params = $request->get_params();

		$id = array_key_exists( 'id', $request_params ) ? $request_params['id'] : '';
		$instance = array_key_exists( 'instance', $request_params ) ? absint( $request_params['instance'] ) : false;

		if ( empty( $id ) ) {
			$error = new WP_Error( 'service_id_missing',
				__( 'Unable to update service settings. Form data is missing service ID.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		$settings = ( object ) $request->get_json_params();

		if ( empty( $settings ) ) {
			$error = new WP_Error( 'bad_form_data',
				__( 'Unable to update service settings. The form data could not be read.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		$validation_result = $this->service_settings_store->validate_and_possibly_update_settings( $settings, $id, $instance );

		if ( is_wp_error( $validation_result ) ) {
			$error = new WP_Error( 'validation_failed',
				sprintf(
					__( 'Unable to update service settings. Validation failed. %s', 'woocommerce-services' ),
					$validation_result->get_error_message()
				),
				array_merge(
					array( 'status' => 400 ),
					$validation_result->get_error_data()
				)
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public function dismiss_notice() {
		$this->nux->dismiss_notice( 'service_settings' );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	* Validate the requestor's permissions
	*/
	public function update_item_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
