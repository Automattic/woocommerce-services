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

	/**
	 * Endpoint method (GET/POST/PUT...)
	 * @var string
	 */
	protected $method;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger ) {
		$this->api_client = $api_client;
		$this->settings_store = $settings_store;
		$this->logger = $logger;
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => $this->method,
				'callback'            => array( $this, 'run_internal' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );
	}

	public function run_internal( $request ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true ); // Play nice with WP-Super-Cache
		}
		return $this->run( $request );
	}

	abstract public function run( $request );

	/**
	 * Validate the requester's permissions
	 */
	public function check_permission( $request ) {
		return current_user_can( 'manage_woocommerce' );
	}

}
