<?php

class WP_Test_WC_Connect_Loader extends WP_UnitTestCase {

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Loader' ) );

	}

}
