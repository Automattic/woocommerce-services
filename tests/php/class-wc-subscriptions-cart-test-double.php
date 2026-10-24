<?php
/**
 * Stand-in for WC_Subscriptions_Cart, which the TaxJar integration calls.
 *
 * Subscriptions is not installed in the test environment. This double copies only the
 * behaviour get_line_items() depends on, and prices a line as before unless a test turns
 * on the synchroniser double's free trial.
 *
 * @package WooCommerce\Tests
 */

if ( ! class_exists( 'WC_Subscriptions_Cart' ) ) {
	/**
	 * Double for WC_Subscriptions_Cart.
	 */
	class WC_Subscriptions_Cart {

		/**
		 * 'none' while the initial cart is totalled, 'recurring_total' for a recurring cart.
		 *
		 * @var string
		 */
		public static $calculation_type = 'none';

		/**
		 * The calculation type Subscriptions reports.
		 *
		 * @return string
		 */
		public static function get_calculation_type() {
			return self::$calculation_type;
		}

		/**
		 * The price of the first payment. With the free trial on it is the sign-up fee,
		 * which these tests leave at $0.
		 *
		 * @param mixed      $price   Product price.
		 * @param WC_Product $product Product.
		 * @return mixed
		 */
		public static function set_subscription_prices_for_calculation( $price, $product ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Same signature as Subscriptions.
			if ( 'none' === self::$calculation_type && WC_Subscriptions_Synchroniser::$free_trial ) {
				return 0.0;
			}

			return $price;
		}
	}
}
