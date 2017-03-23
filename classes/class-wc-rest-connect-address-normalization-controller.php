<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Address_Normalization_Controller' ) ) {
	return;
}

class WC_REST_Connect_Address_Normalization_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/normalize-address';

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

	/**
	 * Register the routes for shipping labels printing.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'update_items_permissions_check' ),
			),
		) );
	}

	public function update_items( $request ) {
		$data    = $request->get_json_params();
		$address = $data['address'];
		$name    = $address['name'];
		$company = $address['company'];
		$phone   = $address['phone'];

		unset( $address['name'], $address['company'], $address['phone'] );

		$body = array(
			'destination' => $address,
			'carrier'     => 'usps', // TODO: remove hardcoding
		);
		$response = $this->api_client->send_address_normalization_request( $body );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		if ( isset( $response->error ) ) {
			$error = new WP_Error(
				$response->error->code,
				$response->error->message,
				array( 'message' => $response->error->message )
			);
			$this->logger->debug( $error, __CLASS__ );
			return $error;
		}

		$response->normalized->name = $name;
		$response->normalized->company = $company;
		$response->normalized->phone = $phone;

		return array(
			'success' => true,
			'normalized' => $response->normalized,
			'is_trivial_normalization' => isset( $response->is_trivial_normalization ) ? $response->is_trivial_normalization : false,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		$data = $request->get_json_params();

		if ( 'origin' === $data['type'] ) {
			return current_user_can( 'manage_woocommerce' ); // Only an admin can normalize the origin address
		}

		return true; // non-authenticated service for the 'destination' address
	}

}
