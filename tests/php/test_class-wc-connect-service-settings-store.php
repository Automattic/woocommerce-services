<?php

class WP_Test_WC_Connect_Service_Settings_Store extends WC_Unit_Test_Case {

	protected $settings_store;

	protected $order_id = 123;

	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-settings-store.php' );
	}

	public function setUp() {
		$this->settings_store = $this->getMockBuilder( 'WC_Connect_Service_Settings_Store' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();
	}

	public function tearDown() {
		delete_post_meta( $this->order_id, 'wc_connect_labels' );

		// release the test store instance
		unset( $this->settings_store );
	}

	public function test_get_label_order_meta_data_regular_json() {
		$labels_data = '[{"label_id":143,"tracking":"9405536897846106800337","refundable_amount":5.95,"created":1492165890165,"carrier_id":"usps","service_name":"USPS - Priority Mail","package_name":"box","product_names":["product"]}]';
		update_post_meta( $this->order_id, 'wc_connect_labels', $labels_data );

		$expected = array(
			array(
				'label_id'          => 143,
				'tracking'          => '9405536897846106800337',
				'refundable_amount' => 5.95,
				'created'           => 1492165890165,
				'carrier_id'        => 'usps',
				'service_name'      => 'USPS - Priority Mail',
				'package_name'      => 'box',
				'product_names'     => array( 'product' )
			),
		);

		$actual = $this->settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_label_order_meta_data_escaped_json() {
		$labels_data = '[{"label_id":143,"tracking":"9405536897846106800337","refundable_amount":5.95,"created":1492165890165,"carrier_id":"usps","service_name":"USPS - Priority Mail","package_name":"Boxy \"The Box\" McBoxface","product_names":["the \"product\""]}]';
		update_post_meta( $this->order_id, 'wc_connect_labels', $labels_data );

		$expected = array(
			array(
				'label_id'          => 143,
				'tracking'          => '9405536897846106800337',
				'refundable_amount' => 5.95,
				'created'           => 1492165890165,
				'carrier_id'        => 'usps',
				'service_name'      => 'USPS - Priority Mail',
				'package_name'      => 'Boxy "The Box" McBoxface',
				'product_names'     => array( 'the "product"' )
			),
		);

		$actual = $this->settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_label_order_meta_data_unescaped_json() {
		$labels_data = '[{"label_id":143,"tracking":"9405536897846106800337","refundable_amount":5.95,"created":1492165890165,"carrier_id":"usps","service_name":"USPS - Priority Mail","package_name":"Boxy "The Box" McBoxface","product_names":["the "product""]}]';
		update_post_meta( $this->order_id, 'wc_connect_labels', $labels_data );

		$expected = array(
			array(
				'label_id'          => 143,
				'tracking'          => '9405536897846106800337',
				'refundable_amount' => 5.95,
				'created'           => 1492165890165,
				'carrier_id'        => 'usps',
				'service_name'      => 'USPS - Priority Mail',
				'package_name'      => 'Boxy "The Box" McBoxface',
				'product_names'     => array( 'the "product"' )
			),
		);

		$actual = $this->settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_label_order_meta_data_array() {
		$labels_data = array(
			array(
				'label_id'          => 143,
				'tracking'          => '9405536897846106800337',
				'refundable_amount' => 5.95,
				'created'           => 1492165890165,
				'carrier_id'        => 'usps',
				'service_name'      => 'USPS - Priority Mail',
				'package_name'      => 'Boxy "The Box" McBoxface',
				'product_names'     => array( 'the "product"' )
			),
		);
		update_post_meta( $this->order_id, 'wc_connect_labels', $labels_data );

		$expected = array(
			array(
				'label_id'          => 143,
				'tracking'          => '9405536897846106800337',
				'refundable_amount' => 5.95,
				'created'           => 1492165890165,
				'carrier_id'        => 'usps',
				'service_name'      => 'USPS - Priority Mail',
				'package_name'      => 'Boxy "The Box" McBoxface',
				'product_names'     => array( 'the "product"' )
			),
		);

		$actual = $this->settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}

	public function test_get_label_order_meta_data_not_set() {
		$expected = array();

		$actual = $this->settings_store->get_label_order_meta_data( $this->order_id );

		$this->assertEquals( $actual, $expected );
	}
}