<?php
/**
 * Stand-in for WC_Subscription, which the TaxJar integration checks for.
 *
 * Subscriptions is not installed in the test environment. A test loads an order as this
 * class through the woocommerce_order_class filter; it is still stored as an order. Its
 * payment date is its own, where Subscriptions gives the last parent or renewal order's:
 * either way an active subscription has one. Load wcs-functions-test-double.php with it.
 *
 * @package WooCommerce\Tests
 */

if ( ! class_exists( 'WC_Subscription' ) ) {
	/**
	 * Double for WC_Subscription.
	 */
	class WC_Subscription extends WC_Order {
	}
}
