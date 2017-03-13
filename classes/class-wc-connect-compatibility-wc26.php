<?php
/**
 * A class for working around the quircks and different versions of WordPress/WooCommerce
 */

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility_WC26' ) ) {

	class WC_Connect_Compatibility_WC26 extends WC_Connect_Compatibility {
		public function get_order_id( WC_Order $order ) {
			return $order->id;
		}

		public function get_item_product( WC_Order $order, $item ) {
			return $order->get_product_from_item( $item );
		}

		public function get_formatted_variation( WC_Product $product, $flat = false ) {
			return $product->get_formatted_variation_attributes( $flat );
		}
	}
}
