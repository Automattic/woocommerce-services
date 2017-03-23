<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Self_Help_Controller' ) ) {
	return;
}

class WC_REST_Connect_Self_Help_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/self-help';

	/**
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	public function __construct( WC_Connect_Logger $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Register the routes for order notes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'update_items_permissions_check' ),
			),
		) );
	}


	/**
	 * Attempts to update the settings on a particular service and instance
	 */
	public function update_items( $request ) {
		$settings = $request->get_json_params();

		if ( empty( $settings ) || ! array_key_exists( 'wcc_debug_on', $settings ) ) {
			$error = new WP_Error( 'bad_form_data',
				__( 'Unable to update settings. The form data could not be read.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		if ( 1 == $settings['wcc_debug_on'] ) {
			$this->logger->enable_logging();
		} else {
			$this->logger->disable_logging();
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	/**
	* Validate the requestor's permissions
	*/
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}


}
