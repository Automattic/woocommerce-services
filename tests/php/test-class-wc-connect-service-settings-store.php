<?php

class WP_Test_WC_Connect_Service_Settings_Store extends WC_Unit_Test_Case {

	protected $labels_data = array(
		array(
			'label_id'          => 143,
			'tracking'          => '9405536897846106800337',
			'refundable_amount' => 5.95,
			'created'           => 1492165890165,
			'carrier_id'        => 'usps',
			'service_name'      => 'USPS - Priority Mail',
			'package_name'      => 'Boxy "The Box" McBoxface',
			'product_names'     => array( 'the "product"' ),
		),
	);

	protected $multiple_labels_data = array(
		array(
			'label_id'          => 143,
			'tracking'          => '9405536897846106800337',
			'refundable_amount' => 5.95,
			'created'           => 1492165890165,
			'carrier_id'        => 'usps',
			'service_name'      => 'USPS - Priority Mail',
			'package_name'      => 'Boxy "The Box" McBoxface',
			'product_names'     => array( 'the "product"' ),
		),
		array(
			'label_id'          => 144,
			'tracking'          => '9405536897846106800338',
			'refundable_amount' => 7.95,
			'created'           => 1492165890165,
			'carrier_id'        => 'usps',
			'service_name'      => 'USPS - Priority Mail',
			'package_name'      => '"Boxinator 5000"',
			'product_names'     => array( 'the "product"', 'another "product"' ),
		),
	);

	protected $order_id;

	public static function set_up_before_class() {
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-settings-store.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client-live.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-schemas-store.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-logger.php';
	}

	private function get_settings_store( $service_schemas_store = false, $api_client = false, $logger = false ) {
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

		if ( ! $logger ) {
			$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
				->disableOriginalConstructor()
				->setMethods( null )
				->getMock();
		}

		return new WC_Connect_Service_Settings_Store( $service_schemas_store, $api_client, $logger );
	}

	public function set_up() {
		$order = wc_create_order();
		$this->order_id = $order->get_id();
	}

	public function tear_down() {
		$order = wc_get_order( $this->order_id );
		$order->delete_meta_data( 'wc_connect_labels' );
		$order->save();
	}

	public function test_get_label_order_meta_data_regular_json() {
		$labels_data                     = $this->labels_data;
		$labels_data[0]['package_name']  = 'box';
		$labels_data[0]['product_names'] = array( 'product' );
		$order                           = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', json_encode( $labels_data ) );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $labels_data );
	}

	public function test_get_label_order_meta_data_escaped_json() {
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', json_encode( $this->labels_data ) );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->labels_data );
	}

	public function test_get_label_order_meta_data_unescaped_json() {
		// create a json and ensure that quotes are unescaped
		$json = json_encode( $this->labels_data );
		$json = str_replace( '\"', '"', $json );

		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', $json );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->labels_data );
	}

	public function test_get_label_order_meta_data_array() {
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', $this->labels_data );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->labels_data );
	}

	public function test_get_label_order_meta_data_not_set() {
		$expected = array();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_label_order_meta_data_multiple_labels_regular_json() {
		$labels_data                     = $this->multiple_labels_data;
		$labels_data[0]['package_name']  = 'box';
		$labels_data[0]['product_names'] = array( 'product' );
		$order                           = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', json_encode( $labels_data ) );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $labels_data );
	}

	public function test_get_label_order_meta_data_multiple_labels_escaped_json() {
		$json  = json_encode( $this->multiple_labels_data );
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', $json );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->multiple_labels_data );
	}

	public function test_get_label_order_meta_data_multiple_labels_unescaped_json() {
		// create a json and ensure that quotes are unescaped
		$json  = json_encode( $this->multiple_labels_data );
		$json  = str_replace( '\"', '"', $json );
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', $json );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->multiple_labels_data );
	}

	public function test_get_label_order_meta_data_multiple_labels_array() {
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', $this->multiple_labels_data );
		$order->save();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->multiple_labels_data );
	}

	public function test_get_label_order_meta_data_invalid_string() {
		$order = wc_get_order( $this->order_id );
		$order->update_meta_data( 'wc_connect_labels', 'this"is"not}a{json}' );
		$order->save();

		$expected = array();

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	/**
	 * Test that creating custom packages extends the existing custom packages in the settings store.
	 */
	public function test_create_packages_extends_existing_packages() {
		// Given.
		$settings_store    = $this->get_settings_store();
		$package_1         = array(
			'is_user_defined'  => true,
			'name'             => 'Fun box',
			'inner_dimensions' => '10 x 20 x 5',
			'box_weight'       => 0.23,
			'max_weight'       => 0,
		);
		$package_2         = array(
			'is_user_defined'  => true,
			'name'             => 'Fun envelope',
			'inner_dimensions' => '12 x 16 x 11',
			'box_weight'       => 0.5,
			'max_weight'       => 0,
		);
		$existing_packages = array( $package_1 );
		$new_packages      = array( $package_2 );
		$settings_store->update_packages( $existing_packages );

		// When.
		$settings_store->create_packages( $new_packages );

		// Then.
		$actual   = $settings_store->get_packages();
		$expected = array( $package_1, $package_2 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Test that creating predefined packages extends the existing predefined packages in the settings store.
	 */
	public function test_create_predefined_packages_extends_existing_packages() {
		// Given.
		$settings_store               = $this->get_settings_store();
		$existing_predefined_packages = array(
			'usps' => array(
				'flat_envelope',
				'padded_flat_envelope',
			),
		);
		$new_predefined_packages      = array(
			'dhlexpress' => array(
				'SmallPaddedPouch',
				'Box2Cube',
			),
			'usps'       => array(
				'legal_flat_envelope',
			),
		);
		$settings_store->update_predefined_packages( $existing_predefined_packages );

		// When.
		$settings_store->create_predefined_packages( $new_predefined_packages );

		// Then.
		$actual   = $settings_store->get_predefined_packages();
		$expected = array(
			'usps'       => array(
				'flat_envelope',
				'padded_flat_envelope',
				'legal_flat_envelope',
			),
			'dhlexpress' => array(
				'SmallPaddedPouch',
				'Box2Cube',
			),
		);

		$this->assertEquals( $expected, $actual );
	}
}
