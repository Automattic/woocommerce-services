<?php
/**
 * A class for working around the quircks and different versions of WordPress/WooCommerce
 */

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility' ) ) {

	class WC_Connect_Compatibility {
		private static $singleton;

		public static function instance() {
			if( self::$singleton === null ) {
				self::$singleton = new WC_Connect_Compatibility();
			}

			return self::$singleton;
		}

		public function __construct() {
		}

		public function get_order_id( WC_Order $order ) {
			if ( is_callable( array( $order, 'get_id' ) ) ) {
				return $order->get_id();
			} else {
				return $order->id;
			}
		}

		public function get_item_product( WC_Order $order, $item ) {
			if ( is_callable( array( $item, 'get_product' ) ) ) {
				return $item->get_product();
			} else {
				return $order->get_product_from_item( $item );
			}
		}

		public function get_formatted_variation( WC_Product $product, $flat = false ) {
			if( is_callable( 'wc_get_formatted_variation' ) ) {
				return wc_get_formatted_variation( $product, $flat );
			} else {
				return $product->get_formatted_variation_attributes( $flat );
			}
		}

	}

}
