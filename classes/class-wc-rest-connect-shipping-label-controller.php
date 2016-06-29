<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'connect/shipping-label';

	/**
	 * Register the routes for shipping labels printing.
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

	// TODO: remove this when the real server-side validation is implemented
	private function validation_error( $fields ) {
		return new WP_Error('validation_failed',
			'Error!!!',
			array(
				'status' => 400,
				'error' => 'validation_failure',
				'data' => array(
					'fields' => $fields,
				),
			)
		);
	}

	public function update_items( $request ) {
		$request_body = $request->get_body();
		$settings = json_decode( $request_body, false, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );

		// TODO: use the real WCC server endpoint to validate
		if ( $settings->orig_address_1 !== 'Hawk St' ) {
			return $this->validation_error( array( 'orig_address_1' ) );

		}

		if ( $settings->dest_address_1 !== 'Hawk St' ) {
			return $this->validation_error( array( 'dest_address_1' ) );
		}

		return $this->validation_error( array( 'cart', 'rate' ) );
	}

	/**
	 * Validate the requester's permissions
	 */
	public function update_items_permissions_check( $request ) {
		return current_user_can( 'edit_shop_order' );
	}

}
