<?php
/**
 * Stand-in for wcs_is_subscription(), which the TaxJar integration calls.
 *
 * Subscriptions is not installed in the test environment. See
 * class-wc-subscription-test-double.php.
 *
 * @package WooCommerce\Tests
 */

if ( ! function_exists( 'wcs_is_subscription' ) ) {
	/**
	 * Double for wcs_is_subscription(), for an object.
	 *
	 * @param mixed $subscription Object to check.
	 * @return bool
	 */
	function wcs_is_subscription( $subscription ) {
		return is_object( $subscription ) && is_a( $subscription, 'WC_Subscription' );
	}
}
