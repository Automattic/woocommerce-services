<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Address_Controller' ) ) {
	return;
}

class WC_REST_Connect_Address_Controller extends WC_REST_Connect_Base_Controller {
	protected $method = 'POST';
	protected $rest_base = 'connect/update-address';

	public function run( $request ) {
		$data     = $request->get_json_params();
		$order_id = $data[ 'orderId' ];
		$type     = $data[ 'type' ];
		$address  = $data[ 'address' ];

		$this->update_address( $order_id, $type, $address );

		return array(
			'success' => true,
		);
	}

	/**
	 * Validate the requester's permissions
	 */
	public function check_permission( $request ) {
		$data = $request->get_json_params();
		if ( 'origin' === $data['type'] ) {
			return current_user_can( 'manage_woocommerce' ); // Only an admin can normalize the origin address
		}
		return true; // non-authenticated service for the 'destination' address
	}

	public static function update_address( $order_id, $type, $api_address ) {
		if ( ! $order_id || 'destination' !== $type ) {
			return;
		}

		$order = wc_get_order( $order_id );
		$wc_address = $order->get_address( 'shipping' );

		$new_address = array_merge( array(), ( array ) $wc_address, ( array ) $api_address );
		//rename address to address_1
		$new_address[ 'address_1' ] = $new_address[ 'address' ];
		//remove api-specific fields
		unset( $new_address[ 'address' ], $new_address[ 'name' ] );

		$order->set_address( $new_address, 'shipping' );
		update_post_meta( $order->id, 'wc_connect_destination_normalized', true );
	}
}