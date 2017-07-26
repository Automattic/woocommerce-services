<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Base_Controller' ) ) {
	return;
}

class WC_Connect_Api_Exception extends Exception {
	public $response;
	public function __construct( $response ) {
		$this->response = $response;
		parent::__construct();
	}
}

abstract class WC_REST_Connect_Base_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * @var WC_Connect_API_Client
	 */
	protected $api_client;

	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	/**
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger ) {
		$this->api_client = $api_client;
		$this->settings_store = $settings_store;
		$this->logger = $logger;
	}

	public function register_routes() {
		if ( method_exists( $this, 'get' ) ) {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_internal' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			) );
		}
		if ( method_exists( $this, 'post' ) ) {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'post_internal' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			) );
		}
	}

	public function get_internal( $request ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // Play nice with WP-Super-Cache
		}
		try {
			return $this->get( $request );
		} catch ( WCC_Api_Exception $error ) {
			return $error->response;
		}
	}

	public function post_internal( $request ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // Play nice with WP-Super-Cache
		}
		try {
			return $this->post( $request );
		} catch ( WC_Connect_Api_Exception $ex ) {
			return $ex->response;
		}
	}

	protected function api_request( $method /*, ...$args */ ) {
		$args = array_slice( func_get_args(), 1 );
		$response = call_user_func_array( array( $this->api_client, $method ), $args );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->debug( $error, get_class( $this ) );
			throw new WC_Connect_Api_Exception( $error );
		}

		if ( isset( $response->async_token ) || isset( $response->async_status ) ) {
			// Not an error per-se, but we want to interrupt further processing of the response at this point
			throw new WC_Connect_Api_Exception( $response );
		}

		return $response;
	}

	/**
	 * Validate the requester's permissions
	 */
	public function check_permission( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
