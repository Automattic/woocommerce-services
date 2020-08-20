<?php

class WP_Test_WC_Connect_Order_Presenter extends WC_Unit_Test_Case {
	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-compatibility.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-order-presenter.php' );

		WC_Connect_Compatibility::set_version( '3.0.0' );
	}

	public function test_get_order_for_api_order_data() {
		// Setup order
		$order = WC_Helper_Order::create_order();
		$order->save();

		$connect_order_presenter = new WC_Connect_Order_Presenter();
		$actual = $connect_order_presenter->get_order_for_api($order);

		// Refer to WC_Helper_Order::create_order()
		$this->assertEquals( 'pending', $actual['status']);
		$this->assertEquals( 1, $actual['customer_id']);
		$this->assertEquals( '50.00', $actual['total']);
		$this->assertEquals( '40.00', $actual['subtotal']);
		$this->assertEquals( '10.00', $actual['total_shipping']);
	}

	public function test_get_order_for_api_shipping_lines() {
		// Setup order
		$order = WC_Helper_Order::create_order();
		$order->save();

		$connect_order_presenter = new WC_Connect_Order_Presenter();
		$actual = $connect_order_presenter->get_order_for_api($order);

		$this->assertEquals( 'flat_rate_shipping', $actual['shipping_lines'][0]['method_id'] );
		$this->assertEquals( 'Flat rate shipping', $actual['shipping_lines'][0]['method_title'] );
		$this->assertEquals( '10.00', $actual['shipping_lines'][0]['total'] );
	}

	public function test_get_order_for_api_line_items() {
		// Setup order
		$order = WC_Helper_Order::create_order();
		$order->save();

		$connect_order_presenter = new WC_Connect_Order_Presenter();
		$actual = $connect_order_presenter->get_order_for_api($order);

		$this->assertEquals( '40.00', $actual['line_items'][0]['subtotal'] );
		$this->assertEquals( '40.00', $actual['line_items'][0]['total'] );
		$this->assertEquals( 4, $actual['line_items'][0]['quantity'] );
		$this->assertEquals( 'DUMMY SKU', $actual['line_items'][0]['sku'] );
		$this->assertEquals( 'Dummy Product', $actual['line_items'][0]['name'] );
	}

	public function test_get_order_for_api_fees_lines() {
		$feeItem = new WC_Order_item_Fee();
		$feeItem->set_total( '15.01' );
		$feeItem->set_total_tax( '5.01' );

		// Setup order
		$order = WC_Helper_Order::create_order();
		$order->add_item($feeItem);
		$order->save();

		$connect_order_presenter = new WC_Connect_Order_Presenter();
		$actual = $connect_order_presenter->get_order_for_api($order);

		$this->assertEquals( '15.01', $actual['fee_lines'][0]['total'] );
		$this->assertEquals( '5.01', $actual['fee_lines'][0]['total_tax'] );
	}

	public function test_get_order_for_api_coupon_lines() {
		$coupon = WC_Helper_Coupon::create_coupon( 'coupon_1' );

		// Setup order
		$order = WC_Helper_Order::create_order();
		$order->apply_coupon($coupon);
		$order->save();

		$connect_order_presenter = new WC_Connect_Order_Presenter();
		$actual = $connect_order_presenter->get_order_for_api($order);

		// Refer to WC_Helper_Coupon::create_coupon()
		$this->assertEquals( 'coupon_1', $actual['coupon_lines'][0]['code'] );
		$this->assertEquals( '1.00', $actual['coupon_lines'][0]['amount'] );
	}
}
