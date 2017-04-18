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
		 * @param WC_Product_Variation $product
		 * @param bool $flat
		 *
		 * @return string
		 */
		public function get_formatted_variation( WC_Product_Variation $product, $flat = false ) {
			return $product->get_formatted_variation_attributes( $flat );
		}

		/**
		 * Get the most specific ID for a given Product.
		 *
		 * Note: Returns the Variation ID for Variable Products.
		 *
		 * @param WC_Product $product
		 *
		 * @return int
		 */
		public function get_product_id( WC_Product $product ) {
			return ( $product->is_type( 'variation' ) ) ? $product->variation_id : $product->get_id();
		}

		/**
		 * Get the top-level ID for a given Product.
		 *
		 * Note: Returns the Parent ID for Variable Products.
		 *
		 * @param WC_Product $product
		 *
		 * @return int
		 */
		public function get_parent_product_id( WC_Product $product ) {
			return ( $product->is_type( 'variation' ) ) ? $product->parent->get_id() : $product->get_id();
		}
	}
}
