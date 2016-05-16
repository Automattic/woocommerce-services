<?php

class WP_Test_WC_Connect_Shipping_Method extends WP_UnitTestCase {

	static function setUpBeforeClass()	{
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-shipping-method.php' );
	}

	public function is_valid_package_destination_provider() {

		return array(
			'empty city' => array( array(
				'destination' => array(
					'city'     => '',
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => '84068',
				)
			), true ),
			'empty country' => array( array(
				'destination' => array(
					'city'     => 'Park City',
					'country'  => '',
					'state'    => 'UT',
					'postcode' => '84068',
				)
			), false ),
			'empty state' => array( array(
				'destination' => array(
					'city'     => 'Park City',
					'country'  => 'US',
					'state'    => '',
					'postcode' => '84068',
				)
			), false ),
			'invalid empty postcode' => array( array(
				'destination' => array(
					'city'     => 'Park City',
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => '',
				)
			), false ),
			'invalid postcode' => array( array(
				'destination' => array(
					'city'     => 'Park City',
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => 'EC4V 6JA',
				)
			), false ),
			'invalid state' => array( array(
				'destination' => array(
					'city'     => 'Park City',
					'country'  => 'US',
					'state'    => 'XX',
					'postcode' => '84068',
				)
			), false ),
			'valid destination with postcode' => array( array(
				'destination' => array(
					'city'     => 'Park City',
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => '84068',
				)
			), true ),
			'valid destination without postcode' => array( array(
				'destination' => array(
					'city'     => 'Kowloon City',
					'country'  => 'HK',
					'state'    => 'KOWLOON',
					'postcode' => '',
				)
			), true ),
			'valid destination without state or postcode' => array( array(
				'destination' => array(
					'city'     => 'Oranjestad',
					'country'  => 'AW',
					'state'    => '',
					'postcode' => '',
				)
			), true ),
		);

	}

	/**
	 * @dataProvider is_valid_package_destination_provider
	 * @covers WC_Connect_Shipping_Method::is_valid_package_destination
	 */
	public function test_is_valid_package_destination( $package, $expected ) {

		$shipping_method = $this->getMockBuilder( 'WC_Connect_Shipping_Method' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->assertEquals( $expected, $shipping_method->is_valid_package_destination( $package ) );

	}

	/**
	 * @covers WC_Connect_Shipping_Method::calculate_shipping
	 */
	public function test_invalid_destination_calculate_shipping() {

		$shipping_method = $this->getMockBuilder( 'WC_Connect_Shipping_Method' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_valid_package_destination', 'log' ) )
			->getMock();

		$shipping_method->expects( $this->any() )
			->method( 'is_valid_package_destination' )
			->will( $this->returnValue( false ) );

		$shipping_method->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'Package destination failed validation.' ),
				$this->anything()
			);

		$shipping_method->calculate_shipping();

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

}
