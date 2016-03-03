<?php

class WP_Test_WC_Connect_Loader extends WC_Unit_Test_Case {

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Loader' ) );

	}

}
