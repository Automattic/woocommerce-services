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

		/**
		 * Get the WC Helper authorization information to use with WC Connect Server requests( e.g. site ID, access token).
		 *
		 * @return array
		 */
		public static function get_wc_helper_auth_info() {
			$helper_auth_data_default = array(
				'access_token'        => '',
				'site_id'             => '',
				'access_token_secret' => '',
			);

			if ( class_exists( 'WC_Helper_Options' ) && is_callable( 'WC_Helper_Options::get' ) ) {
				$helper_auth_data = WC_Helper_Options::get( 'auth' );
			}
			// It's possible for WC_Helper_Options::get() to return false so conditionally send the default array rather than false.
			return $helper_auth_data ? $helper_auth_data : $helper_auth_data_default;
		}
	}
}
