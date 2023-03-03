<?php

class WP_Test_WC_Connect_Service_Schemas_Validator extends WC_Unit_Test_Case {

	/**
	 * @var WC_Connect_Loader
	 */
	protected $loader;

	/**
	 * @var WC_Connect_Service_Schemas_Validator
	 */
	protected $validator;

	private static function get_golden_services() {

		return (object) array(
			'shipping' => array(
				(object) array(
					'id'                 => 'usps',
					'method_description' => 'Obtains rates dynamically from the USPS API during cart/checkout.',
					'method_title'       => 'USPS (WooCommerce Shipping)',
					'form_layout'        => array(),
					'service_settings'   => (object) array(
						'type'       => 'object',
						'required'   => array(),
						'properties' => (object) array(
							'title' => (object) array(
								'type'        => 'string',
								'title'       => 'Method Title',
								'description' => 'This controls the title which the user sees during checkout.',
								'default'     => 'USPS',
							),
						),
					),
				),
			),
			'boxes'    => new stdClass(),
		);

	}

	public function setUp() {

		parent::setUp();

		if ( ! is_a( $this->loader, 'WC_Connect_Loader' ) ) {
			$this->loader = new WC_Connect_Loader();
			$this->loader->load_dependencies();
		}

		if ( ! is_a( $this->validator, 'WC_Connect_Service_Schemas_Validator' ) ) {
			$this->validator = new WC_Connect_Service_Schemas_Validator();
		}

	}

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Service_Schemas_Validator' ) );

	}

	public function validate_services_errors_provider() {

		// service should reference an object
		$service_not_ref_object              = self::get_golden_services();
		$service_not_ref_object->shipping[0] = array();

		// service type should reference an array
		$service_type_array           = self::get_golden_services();
		$service_type_array->shipping = new stdClass();

		// service should have an id
		$service_id_required = self::get_golden_services();
		unset( $service_id_required->shipping[0]->id );

		// service id should be a string
		$service_id_string                  = self::get_golden_services();
		$service_id_string->shipping[0]->id = 99;

		// service should have method description
		$service_method_description = self::get_golden_services();
		unset( $service_method_description->shipping[0]->method_description );

		// service method description should be a string
		$service_method_string                                  = self::get_golden_services();
		$service_method_string->shipping[0]->method_description = 99;

		// service should have method title
		$service_title_required = self::get_golden_services();
		unset( $service_title_required->shipping[0]->method_title );

		// service method title should be a string
		$service_title_string                            = self::get_golden_services();
		$service_title_string->shipping[0]->method_title = 99;

		// service should have service settings
		$service_settings_required = self::get_golden_services();
		unset( $service_settings_required->shipping[0]->service_settings );

		// service should have form layout
		$form_layout_required = self::get_golden_services();
		unset( $form_layout_required->shipping[0]->form_layout );

		// service settings should have type
		$service_settings_type_required = self::get_golden_services();
		unset( $service_settings_type_required->shipping[0]->service_settings->type );

		// service settings type should be a string
		$service_settings_type_string                                      = self::get_golden_services();
		$service_settings_type_string->shipping[0]->service_settings->type = 99;

		// service settings should have required
		$service_settings_required_required = self::get_golden_services();
		unset( $service_settings_required_required->shipping[0]->service_settings->required );

		// service settings required should be an array
		$service_settings_required_array = self::get_golden_services();
		$service_settings_required_array->shipping[0]->service_settings->required = 99;

		// service settings should have properties
		$service_settings_properties_required = self::get_golden_services();
		unset( $service_settings_properties_required->shipping[0]->service_settings->properties );

		// service settings properties should be an object
		$service_settings_properties_object = self::get_golden_services();
		$service_settings_properties_object->shipping[0]->service_settings->properties = array();

		// service settings properties should have title
		$service_settings_title_required = self::get_golden_services();
		unset( $service_settings_title_required->shipping[0]->service_settings->properties->title );

		// schema should have boxes set
		$service_settings_boxes_required = self::get_golden_services();
		unset( $service_settings_boxes_required->boxes );

		return array(
			'services should be an object'                 => array( array(), 'outermost_container_not_object' ),
			'service type should reference an array'       => array( $service_type_array, 'service_type_not_ref_array' ),
			'service should reference an object'           => array( $service_not_ref_object, 'service_not_ref_object' ),
			'service should have an id'                    => array( $service_id_required, 'required_service_property_missing' ),
			'service id should be a string'                => array( $service_id_string, 'required_service_property_wrong_type' ),
			'service should have method description'       => array( $service_method_description, 'required_service_property_missing' ),
			'service method description should be a string' => array( $service_method_string, 'required_service_property_wrong_type' ),
			'service should have method title'             => array( $service_title_required, 'required_service_property_missing' ),
			'service method title should be a string'      => array( $service_title_string, 'required_service_property_wrong_type' ),
			'service should have service settings'         => array( $service_settings_required, 'required_service_property_missing' ),
			'service should have form layout'              => array( $form_layout_required, 'required_service_property_missing' ),
			'service settings should have type'            => array( $service_settings_type_required, 'service_settings_missing_required_property' ),
			'service settings type should be a string'     => array( $service_settings_type_string, 'service_settings_property_wrong_type' ),
			'service settings should have required'        => array( $service_settings_required_required, 'service_settings_missing_required_property' ),
			'service settings required should be an array' => array( $service_settings_required_array, 'service_settings_property_wrong_type' ),
			'service settings should have properties'      => array( $service_settings_properties_required, 'service_settings_missing_required_property' ),
			'service settings properties should be an object' => array( $service_settings_properties_object, 'service_settings_property_wrong_type' ),
			'service settings properties should have title' => array( $service_settings_title_required, 'service_properties_missing_required_property' ),
			'boxes should be an object'                    => array( $service_settings_boxes_required, 'boxes_not_object' ),
		);

	}

	/**
	 * @dataProvider validate_services_errors_provider
	 * @covers WC_Connect_Service_Schemas_Validator::validate_service_schemas
	 */
	public function test_validate_services_errors( $services, $expected ) {

		$result = $this->validator->validate_service_schemas( $services );

		$this->assertWPError( $result );
		$this->assertEquals( $expected, $result->get_error_code() );

	}

	/**
	 * @covers WC_Connect_Service_Schemas_Validator::validate_service_schemas
	 */
	public function test_validate_services() {

		$services = self::get_golden_services();
		$result   = $this->validator->validate_service_schemas( $services );

		$this->assertNotWPError( $result );
		$this->assertTrue( $result );

	}

}
