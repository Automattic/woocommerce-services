<?php

class WP_Test_WC_Connect_Loader extends WC_Unit_Test_Case {

	const SERVICE_SCRIPT_HANDLE = 'wc_connect_admin';

	public function tearDown() {

		wp_deregister_script( self::SERVICE_SCRIPT_HANDLE );

	}

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Loader' ) );

	}

	/**
	 * @covers WC_Connect_Loader::__construct
	 */
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
	 * @covers WC_Connect_Loader::enqueue_service_script
	 */
	public function test_enqueue_service_script( $hook, $tab, $instance_id, $expected ) {

		$loader = new WC_Connect_Loader();

		$loader->enqueue_service_script( $hook, $tab, $instance_id );

		$this->assertEquals( $expected, wp_script_is( self::SERVICE_SCRIPT_HANDLE, 'registered' ) );

	}

	/**
	 * @covers WC_Connect_Loader::get_logger
	 * @covers WC_Connect_Loader::set_logger
	 */
	public function test_logger_getter_setter() {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->getMock();
		$loader->set_logger( $logger );

		$this->assertEquals( $logger, $loader->get_logger() );

	}

	/**
	 * @covers WC_Connect_Loader::get_api_client
	 * @covers WC_Connect_Loader::set_api_client
	 */
	public function test_api_client_getter_setter() {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$client = $this->getMockBuilder( 'WC_Connect_API_Client' )
			->disableOriginalConstructor()
			->getMock();
		$loader->set_api_client( $client );

		$this->assertEquals( $client, $loader->get_api_client() );

	}

	/**
	 * @covers WC_Connect_Loader::get_service_schemas_store
	 * @covers WC_Connect_Loader::set_service_schemas_store
	 */
	public function test_services_store_getter_setter() {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
			->disableOriginalConstructor()
			->getMock();
		$loader->set_service_schemas_store( $store );

		$this->assertEquals( $store, $loader->get_service_schemas_store() );

	}

	/**
	 * @covers WC_Connect_Loader::get_service_schemas_validator
	 * @covers WC_Connect_Loader::set_service_schemas_validator
	 */
	public function test_services_validator_getter_setter() {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$validator = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Validator' )
			->disableOriginalConstructor()
			->getMock();
		$loader->set_service_schemas_validator( $validator );

		$this->assertEquals( $validator, $loader->get_service_schemas_validator() );

	}

	/**
	 * @covers WC_Connect_Loader::load_dependencies
	 */
	public function test_load_dependencies() {

		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();

		$this->assertInstanceOf( 'WC_Connect_Logger', $loader->get_logger() );
		$this->assertInstanceOf( 'WC_Connect_API_Client', $loader->get_api_client() );
		$this->assertInstanceOf( 'WC_Connect_Service_Schemas_Validator', $loader->get_service_schemas_validator() );
		$this->assertInstanceOf( 'WC_Connect_Service_Schemas_Store', $loader->get_service_schemas_store() );

	}

	/**
	 * @covers WC_Connect_Loader::init_service
	 */
	public function test_init_service() {

		$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_service_schema_by_id_or_instance_id' ) )
			->getMock();

		$service_data = array(
			'method_id' => 'test_method'
		);

		$store->expects( $this->any() )
			->method( 'get_service_schema_by_id_or_instance_id' )
			->will( $this->returnValue( $service_data ) );

		$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_service_schemas_store' ) )
			->getMock();

		$loader->expects( $this->any() )
			->method( 'get_service_schemas_store' )
			->will( $this->returnValue( $store ) );

		$loader->load_dependencies();

		$method = new WC_Connect_Shipping_Method();

		$loader->init_service( $method, 1 );

		$this->assertEquals( $loader->get_logger(), $method->get_logger() );
		$this->assertEquals( $loader->get_api_client(), $method->get_api_client() );
		$this->assertEquals( $service_data, $method->get_service_schema() );

	}

	/**
	 * @covers WC_Connect_Loader::is_wc_connect_shipping_service
	 */
	public function test_is_wc_connect_shipping_service() {

		$service_data = array(
			'test_method_that_is_from_wc_connect'
		);

		$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_all_service_ids_of_type' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'get_all_service_ids_of_type' )
			->will( $this->returnValue( $service_data ) );

		$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_service_schemas_store' ) )
			->getMock();

		$loader->expects( $this->any() )
			->method( 'get_service_schemas_store' )
			->will( $this->returnValue( $store ) );

		$loader->load_dependencies();

		$this->assertTrue( $loader->is_wc_connect_shipping_service( 'test_method_that_is_from_wc_connect' ) );
		$this->assertFalse( $loader->is_wc_connect_shipping_service( 'test_method_that_is_not_from_wc_connect' ) );

	}

}
