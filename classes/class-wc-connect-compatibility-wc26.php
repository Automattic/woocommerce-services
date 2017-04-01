<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions 2.6 and lower
 */

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility_WC26' ) ) {

	class WC_Connect_Compatibility_WC26 extends WC_Connect_Compatibility {
		/**
		 * Get the ID for a given Order.
		 *
		 * @param WC_Order $order
		 *
		 * @return int
		 */
		public function get_order_id( WC_Order $order ) {
			return $order->id;
		}

		/**
		 * Retrieve the corresponding Product for the given Order Item.
		 *
		 * @param WC_Order $order
		 * @param WC_Order_Item|WC_Order_Item_Product|array $item
		 *
		 * @return WC_Product
		 */
		public function get_item_product( WC_Order $order, $item ) {
			return $order->get_product_from_item( $item );
		}

		/**
		 * Get formatted list of Product Variations, if applicable.
		 *
		 * @param WC_Product $product
		 * @param bool $flat
		 *
		 * @return string
		 */
		public function get_formatted_variation( WC_Product $product, $flat = false ) {
			return $product->get_formatted_variation_attributes( $flat );
		}
	}
}
