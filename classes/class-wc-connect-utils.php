<?php
/**
 * A class for working around the quirks and different versions of WordPress/WooCommerce
 * This is for versions higher than 2.6 (3.0 and higher)
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Utils' ) ) {
	/**
	 * WC_Connect_Compatibility class.
	 */
	class WC_Connect_Utils {

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
		public static function get_product_name_from_order( $product_id, $order ) {
			$line_item = self::get_line_item_from_order( $product_id, $order );

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
		 * @param WC_Order $order WC Order.
		 *
		 * @return float The product (or variation) price, or NULL if it wasn't found
		 */
		public static function get_product_price_from_order( $product_id, $order ) {
			$line_item = self::get_line_item_from_order( $product_id, $order );

			if ( ! $line_item ) {
				return null;
			}

			return round( floatval( $line_item->get_total() ) / $line_item->get_quantity(), 2 );
		}

		/**
		 * Retrieve the corresponding Product for the given Order Item.
		 *
		 * @param WC_Order                                  $order WC Order.
		 * @param WC_Order_Item|WC_Order_Item_Product|array $item  Order Item.
		 *
		 * @return WC_Product|null|false
		 */
		public static function get_item_product( WC_Order $order, $item ) {
			if ( is_array( $item ) && isset( $item['product_id'] ) ) {
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
		 * Check if order contains given product.
		 *
		 * @param int      $product_id WC Product ID.
		 * @param WC_Order $order      WC Order.
		 *
		 * @return WC_Order_Item_Product|false
		 */
		public static function get_line_item_from_order( $product_id, $order ) {
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
