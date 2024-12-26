<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions higher than 2.6 (3.0 and higher)
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Custom_Surcharge' ) ) {
	/**
	 * WC_Connect_Compatibility class.
	 */
	class WC_Connect_Custom_Surcharge {

		/**
		 * Get US Colorado custom surcharge eligibility.
		 *
		 * @param WC_Cart|null $cart Cart object or null.
		 *
		 * @return boolean.
		 */
		public static function get_us_co_eligibility( $cart = null ) {
			if ( $cart instanceof WC_Cart ) {
				return true;
			}

			return false;
		}
	}
}
