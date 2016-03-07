<?php

class WP_Test_WC_Connect_Shipping_Method extends WP_UnitTestCase {

	public function is_valid_package_destination_provider() {

		return array(
			'empty country' => array( array(
				'destination' => array(
					'country'  => '',
					'state'    => 'UT',
					'postcode' => '84068'
				)
			), false ),
			'empty state' => array( array(
				'destination' => array(
					'country'  => 'US',
					'state'    => '',
					'postcode' => '84068'
				)
			), false ),
			'invalid empty postcode' => array( array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => ''
				)
			), false ),
			'invalid postcode' => array( array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => 'EC4V 6JA'
				)
			), false ),
			'invalid state' => array( array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'XX',
					'postcode' => '84068'
				)
			), false ),
			'valid destination with postcode' => array( array(
				'destination' => array(
					'country'  => 'US',
					'state'    => 'UT',
					'postcode' => '84068'
				)
			), true ),
			'valid destination without postcode' => array( array(
				'destination' => array(
					'country'  => 'HK',
					'state'    => 'KOWLOON',
					'postcode' => ''
				)
			), true ),
			'valid destination without state or postcode' => array( array(
				'destination' => array(
					'country'  => 'AW',
					'state'    => '',
					'postcode' => ''
				)
			), true ),
		);

	}

	/**
	 * @dataProvider is_valid_package_destination_provider
	 * @covers WC_Connect_Shipping_Method::is_valid_package_destination
	 */
	public function test_is_valid_package_destination( $package, $expected ) {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$shipping_method = $this->getMockBuilder( 'WC_Connect_Shipping_Method' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$this->assertEquals( $expected, $shipping_method->is_valid_package_destination( $package ) );

	}

}