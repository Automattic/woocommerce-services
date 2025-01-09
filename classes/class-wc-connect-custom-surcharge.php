<?php
/**
 * A class for custom surcharge.
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Custom_Surcharge' ) ) {
	/**
	 * WC_Connect_Custom_Surcharge class.
	 */
	class WC_Connect_Custom_Surcharge {
		/**
		 * Add a 29 cent surcharge to your cart / checkout based on delivery US Colorado
		 * Taxes, shipping costs and order subtotal are all included in the surcharge amount
		 *
		 * Fee won't be added if all products in the cart are virtual.
		 * 
		 * This fee is being DISABLED by default because there is a lot of consideration for this fee to be applied.
		 * 
		 * If the user wish to use this fee, they can enable it by using `wc_services_apply_us_co_retail_delivery_fee` filter.
		 * Change the 'enabled' value from `false` to `true`. See the example below:
		 * 
		 * `add_filter( 'wc_services_apply_us_co_retail_delivery_fee', function( $fee_info, $cart ) { $fee_info['enabled'] = true; return $fee_info; }, 10, 2 );`
		 *
		 * Uses the WooCommerce fees API
		 *
		 * @param WC_Cart|null $cart WooCommerce Cart object.
		 */
		public static function add_us_co_rdf( $cart = null ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			if ( false === ( $cart instanceof WC_Cart ) ) {
				return;
			}

			if ( ! is_array( $cart->get_cart_contents_taxes() ) || 0 === count( $cart->get_cart_contents_taxes() ) ) {
				return;
			}

			// Do not apply the fee if all products are virtual.
			if ( ! $cart->needs_shipping() ) {
				return;
			}
	
			// Do not apply the fee if all shipping methods use Local Pickup.
			if ( 0 === count( array_diff( wc_get_chosen_shipping_method_ids(), apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) ) ) ) ) {
				return;
			}

			if ( 'US' !== WC()->customer->get_shipping_country() || 'CO' !== WC()->customer->get_shipping_state() ) {
				return;
			}

			/**
			 * Filter for manipulate the custom surcharge.
			 * Set the 'enabled' key to `false` to disable the custom surcharge.
			 *
			 * @since 2.8.6
			 *
			 * @param array   Custom surcharge info.
			 * @param WC_Cart WooCommerce cart object.
			 */
			$fee_info = apply_filters(
				'wc_services_apply_us_co_retail_delivery_fee',
				array(
					'enabled' => false,
					'value'   => 0.29,
					'text'    => __( 'Retail Delivery Fee', 'woocommerce_services' ),
				),
				$cart
			);
			
			if (
				isset( $fee_info['enabled'] ) && true === $fee_info['enabled'] &&
				isset( $fee_info['text'] ) &&
				isset( $fee_info['value'] ) && is_numeric( $fee_info['value'] )
			) {
				$cart->add_fee( $fee_info['text'], floatval( $fee_info['value'] ), true, 'standard' );
			}
		}
	}
}
