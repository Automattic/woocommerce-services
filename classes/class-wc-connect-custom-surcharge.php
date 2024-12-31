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
			$fee_info = array();

			if ( false === ( $cart instanceof WC_Cart ) ) {
				return $fee_info;
			}

			// Do not apply the fee if all products are virtual.
			if ( WC_Connect_Utils::is_all_products_are_virtual( $cart ) ) {
				return $fee_info;
			}
	
			// Do not apply the fee if all shipping methods use Local Pickup.
			if ( 0 === count( array_diff( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) ) {
				return $fee_info;
			}

			$has_taxable_product = false;

			foreach ( $cart->get_cart() as $cart_item ) {
				if ( $cart_item['data']->is_taxable() ) {
					$has_taxable_product = true;
					break;
				}
			}

			$content_taxes = $cart->get_cart_contents_taxes();

			if ( $has_taxable_product && ! empty( $content_taxes ) ) {
				$fee_info = array(
					'fee'      => 0.27,
					'fee_text' => __( 'Retail Delivery Fee', 'woocommerce_services' ),
				);
			}
			
			return $fee_info;
		}
	}
}
