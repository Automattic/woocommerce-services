<?php
/**
 * Test double for StoreApiExtendSchema that simulates container resolution failure.
 *
 * @package Automattic/WCServices
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCServices_Throwing_Store_Api_Extend_Schema' ) ) {

	/**
	 * Forces StoreApiExtendSchema's container resolution to fail with a TypeError, so the
	 * catch ( Throwable ) path in instance() can be exercised without a broken WooCommerce
	 * install (WOOTAX-303).
	 */
	class WCServices_Throwing_Store_Api_Extend_Schema extends \Automattic\WCServices\StoreApi\StoreApiExtendSchema {

		// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- Test double intentionally always throws to simulate a resolution failure.
		/**
		 * Simulate a container that cannot resolve ExtendSchema.
		 *
		 * @throws \TypeError Always, to mimic a container resolution failure.
		 * @return \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema
		 */
		protected static function resolve_extend_schema(): \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema {
			throw new \TypeError( 'Simulated container resolution failure.' );
		}
		// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn
	}
}
