<?php

class WP_Test_WC_Connect_Loader extends WC_Unit_Test_Case {

	const SERVICE_SCRIPT_HANDLE = 'wc_connect_admin';

	public function tearDown() {
		wp_deregister_script( self::SERVICE_SCRIPT_HANDLE );
		remove_all_actions( 'wc_connect_shipping_zone_method_added' );
		remove_all_actions( 'wc_connect_shipping_zone_method_deleted' );
		remove_all_actions( 'wc_connect_shipping_zone_method_status_toggled' );
		remove_all_actions( 'wc_connect_saved_service_settings' );
	}

	protected function mockLoader( $store = false, $api_client = false, $logger = false, $tracks = false ) {
		if ( ! $store ) {
			$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
				->disableOriginalConstructor()
				->setMethods( null )
				->getMock();
		}

		$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_service_schemas_store', 'get_api_client', 'get_logger', 'get_shipping_logger', 'get_tracks', 'get_cart_validator' ) )
			->getMock();

		$loader->expects( $this->any() )
			->method( 'get_service_schemas_store' )
			->will( $this->returnValue( $store ) );

		if ( ! $api_client ) {
			$api_client = $this->getMockBuilder( 'WC_Connect_API_Client_Live' )
				->disableOriginalConstructor()
				->getMock();
		}

		$loader->expects( $this->any() )
			->method( 'get_api_client' )
			->will( $this->returnValue( $api_client ) );

		if ( ! $logger ) {
			$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
				->disableOriginalConstructor()
				->getMock();
		}

		$loader->expects( $this->any() )
			->method( 'get_logger' )
			->will( $this->returnValue( $logger ) );

		$loader->expects( $this->any() )
			->method( 'get_shipping_logger' )
			->will( $this->returnValue( $logger ) );

		if ( ! $tracks ) {
			$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )
				->disableOriginalConstructor()
				->getMock();
		}

		$loader->expects( $this->any() )
			->method( 'get_tracks' )
			->will( $this->returnValue( $tracks ) );

		$cart_validator = $this->getMockBuilder( 'WC_Connect_Cart_Validation' )
			->disableOriginalConstructor()
			->getMock();

		$loader->expects( $this->any() )
			->method( 'get_cart_validator' )
			->will( $this->returnValue( $cart_validator ) );

		return $loader;
	}

	public function mockLoaderAndActiveShippingMethods() {
		$service_data = array(
			'test_method_that_is_from_wc_connect'
		);

		$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_all_shipping_method_ids' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'get_all_shipping_method_ids' )
			->will( $this->returnValue( $service_data ) );


		return $this->mockLoader( $store );
	}

	public function test_class_exists() {

		$this->assertTrue( class_exists( 'WC_Connect_Loader' ) );

	}

	/**
	 * @covers WC_Connect_Loader::__construct
	 */
	public function test_init_hook_attached_in_constructor() {

		$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
			->setMethods( array( 'pre_wc_init' ) )
			->getMock();

		$attached = has_action( 'plugins_loaded', array( $loader, 'on_plugins_loaded' ) );
		$this->assertNotFalse( $attached, 'WC_Connect_Loader::on_plugins_loaded() not attached to `plugins_loaded`.' );
		do_action( 'plugins_loaded' );

		$attached = has_action( 'before_woocommerce_init', array( $loader, 'pre_wc_init' ) );
		$this->assertNotFalse( $attached, 'WC_Connect_Loader::pre_wc_init() not attached to `before_woocommerce_init`.' );

	}

	/**
	 * @covers WC_Connect_Loader::get_logger
	 * @covers WC_Connect_Loader::set_logger
	 */
	public function test_logger_getter_setter() {

		$loader = $this->mockLoader();

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

		$client = $this->getMockBuilder( 'WC_Connect_API_Client_Live' )
			->disableOriginalConstructor()
			->getMock();
		$loader = $this->mockLoader( false, $client );

		$loader->set_api_client( $client );

		$this->assertEquals( $client, $loader->get_api_client() );

	}

	/**
	 * @covers WC_Connect_Loader::get_service_schemas_store
	 * @covers WC_Connect_Loader::set_service_schemas_store
	 */
	public function test_services_store_getter_setter() {

		$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
			->disableOriginalConstructor()
			->getMock();

		$loader = $this->mockLoader( $store );

		$loader->set_service_schemas_store( $store );

		$this->assertEquals( $store, $loader->get_service_schemas_store() );

	}

	/**
	 * @covers WC_Connect_Loader::get_service_schemas_validator
	 * @covers WC_Connect_Loader::set_service_schemas_validator
	 */
	public function test_services_validator_getter_setter() {

		$loader = $this->mockLoader();

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

		$loader = $this->mockLoader();
		$loader->load_dependencies();

		$this->assertInstanceOf( 'WC_Connect_Logger', $loader->get_logger() );
		$this->assertInstanceOf( 'WC_Connect_API_Client', $loader->get_api_client() );
		$this->assertInstanceOf( 'WC_Connect_Service_Schemas_Validator', $loader->get_service_schemas_validator() );
		$this->assertInstanceOf( 'WC_Connect_Service_Schemas_Store', $loader->get_service_schemas_store() );
		$this->assertInstanceOf( 'WC_Connect_Tracks', $loader->get_tracks() );
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

		$loader = $this->mockLoader( $store );

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
		$loader = $this->mockLoaderAndActiveShippingMethods();

		$this->assertTrue( $loader->is_wc_connect_shipping_service( 'test_method_that_is_from_wc_connect' ) );
		$this->assertFalse( $loader->is_wc_connect_shipping_service( 'test_method_that_is_not_from_wc_connect' ) );

	}

	/**
	 * @covers WC_Connect_Loader::shipping_zone_method_added
	 */
	public function test_shipping_zone_method_added() {
		$loader = $this->mockLoaderAndActiveShippingMethods();

		$this->assertEquals( 0, did_action( 'wc_connect_shipping_zone_method_added' ) );
		$loader->shipping_zone_method_added( 3, 'test_method_that_is_from_wc_connect', 2 );
		$this->assertEquals( 1, did_action( 'wc_connect_shipping_zone_method_added' ) );
		$loader->shipping_zone_method_added( 3, 'test_method_that_is_not_from_wc_connect', 2 );
		$this->assertEquals( 1, did_action( 'wc_connect_shipping_zone_method_added' ) );
		$loader->shipping_zone_method_added( 3, 'test_method_that_is_from_wc_connect', 2 );
		$this->assertEquals( 2, did_action( 'wc_connect_shipping_zone_method_added' ) );
	}

}
