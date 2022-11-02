<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions 6.9 and higher
 */

use Automattic\WooCommerce\Utilities\OrderUtil;

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Compatibility_WC69' ) ) {
	/**
	 * WC_Connect_Compatibility class.
	 */
	class WC_Connect_Compatibility_WC69 extends WC_Connect_Compatibility {

		/**
		 * Get the ID for a given Order.
		 *
		 * @param WC_Order $order WC Order.
		 *
		 * @return int
		 */
		public function get_order_id( WC_Order $order ): int {
			return $order->get_id();
		}

		/**
		 * Get admin url for a given order
		 *
		 * @param WC_Order $order WC Order.
		 *
		 * @return string
		 */
		public function get_edit_order_url( WC_Order $order ): string {
			return $order->get_edit_order_url();
		}

		/**
		 * Get the payment method for a given Order.
		 *
		 * @param WC_Order $order WC Order.
		 *
		 * @return string
		 */
		public function get_payment_method( WC_Order $order ): string {
			return $order->get_payment_method();
		}

		/**
		 * Retrieve the corresponding Product for the given Order Item.
		 *
		 * @param WC_Order                                  $order WC Order.
		 * @param WC_Order_Item|WC_Order_Item_Product|array $item  Order Item.
		 *
		 * @return WC_Product|null|false
		 */
		public function get_item_product( WC_Order $order, $item ) {
			if ( is_array( $item ) ) {
				return wc_get_product( $item['product_id'] );
			}
			if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
				/**
				 * Order Item Product
				 *
				 * @var WC_Order_Item_Product $item
				*/
				return $item->get_product();
			}

			return false;
		}

		/**
		 * Get formatted list of Product Variations, if applicable.
		 *
		 * @param WC_Product_Variation $product WC Product.
		 * @param bool                 $flat    Should this be a flat list or HTML list? (default: false).
		 *
		 * @return string
		 */
		public function get_formatted_variation( WC_Product_Variation $product, $flat = false ): string {
			return wc_get_formatted_variation( $product, $flat );
		}

		/**
		 * Get the most specific ID for a given Product.
		 *
		 * Note: Returns the Variation ID for Variable Products.
		 *
		 * @param WC_Product $product WC Product.
		 *
		 * @return int
		 */
		public function get_product_id( WC_Product $product ): int {
			return $product->get_id();
		}

		/**
		 * For a given product ID, it tries to find its name inside an order's line items.
		 * This is useful when an order has a product which was later deleted from the
		 * store.
		 *
		 * @param int      $product_id Product ID or variation ID.
		 * @param WC_Order $order      WC Order.
		 *
		 * @return string The product (or variation) name, ready to print
		 */
		public function get_product_name_from_order( $product_id, $order ): string {
			$line_item = $this->get_line_item_from_order( $product_id, $order );

			if ( ! $line_item ) {
				/* translators: %d: Deleted Product ID */
				return sprintf( __( '#%d - [Deleted product]', 'woocommerce-services' ), $product_id );
			}

			/* translators: %1$d: Product ID, %2$s: Product Name */
			return sprintf( __( '#%1$d - %2$s', 'woocommerce-services' ), $product_id, $line_item->get_name() );
		}

		/**
		 * For a given product ID, it tries to find its price inside an order's line items.
		 *
		 * @param int      $product_id Product ID or variation ID.
		 * @param WC_Order $order      WC Order.
		 *
		 * @return float The product (or variation) price, or NULL if it wasn't found
		 */
		public function get_product_price_from_order( $product_id, $order ): ?float {
			$line_item = $this->get_line_item_from_order( $product_id, $order );

			if ( ! $line_item ) {
				return null;
			}

			return round( floatval( $line_item->get_total() ) / $line_item->get_quantity(), 2 );
		}

		/**
		 * For a given product, return its name. In supported versions, variable
		 * products will include their attributes.
		 *
		 * @param WC_Product $product Product (variable, simple, etc).
		 *
		 * @return string The product (or variation) name, ready to print
		 */
		public function get_product_name( WC_Product $product ): string {
			return $product->get_name();
		}

		/**
		 * Return the order admin screen
		 *
		 * @return string The order admin screen
		 */
		public function get_order_admin_screen(): string {
			return OrderUtil::get_order_admin_screen();
		}

		/**
		 * Helper function to initialize the global $theorder object, mostly used during order meta boxes rendering.
		 *
		 * @param WC_Order|WP_Post $post_or_order_object Post or order object.
		 *
		 * @return bool|WC_Order|WC_Order_Refund WC_Order object.
		 */
		public function init_theorder_object( $post_or_order_object ) {
			return OrderUtil::init_theorder_object( $post_or_order_object );
		}

		/**
		 * Check if order contains given product.
		 *
		 * @param int      $product_id WC Product ID.
		 * @param WC_Order $order      WC Order.
		 *
		 * @return WC_Order_Item_Product|false
		 */
		public function get_line_item_from_order( $product_id, $order ) {
			/**
			 * Order Item Product
			 *
			 * @var WC_Order_Item_Product $line_item
			*/
			foreach ( $order->get_items() as $line_item ) {
				$line_product_id   = $line_item->get_product_id();
				$line_variation_id = $line_item->get_variation_id();

				if ( $line_product_id === $product_id || $line_variation_id === $product_id ) {
					return $line_item;
				}
			}

			return false;
		}

	}
}
