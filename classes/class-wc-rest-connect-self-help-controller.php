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

	public function __construct() {
	}

	/**
	 * Register the routes for order notes.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base . '', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
		) );
	}

	/**
	 * Gets the last 10 lines from the WooCommerce Connect log, if it exists
	 */
	protected function get_debug_log_tail() {

		if ( ! method_exists( 'WC_Admin_Status', 'scan_log_files' ) ) {
			return array(
				__( 'Unable to retrieve log file contents. (scan_log_files not found)', 'woocommerce' )
			);
		}

		$logs = WC_Admin_Status::scan_log_files();

		foreach ( $logs as $log_key => $log_file ) {
			if ( "wc-connect-" === substr( $log_key, 0, 11 ) ) {
				$complete_log = file( WC_LOG_DIR . $log_file );
				$log_tail = array_slice( $complete_log, -10 );
				error_log( $log_tail );
				return $log_tail;
			}
		}

		return array(
			__( 'Log is empty', 'woocommerce' )
		);

	}

	/**
	 * Attempts to get the items for the self-help page
	 */
	public function get_items( $request ) {
		$response = array(
			'success' => true,
			'debug' => ( 'true' === get_option( '', 'false' ) ),
			'debug_log_tail' => $this->get_debug_log_tail()
		);

		return new WP_REST_Response( $response, 200 );
	}

	/**
	* Validate the requestor's permissions
	*/
	public function get_items_permissions_check( $request ) {
		return true; // DO NOT COMMIT return current_user_can( 'manage_woocommerce' );
	}

}
