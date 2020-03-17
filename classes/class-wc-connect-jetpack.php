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
			if ( method_exists( '\\Automattic\\Jetpack\\Status', 'is_staging_site' ) ) {
				$status = new \Automattic\Jetpack\Status();
				return $status->is_staging_site();
			}

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
			return Jetpack::init()->build_connect_url(
				true,
				$redirect_url,
				'woocommerce-services-auto-authorize'
			);
		}

		/**
		 * Records a Tracks event
		 * @param $user
		 * @param $event_type
		 * @param
		 */
		public static function tracks_record_event( $user, $event_type, $data ) {
			if ( version_compare( JETPACK__VERSION, '7.5', '<' ) ) {
				if ( function_exists( 'jetpack_tracks_record_event' ) ) {
					return jetpack_tracks_record_event( $user, $event_type, $data );
				}
			} elseif ( class_exists( 'Automattic\\Jetpack\\Tracking' ) ) {
				$tracking = new Automattic\Jetpack\Tracking();
				return $tracking->tracks_record_event( $user, $event_type, $data );
			}
			return false;
		}
	}
}
