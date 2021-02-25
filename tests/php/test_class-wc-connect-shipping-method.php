<?php

class WP_Test_WC_Connect_Shipping_Method extends WP_UnitTestCase {

	static function setUpBeforeClass()	{
		require_once __DIR__ . '/../../classes/class-wc-connect-shipping-method.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-cart-validation.php';
	}

	public function is_valid_package_destination_provider() {

		return array(
			'empty city'                                  => array(
				array(
					'destination' => array(
						'city'     => '',
						'country'  => 'US',
						'state'    => 'UT',
						'postcode' => '84068',
					),
				),
				true,
			),
			'empty country'                               => array(
				array(
					'destination' => array(
						'city'     => 'Park City',
						'country'  => '',
						'state'    => 'UT',
						'postcode' => '84068',
					),
				),
				false,
			),
			'empty state'                                 => array(
				array(
					'destination' => array(
						'city'     => 'Park City',
						'country'  => 'US',
						'state'    => '',
						'postcode' => '84068',
					),
				),
				false,
			),
			'invalid empty postcode'                      => array(
				array(
					'destination' => array(
						'city'     => 'Park City',
						'country'  => 'US',
						'state'    => 'UT',
						'postcode' => '',
					),
				),
				false,
			),
			'invalid postcode'                            => array(
				array(
					'destination' => array(
						'city'     => 'Park City',
						'country'  => 'US',
						'state'    => 'UT',
						'postcode' => 'EC4V 6JA',
					),
				),
				false,
			),
			'invalid state'                               => array(
				array(
					'destination' => array(
						'city'     => 'Park City',
						'country'  => 'US',
						'state'    => 'XX',
						'postcode' => '84068',
					),
				),
				false,
			),
			'valid destination with postcode'             => array(
				array(
					'destination' => array(
						'city'     => 'Park City',
						'country'  => 'US',
						'state'    => 'UT',
						'postcode' => '84068',
					),
				),
				true,
			),
			'valid destination without postcode'          => array(
				array(
					'destination' => array(
						'city'     => 'Kowloon City',
						'country'  => 'HK',
						'state'    => 'KOWLOON',
						'postcode' => '',
					),
				),
				true,
			),
			'valid destination without state or postcode' => array(
				array(
					'destination' => array(
						'city'     => 'Oranjestad',
						'country'  => 'AW',
						'state'    => '',
						'postcode' => '',
					),
				),
				true,
			),
		);

	}

	/**
	 * @dataProvider is_valid_package_destination_provider
	 * @covers WC_Connect_Shipping_Method::is_valid_package_destination
	 */
	public function test_is_valid_package_destination( $package, $expected ) {

		$shipping_method = new WC_Connect_Shipping_Method();

		$this->assertEquals( $expected, $shipping_method->is_valid_package_destination( $package ) );

	}

	/**
	 * @covers WC_Connect_Shipping_Method::calculate_shipping
	 */
	public function test_invalid_service_settings_calculate_shipping() {

		$shipping_method = $this->getMockBuilder( 'WC_Connect_Shipping_Method' )
			->setConstructorArgs( array( 1 ) )
			->setMethods( array( 'is_valid_package_destination', 'get_service_settings', 'log' ) )
			->getMock();

		$shipping_method->expects( $this->any() )
			->method( 'is_valid_package_destination' )
			->will( $this->returnValue( true ) );

		$shipping_method->expects( $this->any() )
			->method( 'get_service_settings' )
			->will( $this->returnValue( new stdClass() ) );

		$shipping_method->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'Service settings empty.' ),
				$this->anything()
			);

		$shipping_method->calculate_shipping();

	}

	/**
	 * Creates a package with mocked products, based on an array of shipping classes.
	 *
	 * @param int $classes[] An array of shipping class IDs.
	 * @return array A fake package.
	 */
	public function mock_package_for_shipping_classes( $classes = array() ) {
		$contents = array();

		foreach ( $classes as $shipping_class ) {
			$data_object = $this->getMockBuilder( 'WC_Product' )
				->setConstructorArgs( array( null ) )
				->setMethods( array( 'get_shipping_class_id' ) )
				->getMock();

			$data_object->expects( $this->any() )
				->method( 'get_shipping_class_id' )
				->will( $this->returnValue( $shipping_class ) );

			$contents[] = array(
				'data' => $data_object,
			);
		}

		return array(
			'contents' => $contents,
		);
	}

	/**
	 * Creates a new shipping class with certain shipping classes enabled.
	 *
	 * @param  int $classes An array of class IDs (optional).
	 * @return WC_Connect_Shipping_Method
	 */
	public function create_with_shipping_classes( $classes = array() ) {
		$shipping_method = $this->getMockBuilder( 'WC_Connect_Shipping_Method' )
			->setConstructorArgs( array( 123 ) )
			->setMethods( array( 'get_service_settings' ) )
			->getMock();

		$service_settings = (object) array(
			'shipping_classes' => $classes,
		);

		$shipping_method->expects( $this->any() )
			->method( 'get_service_settings' )
			->will( $this->returnValue( $service_settings ) );

		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->getMock();
		$logger->enable_debug();
		$shipping_method->set_logger( $logger );

		return $shipping_method;
	}

	/**
	 * @covers WC_Connect_Shipping_Method::are_shipping_classes_supported
	 */
	public function test_shipping_classes() {

		define( 'BOOK', 11 );
		define( 'CLOTHING', 23 );
		define( 'OTHERS', 37 );

		$empty           = $this->mock_package_for_shipping_classes();
		$fully_valid     = $this->mock_package_for_shipping_classes( array( BOOK, CLOTHING ) );
		$partially_valid = $this->mock_package_for_shipping_classes( array( BOOK, OTHERS ) );
		$fully_invalid   = $this->mock_package_for_shipping_classes( array( OTHERS, OTHERS ) );

		/**
		 * Test with entered shipping classes
		 */
		$standard_method = $this->create_with_shipping_classes( array( BOOK, CLOTHING ) );

		$this->assertTrue( $standard_method->is_available( $empty ) );
		$this->assertTrue( $standard_method->is_available( $fully_valid ) );
		$this->assertFalse( $standard_method->is_available( $partially_valid ) );
		$this->assertFalse( $standard_method->is_available( $fully_invalid ) );

		/**
		 * Test without shipping classes, the method should be always available.
		 */
		$empty_method = $this->create_with_shipping_classes();

		$this->assertTrue( $empty_method->is_available( $empty ) );
		$this->assertTrue( $empty_method->is_available( $fully_valid ) );
		$this->assertTrue( $empty_method->is_available( $partially_valid ) );
		$this->assertTrue( $empty_method->is_available( $fully_invalid ) );

	}

}
