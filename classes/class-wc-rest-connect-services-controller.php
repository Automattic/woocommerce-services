<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_REST_Connect_Services_Controller' ) ) {

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

		public function __construct( WC_Connect_Service_Schemas_Store $schemas_store, WC_Connect_Service_Settings_Store $settings_store ) {
			$this->service_schemas_store  = $schemas_store;
			$this->service_settings_store = $settings_store;
		}

		public get_rest_base() {
			return $this->rest_base;
		}

		public get_rest_nonce() {
			return wp_create_nonce( 'wp_rest' );
		}

		/**
		 * Register the routes for order notes.
		 */
		public function register_routes() {
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\D]+)\/(?P<instance>[\d]+)', array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
				),
			) );
		}


		/**
		 * Attempts to update the settings on a particular service and instance
		 */
		public function update_item( WP_REST_Request $request ) {

			error_log( print_r( $request, true ) );

			// Validate id with schema store

			// Validate instance with settings store
			// TODO - make sure this works with a brand new instance of the shipping method too

			// TODO - ask settings store to validate and save it.  On success, return new WP_REST_Response( $data, 200 );
			// on failure, return the error from store
			return new WP_Error( 'cant-update', __( 'Unable to update service settings', 'woocommerce'), array( 'status' => 500 ) );
		}

		/**
		* Validate the requestor's permissions
		*/
		public function update_item_permissions_check( /* WP_REST_Request $request */) {
			return current_user_can( 'manage_woocommerce' );
		}

	}

}
