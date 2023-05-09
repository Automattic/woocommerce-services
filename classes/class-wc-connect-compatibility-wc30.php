<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions higher than 2.6 (3.0 and higher)
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility_WC30' ) ) {
	/**
	 * WC_Connect_Compatibility class.
	 */
	class WC_Connect_Compatibility_WC30 extends WC_Connect_Compatibility {

		/**
		 * Return the order admin screen
		 *
		 * @return string The order admin screen
		 */
		public function get_order_admin_screen() {
			return 'shop_order';
		}

		/**
		 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
		 *
		 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
		 *
		 * @return bool|WC_Order|WC_Order_Refund
		 */
		public function init_theorder_object( $post_or_order_object ) {
			if ( $post_or_order_object instanceof WC_Order ) {
				return $post_or_order_object;
			}

			return wc_get_order( $post_or_order_object->ID );
		}
	}
}
