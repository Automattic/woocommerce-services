<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is the base class. Its static members auto-select the correct version to use.
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility' ) ) {
	/**
	 * WC_Connect_Compatibility class.
	 */
	abstract class WC_Connect_Compatibility {

		/**
		 * WC_Connect_Compatibility.
		 *
		 * @var WC_Connect_Compatibility
		 */
		private static $singleton;

		/**
		 * Woocommerce version.
		 *
		 * @var string
		 */
		private static $version = WC_VERSION;

		/**
		 * WC_Connect_Compatibility singleton instance.
		 *
		 * @return WC_Connect_Compatibility
		 */
		public static function instance() {
			if ( is_null( self::$singleton ) ) {
				self::$singleton = self::select_compatibility();
			}

			return self::$singleton;
		}

		/**
		 * Return subclass for active version of WooCommerce.
		 *
		 * @return WC_Connect_Compatibility subclass for active version of WooCommerce
		 */
		private static function select_compatibility() {
			if ( version_compare( self::$version, '6.9.0', '<' ) ) {
				require_once 'class-wc-connect-compatibility-wc30.php';

				return new WC_Connect_Compatibility_WC30();
			} else {
				require_once 'class-wc-connect-compatibility-wc69.php';

				return new WC_Connect_Compatibility_WC69();
			}
		}

		/**
		 * Overwrite default WooCommerce Version.
		 *
		 * @param string $value WooCommerce Version.
		 * @return void
		 */
		public static function set_version( $value ) {
			self::$singleton = null;
			self::$version   = $value;
		}

		/**
		 * Revert to current WooCommerce Version.
		 *
		 * @return void
		 */
		public static function reset_version() {
			self::$singleton = null;
			self::$version   = WC_VERSION;
		}

		/**
		 * Return the order admin screen
		 *
		 * @return string The order admin screen
		 */
		abstract public function get_order_admin_screen();

		/**
		 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
		 *
		 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
		 *
		 * @return bool|WC_Order|WC_Order_Refund.
		 */
		abstract public function init_theorder_object( $post_or_order_object );

	}
}
