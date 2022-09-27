<?php

if ( ! class_exists( 'WC_Connect_Compatibility' ) ) {
	require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-compatibility.php';
}

class WP_Test_WC_Services_Compatibility extends WC_Unit_Test_Case {
	public function test_compatibility_selection() {
		WC_Connect_Compatibility::set_version( '3.0.1' );
		$this->assertEquals( 'WC_Connect_Compatibility_WC30', get_class( WC_Connect_Compatibility::instance() ) );

		WC_Connect_Compatibility::set_version( '6.9.1' );
		$this->assertEquals( 'WC_Connect_Compatibility_WC69', get_class( WC_Connect_Compatibility::instance() ) );

		WC_Connect_Compatibility::reset_version();
	}

	private static function get_id( $object ) {
		return $object->get_id();
	}

	public function test_get_order_id() {
		$compat     = WC_Connect_Compatibility::instance();
		$order      = WC_Helper_Order::create_order();
		$next_order = WC_Helper_Order::create_order();
		$id         = $compat->get_order_id( $order );
		$this->assertEquals( self::get_id( $order ), $id );
		$this->assertNotEquals( self::get_id( $order ), self::get_id( $next_order ) );
	}

	public function test_get_item_product() {
		$compat      = WC_Connect_Compatibility::instance();
		$product     = WC_Helper_Product::create_simple_product();
		$order       = WC_Helper_Order::create_order();
		$items       = $order->get_items();
		$item_values = array_values( $items );
		$item        = $item_values[0];

		$result = $compat->get_item_product( $order, $item );

		/**
		 * WC_Helper_Order::create_order() creates it's own product for use by default
		 * and only allows a Product to be passed in in WC 3.0.x and up.
		 *
		 * This hack creates an initial product to use as an ID reference, and is
		 * based on the assumption that the product created inside create_order()
		 * will have an ID one greater than the reference product.
		 */
		$this->assertEquals( self::get_id( $product ) + 1, self::get_id( $result ) );
	}

	public function test_get_formatted_variation() {
		$compat     = WC_Connect_Compatibility::instance();
		$product    = WC_Helper_Product::create_variation_product();
		$variations = $product->get_available_variations();
		$variation  = new WC_Product_Variation( $variations[0]['variation_id'] );
		$this->assertTrue( is_string( $compat->get_formatted_variation( $variation ) ) );
		$this->assertRegExp( '/^\<dl class="variation"/', $compat->get_formatted_variation( $variation ) );
	}

	public function test_get_product_id() {
		$compat  = WC_Connect_Compatibility::instance();
		$product = WC_Helper_Product::create_variation_product();
		$id      = $compat->get_product_id( $product );
		$this->assertEquals( self::get_id( $product ), $id );
	}

	public function test_get_parent_product_id() {
		$compat       = WC_Connect_Compatibility::instance();
		$product      = WC_Helper_Product::create_variation_product();
		$variations   = $product->get_available_variations();
		$variation    = new WC_Product_Variation( $variations[0]['variation_id'] );
		$parent_id    = $compat->get_parent_product_id( $product );
		$variation_id = $compat->get_parent_product_id( $variation );
		$this->assertEquals( self::get_id( $product ), $parent_id );
		$this->assertEquals( self::get_id( $product ), $variation_id );
	}

	public function get_product_name_from_order() {
		$compat   = WC_Connect_Compatibility::instance();
		$order    = WC_Helper_Order::create_order();
		$order_id = self::get_id( $order );

		// The order is auto-populated with a product. Delete that product from the database, but not from the order
		$items        = $order->get_items();
		$item_values  = array_values( $items );
		$item         = $item_values[0];
		$product      = $compat->get_item_product( $order, $item );
		$product_id   = self::get_id( $product );
		$product_name = $product->get_title();
		WC_Helper_Product::delete_product( $product_id );

		$order = wc_get_order( $order_id );
		$this->assertContains( $product_name, $compat->get_product_name_from_order( $product_id, $order ) );
	}
}
