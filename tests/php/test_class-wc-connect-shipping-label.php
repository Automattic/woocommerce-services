<?php

class WP_Test_WC_Connect_Shipping_Label extends WC_Unit_Test_Case {

	protected $wc_connect_packages = array(
		array(
			'id' => 'weight_0_.',
			'box_id' => '"',
			'length' => 10,
			'width' => 10,
			'height' => 10,
			'weight' => 0.25625,
			'items' => array(
				array (
					'product_id' => 128,
					'length' => 3,
					'width' => 3,
					'height' => 2.5,
					'weight' => 0.15625,
					'quantity' => 1,
				),
			),
			'service_id' => 'pri',
		),
	);

	protected $wc_connect_packages_multiple = array(
		array(
			'id' => 'weight_0_.',
			'box_id' => '"',
			'length' => 10,
			'width' => 10,
			'height' => 10,
			'weight' => 0.25625,
			'items' => array(
				array (
					'product_id' => 128,
					'length' => 3,
					'width' => 3,
					'height' => 2.5,
					'weight' => 0.15625,
					'quantity' => 1,
				),
			),
			'service_id' => 'pri',
		),
		array(
			'id' => 'weight_1_.',
			'box_id' => '"',
			'length' => 10,
			'width' => 10,
			'height' => 10,
			'weight' => 0.25625,
			'items' => array(
				array (
					'product_id' => 128,
					'length' => 3,
					'width' => 3,
					'height' => 2.5,
					'weight' => 0.15625,
					'quantity' => 1,
				),
				array (
					'product_id' => 129,
					'length' => 3,
					'width' => 3,
					'height' => 2.5,
					'weight' => 0.15625,
					'quantity' => 1,
				),
			),
			'service_id' => 'pri',
		),
	);

	protected $expected_selected_packages = array(
		'weight_0_.' => array(
			'id' => 'weight_0_.',
			'box_id' => '"',
			'length' => 10,
			'width' => 10,
			'height' => 10,
			'weight' => 0.25625,
			'items' => array ( array(
				'product_id' => 128,
				'length' => 3,
				'width' => 3,
				'height' => 2.5,
				'weight' => 0.15625,
				'quantity' => 1,
				'name' => '#128 - [Deleted product]',
			) ),
			'service_id' => 'pri',
		),
	);

	protected $expected_selected_packages_multiple = array(
		'weight_0_.' => array(
			'id' => 'weight_0_.',
			'box_id' => '"',
			'length' => 10,
			'width' => 10,
			'height' => 10,
			'weight' => 0.25625,
			'items' => array ( array(
				'product_id' => 128,
				'length' => 3,
				'width' => 3,
				'height' => 2.5,
				'weight' => 0.15625,
				'quantity' => 1,
				'name' => '#128 - [Deleted product]',
			) ),
			'service_id' => 'pri',
		),
		'weight_1_.' => array(
			'id' => 'weight_1_.',
			'box_id' => '"',
			'length' => 10,
			'width' => 10,
			'height' => 10,
			'weight' => 0.25625,
			'items' => array (
				array(
					'product_id' => 128,
					'length' => 3,
					'width' => 3,
					'height' => 2.5,
					'weight' => 0.15625,
					'quantity' => 1,
					'name' => '#128 - [Deleted product]',
				),
				array (
					'product_id' => 129,
					'length' => 3,
					'width' => 3,
					'height' => 2.5,
					'weight' => 0.15625,
					'quantity' => 1,
					'name' => '#129 - [Deleted product]',
				),
			),
			'service_id' => 'pri',
		),
	);

	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-compatibility.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-shipping-label.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-settings-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-schemas-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-payment-methods-store.php' );

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
			$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_unescaped_json() {
		//create a json and ensure that quotes are unescaped
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages );
	}

	public function test_get_selected_packages_unescaped_json() {
		//create a json and ensure that quotes are unescaped
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages );
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_rates_multiple_packages_unescaped_json() {
		//create a json and ensure that quotes are unescaped
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
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
		$actual = $shipping_label->get_selected_rates( $mock_order );
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages_multiple );
	}

	public function test_get_selected_packages_multiple_packages_unescaped_json() {
		//create a json and ensure that quotes are unescaped
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
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
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $this->expected_selected_packages_multiple );
	}

	public function test_get_items_as_individual_packages_multiple_products() {
		$products = array(
			WC_Helper_Product::create_simple_product(),
			WC_Helper_Product::create_simple_product(),
		);

		$order = wc_create_order( array(
			'status'        => 'processing',
			'customer_id'   => 1,
			'customer_note' => '',
			'total'         => '',
		) );

		// Add order products
		if ( version_compare( WOOCOMMERCE_VERSION, '3.0.0', '<' ) ) {
			foreach ( $products as $product ) {
				$order->add_product( $product );
			}
		} else {
			foreach ( $products as $product ) {
				$item = new WC_Order_Item_Product();

				$item->set_props( array(
					'product'  => $product,
					'quantity' => 1,
				) );

				$item->save();
				$order->add_item( $item );
			}

			$order->save();
		}

		$shipping_label      = $this->get_shipping_label();
		$individual_packages = $shipping_label->get_items_as_individual_packages( $order );

		$this->assertEquals( count( $products ), count( $individual_packages ) );
	}
}
