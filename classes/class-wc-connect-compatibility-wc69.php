<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions 6.9 and higher
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility_WC69' ) ) {
	/**
	 * WC_Connect_Compatibility class.
	 */
	class WC_Connect_Compatibility_WC69 extends WC_Connect_Compatibility {

		/**
		 * Return the order admin screen
		 *
		 * @return string The order admin screen
		 */
		public function get_order_admin_screen(): string {
			return OrderUtil::get_order_admin_screen();
		}

		/**
		 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
		 *
		 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
		 *
		 * @return bool|WC_Order|WC_Order_Refund WC_Order object.
		 */
		public function init_theorder_object( $post_or_order_object ) {
			return OrderUtil::init_theorder_object( $post_or_order_object );
		}
	}
}
