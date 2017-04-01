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
		/**
		 * Get the ID for a given Order.
		 *
		 * @param WC_Order $order
		 *
		 * @return int
		 */
		public function get_order_id( WC_Order $order ) {
			return $order->get_id();
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
			if ( is_array( $item ) ) {
				return wc_get_product( $item[ 'product_id' ] );
			}
			return $item->get_product();
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
			return wc_get_formatted_variation( $product, $flat );
		}
	}

}
