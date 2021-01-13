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

	protected $order_id = 123;

	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-settings-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client-live.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-schemas-store.php' );
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-logger.php' );
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

	public function tearDown() {
		delete_post_meta( $this->order_id, 'wc_connect_labels' );
	}

	public function test_get_label_order_meta_data_regular_json() {
		$labels_data = $this->labels_data;
		$labels_data[ 0 ][ 'package_name' ] = 'box';
		$labels_data[ 0 ][ 'product_names' ] = array( 'product' );
		update_post_meta( $this->order_id, 'wc_connect_labels', json_encode( $labels_data ) );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $labels_data );
	}

	public function test_get_label_order_meta_data_escaped_json() {
		update_post_meta( $this->order_id, 'wc_connect_labels', json_encode( $this->labels_data ) );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->labels_data );
	}

	public function test_get_label_order_meta_data_unescaped_json() {
		//create a json and ensure that quotes are unescaped
		$json = json_encode( $this->labels_data );
		$json = str_replace( '\"', '"', $json );
		update_post_meta( $this->order_id, 'wc_connect_labels', $json );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->labels_data );
	}

	public function test_get_label_order_meta_data_array() {
		update_post_meta( $this->order_id, 'wc_connect_labels', $this->labels_data );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->labels_data );
	}

	public function test_get_label_order_meta_data_not_set() {
		$expected = array();

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_label_order_meta_data_multiple_labels_regular_json() {
		$labels_data = $this->multiple_labels_data;
		$labels_data[ 0 ][ 'package_name' ] = 'box';
		$labels_data[ 0 ][ 'product_names' ] = array( 'product' );
		update_post_meta( $this->order_id, 'wc_connect_labels', json_encode( $labels_data ) );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $labels_data );
	}

	public function test_get_label_order_meta_data_multiple_labels_escaped_json() {
		$json = json_encode( $this->multiple_labels_data );
		update_post_meta( $this->order_id, 'wc_connect_labels', $json );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->multiple_labels_data );
	}

	public function test_get_label_order_meta_data_multiple_labels_unescaped_json() {
		//create a json and ensure that quotes are unescaped
		$json = json_encode( $this->multiple_labels_data );
		$json = str_replace( '\"', '"', $json );
		update_post_meta( $this->order_id, 'wc_connect_labels', $json );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->multiple_labels_data );
	}

	public function test_get_label_order_meta_data_multiple_labels_array() {
		update_post_meta( $this->order_id, 'wc_connect_labels', $this->multiple_labels_data );

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $this->multiple_labels_data );
	}

	public function test_get_label_order_meta_data_invalid_string() {
		update_post_meta( $this->order_id, 'wc_connect_labels', 'this"is"not}a{json}' );
		$expected = array();

		$settings_store = $this->get_settings_store();
		$actual = $settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_notification_settings() {
		$expected = array(
			'mark_order_complete_and_notify_customer' => true,
			'notify_customer_with_shipment_details'   => false,
		);

		WC_Connect_Options::update_option( 'post_print_notification_settings', $expected );

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_post_print_notification_settings();

		$this->assertEquals( $expected, $actual );
	}

	public function test_get_notification_settings_returns_defaults_if_settings_are_empty() {
		WC_Connect_Options::delete_option( 'post_print_notification_settings' );

		$expected = array(
			'mark_order_complete_and_notify_customer' => false,
			'notify_customer_with_shipment_details'   => false,
		);

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_post_print_notification_settings();

		$this->assertEquals( $expected, $actual );
	}

	public function test_get_notification_settings_fills_missing_options_with_defaults() {
		$partial_settings = array(
			'mark_order_complete_and_notify_customer' => true,
		);

		WC_Connect_Options::update_option( 'post_print_notification_settings', $partial_settings );

		$expected = array(
			'mark_order_complete_and_notify_customer' => true,
			'notify_customer_with_shipment_details'   => false,
		);

		$settings_store = $this->get_settings_store();
		$actual         = $settings_store->get_post_print_notification_settings();

		$this->assertEquals( $expected, $actual );
	}

	public function test_set_notification_setting_updates_settings() {
		WC_Connect_Options::delete_option( 'post_print_notification_settings' );

		$settings_store = $this->get_settings_store();
		$settings_store->set_post_print_notification_setting( 'mark_order_complete_and_notify_customer', true );
		$settings_store->set_post_print_notification_setting( 'notify_customer_with_shipment_details', true );

		$settings = $settings_store->get_post_print_notification_settings();

		$this->assertTrue( $settings['mark_order_complete_and_notify_customer'] );
		$this->assertTrue( $settings['notify_customer_with_shipment_details'] );
	}

	public function test_set_notification_setting_returns_error_for_invalid_setting_names() {
		$settings_store = $this->get_settings_store();
		$result         = $settings_store->set_post_print_notification_setting( 'not a valid setting name', true );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_notification_setting_name', $result->get_error_code() );
	}

	public function test_set_notification_setting_casts_values_to_bool() {
		WC_Connect_Options::delete_option( 'post_print_notification_settings' );

		$settings_store = $this->get_settings_store();
		$settings_store->set_post_print_notification_setting( 'mark_order_complete_and_notify_customer', 'foo' );
		$settings_store->set_post_print_notification_setting( 'notify_customer_with_shipment_details', '' );

		$settings = $settings_store->get_post_print_notification_settings();

		$this->assertSame( true, $settings['mark_order_complete_and_notify_customer'] );
		$this->assertSame( false, $settings['notify_customer_with_shipment_details'] );
	}

	public function test_set_notfication_setting_returns_true_when_setting_same_value_as_before() {
		$settings = array(
			'mark_order_complete_and_notify_customer' => true,
			'notify_customer_with_shipment_details'   => true,
		);
		WC_Connect_Options::update_option( 'post_print_notification_settings', $settings );

		$settings_store = $this->get_settings_store();
		$result         = $settings_store->set_post_print_notification_setting( 'mark_order_complete_and_notify_customer', true );

		$this->assertTrue( $result );
	}
}
