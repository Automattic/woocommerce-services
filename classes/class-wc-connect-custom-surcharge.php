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
		 * Add US Colorado "Retail Delivery Fee" Tax.
		 *
		 * @param WC_Cart|null $cart Cart object or null.
		 *
		 * @return boolean.
		 */
		public static function add_us_co_rdf( $cart = null ) {
			if ( false === ( $cart instanceof WC_Cart ) ) {
				return false;
			}

			if ( ! is_array( $cart->get_cart_contents_taxes() ) || 0 === count( $cart->get_cart_contents_taxes() ) ) {
				return false;
			}

			// Do not apply the fee if all products are virtual.
			if ( ! $cart->needs_shipping() ) {
				return false;
			}
	
			// Do not apply the fee if all shipping methods use Local Pickup.
			if ( 0 === count( array_diff( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) ) {
				return false;
			}
			
			return array(
				'fee'      => 0.27,
				'fee_text' => __( 'Retail Delivery Fee', 'woocommerce_services' ),
			);
		}
	}
}
