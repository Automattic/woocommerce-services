<?php

use Automattic\Jetpack\Connection\Tokens;
use Automattic\Jetpack\Connection\Manager;

if ( ! class_exists( 'WC_Connect_Jetpack' ) ) {
	class WC_Connect_Jetpack {
		const PLUGIN_SLUG = 'woocommerce-shipping-tax';

		public static function get_connection_manager() {
			return new Manager( self::PLUGIN_SLUG );
		}

		/**
		 * @param $user_id
		 *
		 * @return stdClass|WP_Error
		 */
		public static function get_master_user_access_token( $user_id ) {
			return self::get_connection_manager()->get_tokens()->get_access_token( $user_id );
		}

		/**
		 * Helper method to get if Jetpack is in development mode
		 *
		 * @return bool
		 */
		public static function is_development_mode() {
			if ( method_exists( '\\Automattic\\Jetpack\\Status', 'is_offline_mode' ) ) {
				$status = new \Automattic\Jetpack\Status();

				return $status->is_offline_mode();
			}

			return false;
		}

		/**
		 * Helper method to get if Jetpack is connected (aka active)
		 *
		 * @return bool
		 */
		public static function is_active() {
			// TODO: check
			return self::get_connection_manager()->is_connected();
		}

		/**
		 * Helper method to get if the current Jetpack website is marked as staging
		 *
		 * @return bool
		 */
		public static function is_staging_site() {
			$jetpack_status = new \Automattic\Jetpack\Status();

			return $jetpack_status->is_staging_site();
		}

		/**
		 * Helper method to get whether the current site is an Atomic site
		 *
		 * @return bool
		 */
		public static function is_atomic_site() {
			// TODO: WTH?
			if ( function_exists( 'jetpack_is_atomic_site' ) ) {
				return jetpack_is_atomic_site();
			} elseif ( function_exists( 'jetpack_is_automated_transfer_site' ) ) {
				return jetpack_is_automated_transfer_site();
			}

			return false;
		}

		public static function get_connected_user_data( $user_id ) {
			return self::get_connection_manager()->get_connected_user_data( $user_id );
		}

		/**
		 * Helper method to get the Jetpack master user, IF we are connected
		 *
		 * @return WP_User | false
		 */
		public static function get_master_user() {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			if ( self::is_active() && method_exists( 'Jetpack_Options', 'get_option' ) ) {
				$master_user_id = Jetpack_Options::get_option( 'master_user' );

				return get_userdata( $master_user_id );
			}

			return false;

		}

		/**
		 * Builds a connect url
		 *
		 * @param $redirect_url
		 *
		 * @return string
		 */
		public static function build_connect_url( $redirect_url ) {
			return Jetpack::init()->build_connect_url(
				true,
				$redirect_url,
				'woocommerce-services-auto-authorize'
			);
		}

		/**
		 * Records a Tracks event
		 *
		 * @param $user
		 * @param $event_type
		 * @param
		 */
		public static function tracks_record_event( $user, $event_type, $data ) {
			if ( class_exists( 'Automattic\\Jetpack\\Tracking' ) ) {
				$tracking = new Automattic\Jetpack\Tracking();

				return $tracking->tracks_record_event( $user, $event_type, $data );
			}

			return false;
		}

		/**
		 * Determines if the current user is connected to Jetpack
		 *
		 * @return bool Whether or nor the current user is connected to Jetpack
		 */
		public static function is_current_user_connected() {
			if ( class_exists( '\Automattic\Jetpack\Connection\Manager' ) && method_exists( '\Automattic\Jetpack\Connection\Manager', 'is_user_connected' ) ) {
				$connection = new Manager();

				return $connection->is_user_connected();
			}

			if ( defined( 'JETPACK_MASTER_USER' ) ) {
				$user_token = self::get_master_user_access_token( JETPACK_MASTER_USER );

				return ( isset( $user_token->external_user_id ) && get_current_user_id() === $user_token->external_user_id );
			}

			return false;
		}

		/**
		 * Determines if Jetpack is connected
		 *
		 * @return bool Whether or nor Jetpack is connected
		 */
		public static function is_connected() {
			if ( class_exists( '\Automattic\Jetpack\Connection\Manager' ) && method_exists( '\Automattic\Jetpack\Connection\Manager', 'is_connected' ) ) {
				$connection = new Manager();

				return $connection->is_connected();
			}

			if ( defined( 'JETPACK_MASTER_USER' ) ) {
				$user_token = self::get_master_user_access_token( JETPACK_MASTER_USER );

				return isset( $user_token->external_user_id );
			}

			return false;
		}

		/**
		 * Check if WCS&T is installed alongside an old version of Jetpack (8.1 or earlier). Due to the autoloader code in those old
		 * versions, the Jetpack Config initialization code would just crash the site.
		 *
		 * @return bool True if the plugin can keep initializing itself, false otherwise.
		 */
		public static function is_jetpack_version_supported() {
			if ( defined( 'JETPACK__VERSION' ) && version_compare( JETPACK__VERSION, '8.2', '<' ) && JETPACK__VERSION !== 'wpcom' ) {
				return false;
			}

			return true;
		}
	}
}
