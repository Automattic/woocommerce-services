<?php

if ( ! class_exists( 'WC_Connect_Jetpack' ) ) {
	class WC_Connect_Jetpack {
		/**
		 * Helper method to get if Jetpack is in development mode
		 * @return bool
		 */
		public static function is_development_mode() {
			if ( method_exists( 'Jetpack', 'is_development_mode' ) ) {
				return Jetpack::is_development_mode();
			}

			return false;
		}

		/**
		 * Helper method to get if Jetpack is connected (aka active)
		 * @return bool
		 */
		public static function is_active() {
			if ( method_exists( 'Jetpack', 'is_active' ) ) {
				return Jetpack::is_active();
			}

			return false;
		}

		/**
		 * Helper method to get if the current Jetpack website is marked as staging
		 * @return bool
		 */
		public static function is_staging_site() {
			if ( method_exists( 'Jetpack', 'is_staging_site' ) ) {
				return Jetpack::is_staging_site();
			}

			return false;
		}

		/**
		 * Helper method to get whether the current site is an Atomic site
		 * @return bool
		 */
		public static function is_atomic_site() {
			if ( function_exists( 'jetpack_is_atomic_site' ) ) {
				return jetpack_is_atomic_site();
			} elseif ( function_exists( 'jetpack_is_automated_transfer_site' ) ) {
				return jetpack_is_automated_transfer_site();
			}

			return false;
		}

		public static function get_connected_user_data( $user_id ) {
			if ( method_exists( 'Jetpack', 'get_connected_user_data' ) ) {
				return Jetpack::get_connected_user_data( $user_id );
			}

			return false;
		}

		/**
		 * Helper method to get the Jetpack master user, IF we are connected
		 * @return WP_User | false
		 */
		public static function get_master_user() {
			include_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( self::is_active() && method_exists( 'Jetpack_Options', 'get_option' ) ) {
				$master_user_id = Jetpack_Options::get_option( 'master_user' );
				return get_userdata( $master_user_id );
			}

			return false;
		}

		/**
		 * Builds a connect url
		 * @param $redirect_url
		 * @return string
		 */
		public static function build_connect_url( $redirect_url ) {
			return Jetpack_Dino::build_connect_url( true, $redirect_url, 'woocommerce-services-auto-authorize' );
			// return Jetpack::init()->build_connect_url(
			// 	true,
			// 	$redirect_url,
			// 	'woocommerce-services-auto-authorize'
			// );
		}

		public static function get_access_token( $user_id = false ) {
			if ( ! class_exists( 'Jetpack_Data' ) ) {
				return new WP_Error(
					'jetpack_data_class_not_found',
					__( 'Unable to send request to WooCommerce Services server. Jetpack_Data was not found.', 'woocommerce-services' )
				);
			}

			if ( ! method_exists( 'Jetpack_Data', 'get_access_token' ) ) {
				return new WP_Error(
					'jetpack_data_get_access_token_not_found',
					__( 'Unable to send request to WooCommerce Services server. Jetpack_Data does not implement get_access_token.', 'woocommerce-services' )
				);
			}

			return Jetpack_Data::get_access_token( $user_id );
		}

		/**
		 * Jetpack status constants.
		 */
		const JETPACK_NOT_INSTALLED = 'uninstalled';
		const JETPACK_INSTALLED_NOT_ACTIVATED = 'installed';
		const JETPACK_ACTIVATED_NOT_CONNECTED = 'activated';
		const JETPACK_DEV = 'dev';
		const JETPACK_CONNECTED = 'connected';

		public static function get_jetpack_install_status() {
			// // we need to use validate_plugin to check that Jetpack is installed
			// include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			// // check if jetpack is installed
			// if ( 0 !== validate_plugin( 'jetpack/jetpack.php' ) ) {
			// 	return self::JETPACK_NOT_INSTALLED;
			// }

			// // check if Jetpack is activated
			// if ( ! class_exists( 'Jetpack_Data' ) ) {
			// 	return self::JETPACK_INSTALLED_NOT_ACTIVATED;
			// }

			// if ( defined( 'JETPACK_DEV_DEBUG' ) && true === JETPACK_DEV_DEBUG ) {
			// 	// installed, activated, and dev mode on
			// 	return self::JETPACK_DEV;
			// }

			// // installed, activated, dev mode off
			// // check if connected
			// $user_token = self::get_access_token( JETPACK_MASTER_USER );
			// if ( ! isset( $user_token->external_user_id ) ) { // always an int
			return self::JETPACK_ACTIVATED_NOT_CONNECTED;
			// }

			// return self::JETPACK_CONNECTED;
		}
	}
}
