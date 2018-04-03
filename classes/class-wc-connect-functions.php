<?php

if ( ! class_exists( 'WC_Connect_Functions' ) ) {
	class WC_Connect_Functions {
		/**
		 * Checks if the potentially expensive Shipping/Tax API requests should be sent
		 * based on the context in which they are initialized
		 * @return bool true if the request can be sent, false otherwise
		 */
		public static function should_send_cart_api_request() {
			return ! (
				// Skip for carts loaded from session in the dashboard
				( is_admin() && did_action( 'woocommerce_cart_loaded_from_session' ) ) ||
				// Skip during Jetpack API requests
				( false !== strpos( $_SERVER['REQUEST_URI'], 'jetpack/v4/' ) ) ||
				// Skip during REST API or XMLRPC requests
				( defined( 'REST_REQUEST' ) || defined( 'REST_API_REQUEST' ) || defined( 'XMLRPC_REQUEST' ) ) ||
				// Skip during Jetpack REST API proxy requests
				( isset( $_GET['rest_route'] ) && isset( $_GET['_for'] ) && ( 'jetpack' === $_GET['_for'] ) )
			);
		}
	}
}
