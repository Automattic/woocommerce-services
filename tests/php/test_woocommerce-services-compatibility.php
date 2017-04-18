<?php

if( ! class_exists( 'WC_Connect_Compatibility' ) ) {
	require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-compatibility.php' );
}

class WP_Test_WC_Services_Compatibility extends WC_Unit_Test_Case {
	public function  test_compatibility_selection() {
		WC_Connect_Compatibility::set_version( '3.0.1' );
		$this->assertEquals( 'WC_Connect_Compatibility_WC30', get_class( WC_Connect_Compatibility::instance() ) );

		WC_Connect_Compatibility::set_version( '2.3.3' );
		$this->assertEquals( 'WC_Connect_Compatibility_WC26', get_class( WC_Connect_Compatibility::instance() ) );

		WC_Connect_Compatibility::reset_version();
	}

	private static function get_id( $object ) {
		if ( version_compare( WC_VERSION, '3.0.0', '<' ) ) {
			return $object->id;
		} else {
			return $object->get_id();
		}
	}

	public function test_get_order_id() {
		$compat = WC_Connect_Compatibility::instance();
		$order = WC_Helper_Order::create_order();
		$next_order = WC_Helper_Order::create_order();
		$id = $compat->get_order_id( $order );
		$this->assertEquals( self::get_id( $order ), $id );
		$this->assertNotEquals( self::get_id( $order ), self::get_id( $next_order ) );
	}

	public function test_get_item_product() {
		$compat = WC_Connect_Compatibility::instance();
		$product = WC_Helper_Product::create_simple_product();
 		$order = WC_Helper_Order::create_order();
 		$items = $order->get_items();
		$item_values = array_values( $items );
		$item = $item_values[ 0 ];

		$result = $compat->get_item_product( $order, $item );

		$this->assertEquals( self::get_id( $product ) + 1, self::get_id( $result ) );
	}
}
