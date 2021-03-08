<?php

class WP_Test_WC_Connect_Shipping_Label extends WC_Unit_Test_Case {

	protected $wc_connect_packages = array(
		array(
			'id'         => 'weight_0_.',
			'box_id'     => '"',
			'length'     => 10,
			'width'      => 10,
			'height'     => 10,
			'weight'     => 0.25625,
			'items'      => array(
				array(
					'product_id' => 128,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
				),
			),
			'service_id' => 'pri',
		),
	);

	protected $wc_connect_packages_multiple = array(
		array(
			'id'         => 'weight_0_.',
			'box_id'     => '"',
			'length'     => 10,
			'width'      => 10,
			'height'     => 10,
			'weight'     => 0.25625,
			'items'      => array(
				array(
					'product_id' => 128,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
				),
			),
			'service_id' => 'pri',
		),
		array(
			'id'         => 'weight_1_.',
			'box_id'     => '"',
			'length'     => 10,
			'width'      => 10,
			'height'     => 10,
			'weight'     => 0.25625,
			'items'      => array(
				array(
					'product_id' => 128,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
				),
				array(
					'product_id' => 129,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
				),
			),
			'service_id' => 'pri',
		),
	);

	protected $expected_selected_packages = array(
		'weight_0_.' => array(
			'id'         => 'weight_0_.',
			'box_id'     => '"',
			'length'     => 10,
			'width'      => 10,
			'height'     => 10,
			'weight'     => 0.25625,
			'items'      => array(
				array(
					'product_id' => 128,
					'value'      => 0,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
					'name'       => '#128 - [Deleted product]',
				),
			),
			'service_id' => 'pri',
		),
	);

	protected $expected_selected_packages_multiple = array(
		'weight_0_.' => array(
			'id'         => 'weight_0_.',
			'box_id'     => '"',
			'length'     => 10,
			'width'      => 10,
			'height'     => 10,
			'weight'     => 0.25625,
			'items'      => array(
				array(
					'product_id' => 128,
					'value'      => 0,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
					'name'       => '#128 - [Deleted product]',
				),
			),
			'service_id' => 'pri',
		),
		'weight_1_.' => array(
			'id'         => 'weight_1_.',
			'box_id'     => '"',
			'length'     => 10,
			'width'      => 10,
			'height'     => 10,
			'weight'     => 0.25625,
			'items'      => array(
				array(
					'product_id' => 128,
					'value'      => 0,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
					'name'       => '#128 - [Deleted product]',
				),
				array(
					'product_id' => 129,
					'value'      => 0,
					'length'     => 3,
					'width'      => 3,
					'height'     => 2.5,
					'weight'     => 0.15625,
					'quantity'   => 1,
					'name'       => '#129 - [Deleted product]',
				),
			),
			'service_id' => 'pri',
		),
	);

	public static function setupBeforeClass() {
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-compatibility.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-shipping-label.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-settings-store.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client-live.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-schemas-store.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-payment-methods-store.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-account-settings.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-package-settings.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-continents.php';

		WC_Connect_Compatibility::set_version( '3.0.0' );
	}

	private function get_shipping_label( $api_client = false, $settings_store = false, $service_schemas_store = false, $payment_methods_store = false ) {
		if ( ! $settings_store ) {
			$settings_store = $this->getMockBuilder( 'WC_Connect_Service_Settings_Store' )
				 ->disableOriginalConstructor()
				 ->setMethods( null )
				 ->getMock();
		}

		if ( ! $api_client ) {
			$api_client = $this->getMockBuilder( 'WC_Connect_API_Client_Live' )
				->disableOriginalConstructor()
				->setMethods( null )
				->getMock();
		}

		if ( ! $service_schemas_store ) {
			$service_schemas_store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
				->disableOriginalConstructor()
				->setMethods( null )
				->getMock();
		}

		if ( ! $payment_methods_store ) {
			$payment_methods_store = $this->getMockBuilder( 'WC_Connect_Payment_Methods_Store' )
				->disableOriginalConstructor()
				->setMethods( null )
				->getMock();
		}

		return new WC_Connect_Shipping_Label( $api_client, $settings_store, $service_schemas_store, $payment_methods_store );
	}

	private function create_mock_order() {
		$mock_order = $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_shipping_methods', 'get_items' ) )
			->getMock();

		$mock_order->expects( $this->any() )->method( 'get_items' )->will( $this->returnValue( array() ) );

		return $mock_order;
	}

	public function test_get_selected_rates_regular_json() {
		$json = json_encode( $this->wc_connect_packages );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_unescaped_json() {
		// create a json and ensure that quotes are unescaped
		$json = json_encode( $this->wc_connect_packages );
		$json = str_replace( '"box_id":"\""', '"box_id":"""', $json );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_serialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => maybe_serialize( $this->wc_connect_packages ),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_unserialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => $this->wc_connect_packages,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_packages_regular_json() {
		$json = json_encode( $this->wc_connect_packages );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages );
	}

	public function test_get_selected_packages_unescaped_json() {
		// create a json and ensure that quotes are unescaped
		$json = json_encode( $this->wc_connect_packages );
		$json = str_replace( '"box_id":"\""', '"box_id":"""', $json );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages );
	}

	public function test_get_selected_packages_serialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => maybe_serialize( $this->wc_connect_packages ),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages );
	}

	public function test_get_selected_packages_unserialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => $this->wc_connect_packages,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages );
	}

	// WC 3.1.0 shipping rate meta_data save bug
	// See: https://github.com/Automattic/woocommerce-services/issues/1075
	public function test_get_selected_packages_null_in_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => array( null ), // Bug causes data to be `a:1:{i:0;N;}` in database
			),
		);

		$expected = array(
			'default_box' => array(
				'id'     => 'default_box',
				'box_id' => 'not_selected',
				'height' => 0,
				'length' => 0,
				'weight' => 0,
				'width'  => 0,
				'items'  => array(),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_multiple_packages_regular_json() {
		$json = json_encode( $this->wc_connect_packages_multiple );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
			'weight_1_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_multiple_packages_unescaped_json() {
		// create a json and ensure that quotes are unescaped
		$json = json_encode( $this->wc_connect_packages_multiple );
		$json = str_replace( '"box_id":"\""', '"box_id":"""', $json );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
			'weight_1_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_multiple_packages_serialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => maybe_serialize( $this->wc_connect_packages_multiple ),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
			'weight_1_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_multiple_packages_unserialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => $this->wc_connect_packages_multiple,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => 'pri',
			'weight_1_.' => 'pri',
		);

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_packages_multiple_packages_regular_json() {
		$json = json_encode( $this->wc_connect_packages_multiple );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages_multiple );
	}

	public function test_get_selected_packages_multiple_packages_unescaped_json() {
		// create a json and ensure that quotes are unescaped
		$json = json_encode( $this->wc_connect_packages_multiple );
		$json = str_replace( '"box_id":"\""', '"box_id":"""', $json );

		$shipping_method = array(
			array(
				'wc_connect_packages' => $json,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages_multiple );
	}

	public function test_get_selected_packages_multiple_packages_serialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => maybe_serialize( $this->wc_connect_packages_multiple ),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages_multiple );
	}

	public function test_get_selected_packages_multiple_packages_unserialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => $this->wc_connect_packages_multiple,
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$shipping_label = $this->get_shipping_label();
		$actual         = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages_multiple );
	}

	/**
	 * Test that an order with one virtual product is not eligible for shipping label creation (shipping is not required).
	 */
	public function test_order_with_a_virtual_product_is_not_eligible_for_shipping_label_creation() {
		// Given.
		$product = $this->create_simple_product( true );
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$shipping_label = $this->get_shipping_label();
		$is_eligible    = $shipping_label->is_order_eligible_for_shipping_label_creation( $order );

		// Then.
		$this->assertFalse( $is_eligible );
	}

	/**
	 * Test that an order with one shippable product is eligible for shipping label creation.
	 */
	public function test_order_with_a_shippable_product_is_eligible_for_shipping_label_creation() {
		// Given.
		$product = $this->create_simple_product( false );
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$shipping_label = $this->get_shipping_label();
		$is_eligible    = $shipping_label->is_order_eligible_for_shipping_label_creation( $order );

		// Then.
		$this->assertTrue( $is_eligible );
	}

	/**
	 * Test that an order with one fully refunded shippable product is not eligible for shipping label creation (no product to ship).
	 */
	public function test_order_with_a_fully_refunded_shippable_product_is_not_eligible_for_shipping_label_creation() {
		// Given.
		$product = $this->create_simple_product( false );
		$order   = WC_Helper_Order::create_order( 1, $product );

		$order_items       = $order->get_items();
		$refund_line_items = array();
		foreach ( $order_items as $order_item ) {
			$refund_line_items[ $order_item->get_id() ] = array(
				'qty'          => $order_item->get_quantity(),
				'refund_total' => 20,
			);
		}
		$refund_args = array(
			'order_id'   => $order->get_id(),
			'line_items' => $refund_line_items,
		);
		wc_create_refund( $refund_args );

		// When.
		$shipping_label = $this->get_shipping_label();
		$is_eligible    = $shipping_label->is_order_eligible_for_shipping_label_creation( $order );

		// Then.
		$this->assertFalse( $is_eligible );
	}

	/**
	 * Test that an order with one partially refunded shippable product is eligible for shipping label creation (still product to ship).
	 */
	public function test_order_with_a_partially_refunded_shippable_product_is_eligible_for_shipping_label_creation() {
		// Given.
		$product = $this->create_simple_product( false );
		$order   = WC_Helper_Order::create_order( 1, $product );

		$order_items       = $order->get_items();
		$refund_line_items = array();
		foreach ( $order_items as $order_item ) {
			$refund_line_items[ $order_item->get_id() ] = array(
				'qty'          => $order_item->get_quantity() - 1,
				'refund_total' => 20,
			);
		}
		$refund_args = array(
			'order_id'   => $order->get_id(),
			'line_items' => $refund_line_items,
		);
		wc_create_refund( $refund_args );

		// When.
		$shipping_label = $this->get_shipping_label();
		$is_eligible    = $shipping_label->is_order_eligible_for_shipping_label_creation( $order );

		// Then.
		$this->assertTrue( $is_eligible );
	}

	/**
	 * Test that an order with one shippable product but already included in a pre-existing label is not eligible for shipping label creation
	 * (no need to ship again).
	 */
	public function test_order_with_a_shippable_product_already_in_a_label_is_not_eligible_for_shipping_label_creation() {
		// Given.
		$product = $this->create_simple_product( false );
		$order   = WC_Helper_Order::create_order( 1, $product );

		// Add a shipping label with all the products in the order.
		$order_items = $order->get_items();
		$product_ids = array();
		foreach ( $order_items as $order_item ) {
			foreach ( range( 1, $order_item->get_quantity() ) as $index ) {
				$product = WC_Connect_Compatibility::instance()->get_item_product( $order, $order_item );
				array_push( $product_ids, $product->get_id() );
			}
		}
		$settings_store = $this->getMockBuilder( 'WC_Connect_Service_Settings_Store' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();
		$this->add_shipping_label_to_order( $settings_store, $order, $product_ids );

		// When.
		$shipping_label = $this->get_shipping_label( false, $settings_store );
		$is_eligible    = $shipping_label->is_order_eligible_for_shipping_label_creation( $order );

		// Then.
		$this->assertFalse( $is_eligible );
	}

	/**
	 * A helper to create a simple product.
	 *
	 * @param bool $virtual Whether the simple product is virtual or not.
	 * @return WC_Product_Simple
	 */
	private function create_simple_product( $virtual = false ): WC_Product_Simple {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( $virtual );
		$product->save();
		return $product;
	}

	/**
	 * A helper to add a shipping label to an order.
	 *
	 * @param WC_Connect_Service_Settings_Store $settings_store Settings store that coordinates the shipping labels.
	 * @param WC_Order                          $order The order for the shipping label to be added to.
	 * @param array                             $product_ids A list of product IDs that the shipping label contains.
	 */
	private function add_shipping_label_to_order( $settings_store, $order, $product_ids = array() ) {
		$labels_meta = array(
			array(
				'label_id'    => 17,
				'product_ids' => $product_ids,
			),
		);
		$settings_store->add_labels_to_order( $order->get_id(), $labels_meta );
	}
}
