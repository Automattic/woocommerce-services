<?php

class WP_Test_WC_Connect_Shipping_Label extends WC_Unit_Test_Case {
	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-compatibility.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-shipping-label.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-settings-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client-live.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-schemas-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-payment-methods-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-account-settings.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-package-settings.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-continents.php' );

		WC_Connect_Compatibility::set_version( '3.0.0' );
	}

	public function get_order_for_api() {
		$mock_order_item_shipping = $this->createMock( WC_Order_Item_Shipping::class );
		$mock_order_item_shipping->expects( $this->once() )
			->method('get_method_id')
			->willReturn('flat_rate');
		$mock_order_item_shipping->expects( $this->once() )
			->method('get_name')
			->willReturn('Flat rate');
		$mock_order_item_shipping->expects( $this->once() )
			->method('get_total')
			->willReturn(7);

		$mock_order = $this->getMockBuilder( WC_Order::class )
			->disableOriginalConstructor()
			->getMock();
		$mock_order->expects( $this->once() )->method( 'get_shipping_methods' )->willReturn(
			array(
				131 => $mock_order_item_shipping
			)
		);

		$expected = array(
			'id' => 131,
			'method_id' => 'flat_rate',
			'method_title' => 'Flat Rate',
			'total' => 7.00
		);

		$connect_order_presenter = new WC_Connect_Order_Presenter();
		$actual = $connect_order_presenter->get_order_for_api($mock_order);

		$this->assertEquals( $expected, $actual['shipping_lines'] );
	}
}
