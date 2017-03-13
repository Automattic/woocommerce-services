<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is the base class. Its static members auto-select the correct version to use.
 */

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility' ) ) {

	abstract class WC_Connect_Compatibility {
		private static $singleton;

		public static function instance() {
			if( self::$singleton === null ) {
				self::$singleton = self::select_compatibility();
			}

			return self::$singleton;
		}

		private static function select_compatibility() {
			if( doubleval( WC()->version ) <= 2.6 ) {
				return new WC_Connect_Compatibility_WC26();
			} else {
				return new WC_Connect_Compatibility_WC30();
			}
		}

		abstract public function get_order_id( WC_Order $order );
		abstract public function get_item_product( WC_Order $order, $item );
		abstract public function get_formatted_variation( WC_Product $product, $flat = false );
	}

}
