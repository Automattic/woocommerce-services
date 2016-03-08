<?php

class WP_Test_WC_Connect_Services_Validator extends WC_Unit_Test_Case {

	private static function get_golden_services() {

		$usps = new stdClass();


		$services = new stdClass();
		$services->shipping = array(
			$usps
		);


	}

	public function tearDown() {
	}

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Services_Validator' ) );

	}

	public function test_services_not_an_object_fails() {

		$validator = new WC_Connect_Services_Validator();

		$this->assertIsWPError(
			$validator->validate_services(
				array()
			)
		);

	}

	public function test_service_type_referencing_a_non_array_fails() {

		$validator = new WC_Connect_Services_Validator();

		$this->assertIsWPError(
			$validator->validate_services(
				array(
					'shipping' => new stdClass()
				)
			)
		);

	}

	public function test_service_referencing_a_non_object_fails() {

		$validator = new WC_Connect_Services_Validator();

		$this->assertIsWPError(
			$validator->validate_services(
				array(
					'shipping' => array(
						'usps' => array()
					)
				)
			)
		);

	}

	public function test_service_missing_id_property_fails() {

		$validator = new WC_Connect_Services_Validator();
		$service = new stdClass();

		$this->assertIsWPError(
			$validator->validate_services(
				array(
					'shipping' => array(
						'usps' => $service
					)
				)
			)
		);

	}

	public function test_service_missing_method_description_property_fails() {

		$validator = new WC_Connect_Services_Validator();
		$service = new stdClass();
		$service->id = 'usps';
		$service->

		$this->assertIsWPError(
			$validator->validate_services(
				array(
					'shipping' => array(
						'usps' => $service
					)
				)
			)
		);

	}

	public function test_service_missing_method_description_property_fails() {

		$validator = new WC_Connect_Services_Validator();
		$service = new stdClass();
		$service->id = 'usps';

		$this->assertIsWPError(
			$validator->validate_services(
				array(
					'shipping' => array(
						'usps' => $service
					)
				)
			)
		);

	}}
