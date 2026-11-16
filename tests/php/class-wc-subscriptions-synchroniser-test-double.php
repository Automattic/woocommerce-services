<?php
/**
 * Stand-in for WC_Subscriptions_Synchroniser, which the TaxJar integration calls.
 *
 * Subscriptions is not installed in the test environment. This double copies only the
 * behaviour get_line_items() depends on, and does nothing until a test turns it on, so
 * defining it leaves every other test's cart priced as before.
 *
 * @package WooCommerce\Tests
 */

if ( ! class_exists( 'WC_Subscriptions_Synchroniser' ) ) {
	/**
	 * Double for WC_Subscriptions_Synchroniser.
	 *
	 * Subscriptions gives a synced product a free trial while it totals the carts, so the
	 * first payment, which it moves to the sync date, costs $0 and ships nothing.
	 */
	class WC_Subscriptions_Synchroniser {

		/**
		 * Whether the cart holds a synced product whose first payment is moved to the sync date.
		 *
		 * @var bool
		 */
		public static $deferred_first_payment = false;

		/**
		 * Whether that free trial is on.
		 *
		 * @var bool
		 */
		public static $free_trial = false;

		/**
		 * Turn the free trial on, as Subscriptions does for a deferred first payment.
		 *
		 * @param mixed $total Passed through, as the real method is also a filter.
		 * @return mixed
		 */
		public static function maybe_set_free_trial( $total = '' ) {
			if ( self::$deferred_first_payment ) {
				self::$free_trial = true;
			}

			return $total;
		}

		/**
		 * Turn the free trial off.
		 *
		 * @param mixed $total Passed through, as the real method is also a filter.
		 * @return mixed
		 */
		public static function maybe_unset_free_trial( $total = '' ) {
			self::$free_trial = false;

			return $total;
		}

		/**
		 * Put the double back to doing nothing.
		 */
		public static function reset() {
			self::$deferred_first_payment = false;
			self::$free_trial             = false;
		}
	}
}
