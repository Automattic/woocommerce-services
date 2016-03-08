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

	public function get_posted_form_settings_provider() {

		$posted_data = array(
			'field_1'          => 'test',
			'field_2'          => 'on',
			'_wp_http_referer' => '/wp-admin/admin.php?page=wc-settings&tab=shipping&instance_id=1',
			'_wpnonce'         => '1q2w3e4r5t',
		);

		$bad_schema = (object) array();
		$expected_bad_schema = array();

		$good_schema = (object) array(
			'properties' => (object) array(
				'field_1' => (object) array(
					'type'  => 'string',
					'title' => 'Field 1',
				),
				'field_2' => (object) array(
					'type'  => 'boolean',
					'title' => 'Field 2',
				),
			)
		);

		$expected_good_schema = array(
			'field_1' => 'test',
			'field_2' => true,
		);

		return array(
			'bad schema' => array( array(), $posted_data, $bad_schema, $expected_bad_schema ),
			'good schema, passed in data' => array( array(), $posted_data, $good_schema, $expected_good_schema ),
			'good schema, data from POST' => array( $posted_data, null, $good_schema, $expected_good_schema ),
		);

	}

	/**
	 * @dataProvider get_posted_form_settings_provider
	 * @covers WC_Connect_Shipping_Method::get_posted_form_settings
	 */
	public function test_get_posted_form_settings( $post_data, $passed_in, $schema, $expected ) {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$shipping_method = $this->getMockBuilder( 'WC_Connect_Shipping_Method' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_form_schema' ) )
			->getMock();

		$shipping_method->expects( $this->any() )
			->method( 'get_form_schema' )
			->will( $this->returnValue( $schema ) );

		$_POST = $post_data;

		$this->assertEquals( $expected, $shipping_method->get_posted_form_settings( $passed_in ) );

	}

}