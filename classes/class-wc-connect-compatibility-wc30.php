<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions higher than 2.6 (3.0 and higher)
 */

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility_WC30' ) ) {

	class WC_Connect_Compatibility_WC30 extends WC_Connect_Compatibility {
		public function get_order_id( WC_Order $order ) {
			return $order->get_id();
		}

		public function get_item_product( WC_Order $order, $item ) {
			if ( is_array( $item ) ) {
				return wc_get_product( $item[ 'product_id' ] );
			}
			return $item->get_product();
		}

		public function get_formatted_variation( WC_Product $product, $flat = false ) {
			return wc_get_formatted_variation( $product, $flat );
		}
	}

}
