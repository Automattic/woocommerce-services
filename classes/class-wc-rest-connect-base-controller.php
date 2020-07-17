<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Base_Controller' ) ) {
	return;
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
		if ( method_exists( $this, 'delete' ) ) {
			register_rest_route( $this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_internal' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			) );
		}
	}

	/**
	 * Consolidate cache prevention mechanisms.
	 */
	public function prevent_route_caching() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // Play nice with WP-Super-Cache
		}

		// Prevent our REST API endpoint responses from being added to browser cache
		add_filter( 'rest_post_dispatch', array( $this, 'send_nocache_header' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Send a no-cache header for WCS REST API responses. Prompted by cache issues
	 * on the Pantheon hosting platform.
	 *
	 * See: https://pantheon.io/docs/cache-control/
	 *
	 * @param WP_REST_Response $response
	 * @param WP_REST_Server $server
	 *
	 * @return WP_REST_Response passthrough $response parameter
	 */
	public function send_nocache_header( $response, $server ) {
		$server->send_header( 'Cache-Control', 'no-cache, must-revalidate, max-age=0' );

		return $response;
	}

	public function get_internal( $request ) {
		$this->prevent_route_caching();

		return $this->get( $request );
	}

	public function post_internal( $request ) {
		$this->prevent_route_caching();

		return $this->post( $request );
	}

	public function delete_internal( $request ) {
		$this->prevent_route_caching();

		return $this->delete( $request );
	}
	/**
	 * Validate the requester's permissions
	 */
	public function check_permission( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
