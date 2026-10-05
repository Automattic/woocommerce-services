<?php
/**
 * Puts the site in a real Jetpack connection state for tests, and takes it out again.
 *
 * @package WC_Connect
 */

use Automattic\Jetpack\Connection\Manager;
use Automattic\Jetpack\Status\Cache;

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCS_Test_Jetpack_Connection' ) ) {

	/**
	 * Writes the options the Jetpack connection package reads (blog id, blog token, owner and
	 * owner token), so WC_Connect_Jetpack reports a connected store with a real owner. Call
	 * reset() in tear_down() of every test that uses it.
	 */
	class WCS_Test_Jetpack_Connection {

		/**
		 * Jetpack options connect() writes.
		 *
		 * @var string[]
		 */
		const OPTIONS = array( 'id', 'blog_token', 'user_tokens', 'master_user' );

		/**
		 * Manager properties that memoize the connection state for the request.
		 *
		 * @var string[]
		 */
		const MANAGER_CACHES = array( 'is_connected', 'connection_owner_id' );

		/**
		 * The jetpack_offline_mode option before connect(), or null when it was not set.
		 *
		 * @var array|null
		 */
		private static $saved_offline_option = null;

		/**
		 * Connect the site with the given user as the connection owner, offline mode off.
		 *
		 * @param int $owner_id User ID of the connection owner.
		 */
		public static function connect( $owner_id ) {
			// Status reads this option after the filter, so it must not turn offline mode back on.
			if ( null === self::$saved_offline_option ) {
				$sentinel                   = new stdClass();
				$value                      = get_option( 'jetpack_offline_mode', $sentinel );
				self::$saved_offline_option = array(
					'exists' => $sentinel !== $value,
					'value'  => $value,
				);
			}
			delete_option( 'jetpack_offline_mode' );

			Jetpack_Options::update_option( 'id', 12345 );
			Jetpack_Options::update_option( 'blog_token', 'blogtoken.blogsecret' );
			Jetpack_Options::update_option( 'user_tokens', array( $owner_id => "usertoken.usersecret.{$owner_id}" ) );
			Jetpack_Options::update_option( 'master_user', $owner_id );

			self::set_offline( false );
		}

		/**
		 * Force Jetpack offline mode on or off.
		 *
		 * @param bool $offline Whether offline mode is on.
		 */
		public static function set_offline( $offline ) {
			remove_filter( 'jetpack_offline_mode', '__return_true' );
			remove_filter( 'jetpack_offline_mode', '__return_false' );
			add_filter( 'jetpack_offline_mode', $offline ? '__return_true' : '__return_false' );

			self::reset_caches();
		}

		/**
		 * Remove everything connect() and set_offline() changed.
		 */
		public static function reset() {
			Jetpack_Options::delete_option( self::OPTIONS );
			remove_filter( 'jetpack_offline_mode', '__return_true' );
			remove_filter( 'jetpack_offline_mode', '__return_false' );

			if ( null !== self::$saved_offline_option ) {
				if ( self::$saved_offline_option['exists'] ) {
					update_option( 'jetpack_offline_mode', self::$saved_offline_option['value'] );
				} else {
					delete_option( 'jetpack_offline_mode' );
				}
				self::$saved_offline_option = null;
			}

			self::reset_caches();
		}

		/**
		 * Drop the connection state the Jetpack packages memoize for the request.
		 */
		public static function reset_caches() {
			foreach ( self::MANAGER_CACHES as $name ) {
				if ( property_exists( Manager::class, $name ) ) {
					$property = new ReflectionProperty( Manager::class, $name );
					$property->setAccessible( true );
					$property->setValue( null, null );
				}
			}

			Cache::clear();
		}
	}
}
