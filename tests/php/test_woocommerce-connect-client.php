<?php

class WP_Test_WC_Connect_Loader extends WC_Unit_Test_Case {

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Loader' ) );

	}

	public function test_init_hook_attached_in_constructor() {

		$loader   = new WC_Connect_Loader();
		$attached = has_action( 'woocommerce_init', array( $loader, 'init' ) );

		$this->assertNotFalse( $attached, 'WC_Connect_Loader::init() not attached to `woocommerce_init`.' );

	}

}
