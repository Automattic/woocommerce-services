<?php

class WP_Test_WC_Connect_Loader extends WC_Unit_Test_Case {

	const SERVICE_SCRIPT_HANDLE = 'wc_connect_admin';

	public function tear_down() {
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
			->setMethods( array( 'get_service_schemas_store', 'get_api_client', 'get_logger', 'get_shipping_logger', 'get_tracks' ) )
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

		return $loader;
	}

	public function mockLoaderAndActiveShippingMethods() {
		$service_data = array(
			'test_method_that_is_from_wc_connect',
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
			'method_id' => 'test_method',
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

	/**
	 * When the StoreApi class is present, extend_store_api() registers the
	 * plugin's Store API extensions.
	 *
	 * @covers WC_Connect_Loader::extend_store_api
	 */
	public function test_extend_store_api_registers_when_store_api_available() {
		$sut = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_store_api_available', 'register_store_api_extensions' ) )
			->getMock();

		$sut->method( 'is_store_api_available' )->willReturn( true );
		$sut->expects( $this->once() )->method( 'register_store_api_extensions' );

		$sut->extend_store_api();
	}

	/**
	 * On WooCommerce versions without the StoreApi class, extend_store_api()
	 * skips registration instead of fataling on the missing class. This is the
	 * guard that prevents the `Class "…\StoreApi" not found` fatal (WOOTAX-298).
	 *
	 * @covers WC_Connect_Loader::extend_store_api
	 */
	public function test_extend_store_api_skips_registration_when_store_api_unavailable() {
		$sut = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_store_api_available', 'register_store_api_extensions' ) )
			->getMock();

		$sut->method( 'is_store_api_available' )->willReturn( false );
		$sut->expects( $this->never() )->method( 'register_store_api_extensions' );

		// Must not throw when the StoreApi class is absent.
		$this->assertNull( $sut->extend_store_api() );
	}

	/**
	 * is_store_api_available() returns true when the StoreApi class the plugin
	 * depends on is present. The PHPUnit harness loads WooCommerce, so the class
	 * exists here - pinning to true means a typo or rename in the guarded class
	 * string would fail this test rather than silently tracking the change.
	 *
	 * @covers WC_Connect_Loader::is_store_api_available
	 */
	public function test_is_store_api_available_returns_true_when_store_api_class_present() {
		// Precondition: the harness must actually load the StoreApi class for the
		// assertion below to be meaningful.
		$this->assertTrue(
			class_exists( '\Automattic\WooCommerce\StoreApi\StoreApi' ),
			'Test precondition: the StoreApi class must be loaded in the test harness.'
		);

		$sut = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$method = new ReflectionMethod( 'WC_Connect_Loader', 'is_store_api_available' );
		$method->setAccessible( true );

		$this->assertTrue(
			$method->invoke( $sut ),
			'is_store_api_available() should return true when the StoreApi class is present.'
		);
	}

	/**
	 * On the unavailable path, extend_store_api() logs a notice - but at most
	 * once per day, because it runs on `woocommerce_blocks_loaded` (nearly every
	 * request). The throttle transient must suppress the second same-day call.
	 *
	 * @covers WC_Connect_Loader::extend_store_api
	 * @covers WC_Connect_Loader::log_store_api_unavailable
	 */
	public function test_extend_store_api_logs_unavailable_notice_once_per_day() {
		delete_transient( 'wcservices_store_api_unavailable_logged' );

		// Spy logger injected via the woocommerce_logging_class filter. Returning
		// an object bypasses wc_get_logger()'s static cache.
		$logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		$logger->expects( $this->once() )
			->method( 'notice' )
			->with(
				'StoreApi class not found. Store API extensions will not be registered.',
				array( 'source' => 'woocommerce-services' )
			);

		$inject_logger = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'woocommerce_logging_class', $inject_logger );

		$sut = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_store_api_available', 'register_store_api_extensions' ) )
			->getMock();

		$sut->method( 'is_store_api_available' )->willReturn( false );
		$sut->expects( $this->never() )->method( 'register_store_api_extensions' );

		// First skip logs the notice; the second same-day skip is throttled.
		$sut->extend_store_api();
		$sut->extend_store_api();

		remove_filter( 'woocommerce_logging_class', $inject_logger );
		delete_transient( 'wcservices_store_api_unavailable_logged' );
	}

	/**
	 * When the container cannot resolve ExtendSchema, get_store_api_extend_schema()
	 * returns null and register_store_api_extensions() must skip quietly instead of
	 * passing null into StoreApiExtensionController's typed constructor (WOOTAX-303).
	 *
	 * @covers WC_Connect_Loader::register_store_api_extensions
	 */
	public function test_register_store_api_extensions_skips_when_schema_unavailable() {
		$sut = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_store_api_extend_schema' ) )
			->getMock();

		$sut->method( 'get_store_api_extend_schema' )->willReturn( null );

		$method = new ReflectionMethod( 'WC_Connect_Loader', 'register_store_api_extensions' );
		$method->setAccessible( true );

		// Must not throw a TypeError; the early return yields void (null).
		$this->assertNull( $method->invoke( $sut ) );
	}

	/**
	 * When resolution has already been attempted and failed, instance() returns
	 * null instead of fataling on an uninitialized typed static, and the $attempted
	 * guard prevents a re-resolution (which, against the working test-harness
	 * container, would otherwise return a non-null instance) (WOOTAX-303).
	 *
	 * @covers Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance
	 */
	public function test_instance_returns_null_when_resolution_failed() {
		$class     = '\Automattic\WCServices\StoreApi\StoreApiExtendSchema';
		$attempted = new ReflectionProperty( $class, 'attempted' );
		$instance  = new ReflectionProperty( $class, 'instance' );
		$attempted->setAccessible( true );
		$instance->setAccessible( true );

		// Static state leaks across tests; capture and restore it.
		$orig_attempted = $attempted->getValue();
		$orig_instance  = $instance->getValue();

		try {
			$attempted->setValue( true );   // resolution already attempted...
			$instance->setValue( null );    // ...and it failed.

			$this->assertNull( \Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance() );
		} finally {
			$attempted->setValue( $orig_attempted );
			$instance->setValue( $orig_instance );
		}
	}
}
