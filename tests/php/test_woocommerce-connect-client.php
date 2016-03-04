<?php

class WP_Test_WC_Connect_Loader extends WC_Unit_Test_Case {

	const SHIPPING_SCRIPT_HANDLE = 'wc_connect_shipping_admin';

	public function tearDown() {

		wp_deregister_script( self::SHIPPING_SCRIPT_HANDLE );

	}

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Loader' ) );

	}

	public function test_init_hook_attached_in_constructor() {

		$loader   = new WC_Connect_Loader();
		$attached = has_action( 'woocommerce_init', array( $loader, 'init' ) );

		$this->assertNotFalse( $attached, 'WC_Connect_Loader::init() not attached to `woocommerce_init`.' );

	}

	public function enqueue_scripts_provider() {

		return array(
			'good values'     => array( 'woocommerce_page_wc-settings', 'shipping', 1, true ),
			'bad page hook'   => array( 'woocommerce_page_wc-status', 'shipping', 1, false ),
			'bad tab value'   => array( 'woocommerce_page_wc-settings', 'checkout', 1, false ),
			'bad instance id' => array( 'woocommerce_page_wc-settings', 'shipping', null, false ),
		);

	}

	/**
	 * @dataProvider enqueue_scripts_provider
	 */
	public function test_enqueue_shipping_script( $hook, $tab, $instance_id, $expected ) {

		$loader = new WC_Connect_Loader();

		$loader->enqueue_shipping_script( $hook, $tab, $instance_id );

		$this->assertEquals( $expected, wp_script_is( self::SHIPPING_SCRIPT_HANDLE, 'registered' ) );

	}

}
