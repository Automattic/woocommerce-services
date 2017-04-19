<?php

class WP_Test_WC_Connect_Shipping_Label extends WC_Unit_Test_Case {
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
		return $this->getMockBuilder( 'WC_Order' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_shipping_methods' ) )
			->getMock();
	}

	public function test_get_selected_rates_regular_json() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => '[{"id":"weight_0_.","box_id":"box","length":10,"width":10,"height":10,"weight":0.25625,"items":[{"product_id":128,"length":3,"width":3,"height":2.5,"weight":0.15625,"quantity":1}],"service_id":"pri"}]',
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
		$shipping_method = array(
			array(
				'wc_connect_packages' => '[{"id":"weight_0_.","box_id":""","length":10,"width":10,"height":10,"weight":0.25625,"items":[{"product_id":128,"length":3,"width":3,"height":2.5,"weight":0.15625,"quantity":1}],"service_id":"pri"}]',
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
				'wc_connect_packages' => maybe_serialize( array( array(
					'id'         => 'weight_0_.',
					'service_id' => 'pri'
				) ) ),
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
				'wc_connect_packages' => maybe_serialize( array( array(
					'id'         => 'weight_0_.',
					'service_id' => 'pri'
				) ) ),
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
		$shipping_method = array(
			array(
				'wc_connect_packages' => '[{"id":"weight_0_.","box_id":"box","length":10,"width":10,"height":10,"weight":0.25625,"items":[{"product_id":128,"length":3,"width":3,"height":2.5,"weight":0.15625,"quantity":1}],"service_id":"pri"}]',
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
			'weight_0_.' => array(
				'id' => 'weight_0_.',
				'box_id' => 'box',
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
				) ),
				'service_id' => 'pri',
			),
		);

		$shipping_label = $this->get_shipping_label();
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_packages_unescaped_json() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => '[{"id":"weight_0_.","box_id":""","length":10,"width":10,"height":10,"weight":0.25625,"items":[{"product_id":128,"length":3,"width":3,"height":2.5,"weight":0.15625,"quantity":1}],"service_id":"pri"}]',
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
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
				) ),
				'service_id' => 'pri',
			),
		);

		$shipping_label = $this->get_shipping_label();
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_packages_serialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => maybe_serialize(
					array(
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
					)
				),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
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
				) ),
				'service_id' => 'pri',
			),
		);

		$shipping_label = $this->get_shipping_label();
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $expected );
	}

	public function test_get_selected_packages_unserialized_array() {
		$shipping_method = array(
			array(
				'wc_connect_packages' => array(
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
				),
			),
		);

		$mock_order = $this->create_mock_order();
		$mock_order->expects( $this->any() )->method( 'get_shipping_methods' )->will( $this->returnValue( $shipping_method ) );

		$expected = array(
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
				) ),
				'service_id' => 'pri',
			),
		);

		$shipping_label = $this->get_shipping_label();
		$actual = $shipping_label->get_selected_packages( $mock_order );
		$this->assertEquals( $actual, $expected );
	}
}
