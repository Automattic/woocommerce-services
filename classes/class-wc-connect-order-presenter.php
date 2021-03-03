<?php

if ( ! class_exists( 'WC_Connect_Order_Presenter' ) ) {

	class WC_Connect_Order_Presenter {

		/**
		 * This function transform the WC_Order object to a representational JSON form for the react app.
		 * This is based on WooCommerce v3's get_order API woocommerce/includes/legacy/api/v3/class-wc-api-orders.php
		 *
		 * @param WC_Order $order
		 * @return array
		 */
		public function get_order_for_api( WC_Order $order ) {
			$decimal_point = 2;
			$order_data    = array(
				'id'                        => $order->get_id(),
				'order_number'              => $order->get_order_number(),
				'order_key'                 => $order->get_order_key(),
				'created_at'                => $order->get_date_created()->getTimestamp(),
				'updated_at'                => wc_format_datetime( $order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : 0 ),
				'completed_at'              => wc_format_datetime( $order->get_date_completed() ? $order->get_date_completed()->getTimestamp() : 0 ),
				'status'                    => $order->get_status(),
				'currency'                  => $order->get_currency(),
				'total'                     => wc_format_decimal( $order->get_total(), $decimal_point ),
				'subtotal'                  => wc_format_decimal( $order->get_subtotal(), $decimal_point ),
				'total_line_items_quantity' => $order->get_item_count(),
				'total_tax'                 => wc_format_decimal( $order->get_total_tax(), $decimal_point ),
				'total_shipping'            => wc_format_decimal( $order->get_shipping_total(), $decimal_point ),
				'cart_tax'                  => wc_format_decimal( $order->get_cart_tax(), $decimal_point ),
				'shipping_tax'              => wc_format_decimal( $order->get_shipping_tax(), $decimal_point ),
				'total_discount'            => wc_format_decimal( $order->get_total_discount(), $decimal_point ),
				'shipping_methods'          => $order->get_shipping_method(),
				'payment_details'           => array(
					'method_id'    => $order->get_payment_method(),
					'method_title' => $order->get_payment_method_title(),
					'paid'         => ! is_null( $order->get_date_paid() ),
				),
				'billing_address'           => array(
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'company'    => $order->get_billing_company(),
					'address_1'  => $order->get_billing_address_1(),
					'address_2'  => $order->get_billing_address_2(),
					'city'       => $order->get_billing_city(),
					'state'      => $order->get_billing_state(),
					'postcode'   => $order->get_billing_postcode(),
					'country'    => $order->get_billing_country(),
					'email'      => $order->get_billing_email(),
					'phone'      => $order->get_billing_phone(),
				),
				'shipping_address'          => array(
					'first_name' => $order->get_shipping_first_name(),
					'last_name'  => $order->get_shipping_last_name(),
					'company'    => $order->get_shipping_company(),
					'address_1'  => $order->get_shipping_address_1(),
					'address_2'  => $order->get_shipping_address_2(),
					'city'       => $order->get_shipping_city(),
					'state'      => $order->get_shipping_state(),
					'postcode'   => $order->get_shipping_postcode(),
					'country'    => $order->get_shipping_country(),
				),
				'note'                      => $order->get_customer_note(),
				'customer_ip'               => $order->get_customer_ip_address(),
				'customer_user_agent'       => $order->get_customer_user_agent(),
				'customer_id'               => $order->get_user_id(),
				'view_order_url'            => $order->get_view_order_url(),
				'line_items'                => array(),
				'shipping_lines'            => array(),
				'tax_lines'                 => array(),
				'fee_lines'                 => array(),
				'coupon_lines'              => array(),
			);

			// Add line items.
			foreach ( $order->get_items() as $item_id => $item ) {
				$product   = $item->get_product();
				$item_meta = $item->get_formatted_meta_data();

				foreach ( $item_meta as $key => $values ) {
					$item_meta[ $key ]->label = $values->display_key;
					unset( $item_meta[ $key ]->display_key );
					unset( $item_meta[ $key ]->display_value );
				}

				$line_item = array(
					'id'           => $item_id,
					'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false ), $decimal_point ),
					'subtotal_tax' => wc_format_decimal( $item->get_subtotal_tax(), $decimal_point ),
					'total'        => wc_format_decimal( $order->get_line_total( $item, false, false ), $decimal_point ),
					'total_tax'    => wc_format_decimal( $item->get_total_tax(), $decimal_point ),
					'price'        => wc_format_decimal( $order->get_item_total( $item, false, false ), $decimal_point ),
					'quantity'     => $item->get_quantity(),
					'tax_class'    => $item->get_tax_class(),
					'name'         => $item->get_name(),
					'product_id'   => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
					'sku'          => is_object( $product ) ? $product->get_sku() : null,
					'meta'         => array_values( $item_meta ),
				);

				$order_data['line_items'][] = $line_item;
			}

			// Add shipping.
			foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
				$order_data['shipping_lines'][] = array(
					'id'           => $shipping_item_id,
					'method_id'    => $shipping_item->get_method_id(),
					'method_title' => $shipping_item->get_name(),
					'total'        => wc_format_decimal( $shipping_item->get_total(), $decimal_point ),
				);
			}

			// Add taxes.
			foreach ( $order->get_tax_totals() as $tax_code => $tax ) {
				$tax_line = array(
					'id'       => $tax->id,
					'rate_id'  => $tax->rate_id,
					'code'     => $tax_code,
					'title'    => $tax->label,
					'total'    => wc_format_decimal( $tax->amount, $decimal_point ),
					'compound' => (bool) $tax->is_compound,
				);

				$order_data['tax_lines'][] = $tax_line;
			}

			// Add fees.
			foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
				$order_data['fee_lines'][] = array(
					'id'        => $fee_item_id,
					'title'     => $fee_item->get_name(),
					'tax_class' => $fee_item->get_tax_class(),
					'total'     => wc_format_decimal( $order->get_line_total( $fee_item ), $decimal_point ),
					'total_tax' => wc_format_decimal( $order->get_line_tax( $fee_item ), $decimal_point ),
				);
			}

			// Add coupons.
			foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
				$coupon_line = array(
					'id'     => $coupon_item_id,
					'code'   => $coupon_item->get_code(),
					'amount' => wc_format_decimal( $coupon_item->get_discount(), $decimal_point ),
				);

				$order_data['coupon_lines'][] = $coupon_line;
			}

			return $order_data;
		}
	}
}
