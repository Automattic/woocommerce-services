<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Base_Controller' ) ) {
	return;
}

/**
 * Base controller for Connect REST API endpoints.
 */
abstract class WC_REST_Connect_Base_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * The Connect API client.
	 *
	 * @var WC_Connect_API_Client
	 */
	protected $api_client;

	/**
	 * The service settings store.
	 *
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	/**
	 * The Connect logger.
	 *
	 * @var WC_Connect_Logger
	 */
	protected $logger;

	/**
	 * WC_REST_Connect_Base_Controller constructor.
	 *
	 * @param WC_Connect_API_Client             $api_client     The Connect API client.
	 * @param WC_Connect_Service_Settings_Store $settings_store The service settings store.
	 * @param WC_Connect_Logger                 $logger         The Connect logger.
	 */
	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger ) {
		$this->api_client     = $api_client;
		$this->settings_store = $settings_store;
		$this->logger         = $logger;
	}

	/**
	 * Register the REST API routes for this controller.
	 *
	 * @return void
	 */
	public function register_routes() {
		if ( method_exists( $this, 'get' ) ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => 'GET',
						'callback'            => array( $this, 'get_internal' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);
		}
		if ( method_exists( $this, 'post' ) ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => 'POST',
						'callback'            => array( $this, 'post_internal' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);
		}
		if ( method_exists( $this, 'put' ) ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => 'PUT',
						'callback'            => array( $this, 'put_internal' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);
		}
		if ( method_exists( $this, 'delete' ) ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => 'DELETE',
						'callback'            => array( $this, 'delete_internal' ),
						'permission_callback' => array( $this, 'check_permission' ),
					),
				)
			);
		}
	}

	/**
	 * Consolidate cache prevention mechanisms.
	 */
	public function prevent_route_caching() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			// Play nice with WP-Super-Cache. DONOTCACHEPAGE is a well-known caching constant
			// shared across caching plugins, not a plugin-owned constant to prefix.
			define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- standard caching constant.
		}

		// Prevent our REST API endpoint responses from being added to browser cache.
		add_filter( 'rest_post_dispatch', array( $this, 'send_nocache_header' ), PHP_INT_MAX, 2 );
	}

	/**
	 * Send a no-cache header for WCS REST API responses. Prompted by cache issues
	 * on the Pantheon hosting platform.
	 *
	 * See: https://pantheon.io/docs/cache-control/
	 *
	 * @param WP_REST_Response $response The REST response object.
	 * @param WP_REST_Server   $server   The REST server instance.
	 *
	 * @return WP_REST_Response passthrough $response parameter
	 */
	public function send_nocache_header( $response, $server ) {
		$server->send_header( 'Cache-Control', 'no-cache, must-revalidate, max-age=0' );

		return $response;
	}

	/**
	 * Handle an internal GET request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return mixed The response from the GET handler.
	 */
	public function get_internal( $request ) {
		$this->prevent_route_caching();

		return $this->get( $request );
	}

	/**
	 * Handle an internal POST request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return mixed The response from the POST handler.
	 */
	public function post_internal( $request ) {
		$this->prevent_route_caching();

		return $this->post( $request );
	}

	/**
	 * Handle an internal PUT request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return mixed The response from the PUT handler.
	 */
	public function put_internal( $request ) {
		$this->prevent_route_caching();

		return $this->put( $request );
	}

	/**
	 * Handle an internal DELETE request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return mixed The response from the DELETE handler.
	 */
	public function delete_internal( $request ) {
		$this->prevent_route_caching();

		return $this->delete( $request );
	}

	/**
	 * Validate the requester's permissions.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return bool Whether the requester can manage labels.
	 */
	public function check_permission( $request ) {
		return WC_Connect_Functions::user_can_manage_labels();
	}
}
