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
		 * Initialize the class and set up action hooks.
		 */
		public static function init() {
			add_action( 'woocommerce_cart_calculate_fees', array( static::class, 'add_us_co_retail_delivery_fee' ), 10 );
		}
		/**
		 * Add US Colorado Retail Delivery Fee Tax.
		 * Uses the WooCommerce fees API `$cart->add_fee()`.
		 *
		 * Colorado Retail Delivery Fee Tax:
		 * https://www.avalara.com/blog/en/north-america/2022/10/what-you-need-to-know-about-the-colorado-retail-delivery-fee-now.html
		 *
		 * RDF fee is DISABLED by default - not all business are required to charge the fee.
		 * To apply the fee use `wc_services_apply_us_co_retail_delivery_fee` filter.
		 * Change boolian flag to `true`
		 * Example: `add_filter( 'wc_services_apply_us_co_retail_delivery_fee', '__return_true' );`
		 *
		 * @param WC_Cart $cart WooCommerce Cart object.
		 */
		public static function add_us_co_retail_delivery_fee( $cart ) {

			/**
			 * Filter should Retail Delivery Fee be applied.
			 * Default: false.
			 *
			 * @since 2.9.0
			 *
			 * @param bool    Should the Retail Delivery Fee be applied.
			 * @param WC_Cart WooCommerce cart object.
			 */
			if ( ! apply_filters( 'wc_services_enable_us_co_retail_delivery_fee', false, $cart ) ) {
				return;
			}

			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			if ( false === ( $cart instanceof WC_Cart ) ) {
				return;
			}

			// Do not apply RDF if the customer is not in US Colorado.
			if (
				'US' !== WC()->customer->get_shipping_country()
				|| 'CO' !== WC()->customer->get_shipping_state()
			) {
				return;
			}

			// Do not apply RDF when every item in order is exempt from Colorado sales tax.
			if (
				! is_array( $cart->get_cart_contents_taxes() )
				|| 0 === count( $cart->get_cart_contents_taxes() )
			) {
				return;
			}

			// Do not apply RDF if all shipping methods use Local Pickup.
			if ( 0 === count(
				array_diff(
					wc_get_chosen_shipping_method_ids(),
					/**
					 * Filters local pickup shipping methods.
					 * Copied from WooCommerce core to maintain compatability.
					 *
					 * @since 6.8.0
					 * @param string[] $local_pickup_methods Local pickup shipping method IDs.
					 */
					apply_filters( 'woocommerce_local_pickup_methods', array( 'legacy_local_pickup', 'local_pickup' ) )
				)
			) ) {
				return;
			}

			// Do not apply RDF if all products are virtual.
			if ( ! $cart->needs_shipping() ) {
				return;
			}

			/**
			 * Filter for manipulate the custom surcharge.
			 * 
			 * As of July 1, 2024 till June 30, 2025 RDF is 29 cents per order
			 * RDF is subject to sales tax.
			 * https://www.avalara.com/blog/en/north-america/2022/10/what-you-need-to-know-about-the-colorado-retail-delivery-fee-now.html.
			 *
			 * @since 2.9.0
			 *
			 * @param array   Custom surcharge info.
			 * @param WC_Cart WooCommerce cart object.
			 */
			$fee_info = apply_filters(
				'wc_services_apply_us_co_retail_delivery_fee',
				array(
					'value' => 0.29,
					'text'  => __( 'Retail Delivery Fee', 'woocommerce_services' ),
				),
				$cart
			);

			if (
				! empty( $fee_info['text'] ) &&
				isset( $fee_info['value'] ) && is_numeric( $fee_info['value'] )
			) {
				$cart->add_fee( $fee_info['text'], floatval( $fee_info['value'] ), true, 'standard' );
			}
		}
	}
}
