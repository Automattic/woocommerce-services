<?php

require_once __DIR__ . '/class-wcservices-throwing-store-api-extend-schema.php';

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
	 * The bundled wc-api-dev copy of /wc/v3/data/continents is a fallback for WooCommerce versions
	 * that predate the endpoint. Core has shipped it since WC 3.5, so on any supported WooCommerce
	 * the fallback must stay dormant.
	 *
	 * This is the regression guard for the lazy-namespace interaction: since WC 9.2 core skips
	 * registering the whole wc/v3 namespace on requests aimed at another namespace, so the route
	 * table alone cannot tell us whether core provides the endpoint.
	 *
	 * Asserted against the route table rather than against class-load state: whether the bundled
	 * class has been required is process-global and outlives whichever test loaded it, so it cannot
	 * carry this contract under an arbitrary execution order.
	 *
	 * @covers WC_Connect_Loader::wc_api_dev_init
	 */
	public function test_wc_api_dev_init_defers_to_core_continents_controller() {
		global $wp, $wp_rest_server;

		if ( ! function_exists( 'wc_rest_should_load_namespace' ) ) {
			$this->markTestSkipped( 'This WooCommerce does not register REST namespaces lazily.' );
		}

		$this->assertTrue(
			class_exists( 'WC_REST_Data_Continents_Controller' ),
			'WooCommerce core is expected to provide WC_REST_Data_Continents_Controller.'
		);

		$original_server = $wp_rest_server;
		$original_route  = isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : null;

		// Reproduce a Store API request: core then skips the whole wc/v3 namespace, so
		// /wc/v3/data/continents is missing from the route table even though core provides it.
		$wp->query_vars['rest_route'] = '/wc/store/v1/cart';
		$wp_rest_server               = null;

		try {
			$this->assertArrayNotHasKey(
				'/wc/v3/data/continents',
				rest_get_server()->get_routes(),
				'Expected WooCommerce to defer the wc/v3 namespace on a wc/store request.'
			);

			$this->mockLoader()->wc_api_dev_init();

			$this->assertArrayNotHasKey(
				'/wc/v3/data/continents',
				rest_get_server()->get_routes(),
				'The bundled wc-api-dev continents controller must not register a route when WooCommerce core provides its own.'
			);
		} finally {
			$wp_rest_server = $original_server;

			if ( null === $original_route ) {
				unset( $wp->query_vars['rest_route'] );
			} else {
				$wp->query_vars['rest_route'] = $original_route;
			}
		}
	}

	/**
	 * The other half of the contract: when a WooCommerce genuinely does not supply the endpoint,
	 * the fallback must still fire and register the route.
	 *
	 * Every supported WooCommerce does supply WC_REST_Data_Continents_Controller, and class_exists()
	 * cannot be made to answer otherwise within a process, so that one question is stubbed at its
	 * seam (WC_Connect_Loader::core_provides_continents_controller) and the rest of wc_api_dev_init()
	 * runs for real: its own require_once calls, the controller construction and register_routes().
	 *
	 * The branch's own require_once calls are covered too, but only as far as PHP allows: loading a
	 * file is process-global and monotonic, so a dropped require_once is only observable while no
	 * earlier test has already loaded that class. Run on its own
	 * (composer test -- --filter test_wc_api_dev_init) this test fails if any of the three requires
	 * goes away; in a whole-suite run the WC_Connect_Continents one is masked, because
	 * tests/php/classes/test-class-wc-rest-connect-shipping-label-controller.php sorts earlier and
	 * loads that class for its own purposes. Closing that gap would need a fresh process per test,
	 * which this suite cannot afford: PHPUnit's process-isolation annotation re-runs the WP/WC
	 * bootstrap in the child (~26s) and that bootstrap reinstalls the shared test database
	 * underneath the tests that follow.
	 *
	 * @covers WC_Connect_Loader::wc_api_dev_init
	 */
	public function test_wc_api_dev_init_registers_bundled_continents_route_when_core_lacks_it() {
		global $wp, $wp_rest_server;

		$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( array( 'core_provides_continents_controller' ) )
			->getMock();

		$loader->expects( $this->any() )
			->method( 'core_provides_continents_controller' )
			->will( $this->returnValue( false ) );

		$original_server = $wp_rest_server;
		$original_route  = isset( $wp->query_vars['rest_route'] ) ? $wp->query_vars['rest_route'] : null;

		// A wc/store request keeps core's own wc/v3 registration out of the route table, so any
		// /wc/v3/data/continents route seen below can only have come from the bundled fallback.
		$wp->query_vars['rest_route'] = '/wc/store/v1/cart';
		$wp_rest_server               = null;

		try {
			$this->assertArrayNotHasKey(
				'/wc/v3/data/continents',
				rest_get_server()->get_routes(),
				'Expected WooCommerce to defer the wc/v3 namespace on a wc/store request.'
			);

			$loader->wc_api_dev_init();

			$routes = rest_get_server()->get_routes();

			$this->assertArrayHasKey(
				'/wc/v3/data/continents',
				$routes,
				'The bundled wc-api-dev fallback must register the collection route when core does not provide the endpoint.'
			);
			$this->assertArrayHasKey(
				'/wc/v3/data/continents/(?P<location>[\w-]+)',
				$routes,
				'The bundled wc-api-dev fallback must register the single-continent route as well.'
			);
		} finally {
			$wp_rest_server = $original_server;

			if ( null === $original_route ) {
				unset( $wp->query_vars['rest_route'] );
			} else {
				$wp->query_vars['rest_route'] = $original_route;
			}
		}
	}

	/**
	 * Pins the fix shipped in 2.5.1: the bundled controller used to call parent::__construct(),
	 * but no class in its ancestry (WC_REST_Dev_Data_Controller, WC_REST_Controller,
	 * WP_REST_Controller) declares a constructor, so that call raised
	 * "Uncaught Error: Cannot call constructor" every time the fallback branch was reached.
	 *
	 * Scope is exactly that: the controller is constructible given its dependency. The requires
	 * below are the test supplying that dependency itself, so the test stays deterministic when it
	 * runs alone; they are not a claim about what the fallback branch loads.
	 */
	public function test_bundled_continents_controller_is_constructible() {

		require_once __DIR__ . '/../../classes/class-wc-connect-continents.php';
		require_once __DIR__ . '/../../classes/wc-api-dev/class-wc-rest-dev-data-controller.php';
		require_once __DIR__ . '/../../classes/wc-api-dev/class-wc-rest-dev-data-continents-controller.php';

		$controller = new WC_REST_Dev_Data_Continents_Controller();

		$this->assertInstanceOf( 'WC_REST_Dev_Data_Continents_Controller', $controller );
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

			// Non-vacuity relies on the test harness container resolving a real
			// instance on re-entry: if the $attempted guard were removed, instance()
			// would re-run the constructor and return that non-null instance,
			// failing this assertion.
			$this->assertNull( \Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance() );
		} finally {
			$attempted->setValue( $orig_attempted );
			$instance->setValue( $orig_instance );
		}
	}

	/**
	 * The $attempted latch is what stops container resolution (and its debug log)
	 * from re-running on every request. This asserts the latch engages on the first
	 * call regardless of whether resolution succeeds or fails (WOOTAX-303).
	 *
	 * @testdox instance() sets the attempted latch on the first call.
	 * @covers Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance
	 */
	public function test_instance_latches_attempted_on_first_call() {
		$class     = '\Automattic\WCServices\StoreApi\StoreApiExtendSchema';
		$attempted = new ReflectionProperty( $class, 'attempted' );
		$instance  = new ReflectionProperty( $class, 'instance' );
		$attempted->setAccessible( true );
		$instance->setAccessible( true );

		$orig_attempted = $attempted->getValue();
		$orig_instance  = $instance->getValue();

		try {
			$attempted->setValue( false );
			$instance->setValue( null );

			\Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance();

			$this->assertTrue( $attempted->getValue(), 'instance() must latch $attempted on the first call so resolution runs at most once per request.' );
		} finally {
			$attempted->setValue( $orig_attempted );
			$instance->setValue( $orig_instance );
		}
	}

	/**
	 * Once resolution has been attempted, instance() must return the already-resolved
	 * instance without re-entering the container. A distinct ExtendSchema sentinel
	 * (built without the constructor, so it is never the container's shared instance)
	 * makes this deterministic: if the $attempted guard were removed, instance() would
	 * re-resolve and return a different object, failing the identity assertion. This
	 * avoids the working-container dependency of the null-path test (WOOTAX-303).
	 *
	 * @testdox instance() returns the cached instance without re-resolving once attempted.
	 * @covers Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance
	 */
	public function test_instance_returns_cached_instance_without_reresolving() {
		$class     = '\Automattic\WCServices\StoreApi\StoreApiExtendSchema';
		$attempted = new ReflectionProperty( $class, 'attempted' );
		$instance  = new ReflectionProperty( $class, 'instance' );
		$attempted->setAccessible( true );
		$instance->setAccessible( true );

		$orig_attempted = $attempted->getValue();
		$orig_instance  = $instance->getValue();

		// ExtendSchema is final; build a distinct real instance without its constructor.
		$sentinel = ( new ReflectionClass( '\Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema' ) )->newInstanceWithoutConstructor();

		try {
			$attempted->setValue( true );
			$instance->setValue( $sentinel );

			$this->assertSame( $sentinel, \Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance(), 'instance() must return the cached instance without re-resolving once resolution has been attempted.' );
		} finally {
			$attempted->setValue( $orig_attempted );
			$instance->setValue( $orig_instance );
		}
	}

	/**
	 * When the container throws while resolving ExtendSchema, instance() must catch it
	 * and return null instead of fataling. A TypeError is used deliberately: it is a
	 * Throwable but not an Exception, so pre-fix code (catch Exception) would let it
	 * propagate. The failure must also be logged once (WOOTAX-303).
	 *
	 * @testdox instance() returns null and logs when the container throws a non-Exception Throwable.
	 * @covers Automattic\WCServices\StoreApi\StoreApiExtendSchema::instance
	 */
	public function test_instance_returns_null_when_container_throws() {
		$class     = '\Automattic\WCServices\StoreApi\StoreApiExtendSchema';
		$attempted = new ReflectionProperty( $class, 'attempted' );
		$instance  = new ReflectionProperty( $class, 'instance' );
		$attempted->setAccessible( true );
		$instance->setAccessible( true );

		$orig_attempted = $attempted->getValue();
		$orig_instance  = $instance->getValue();

		$logger = $this->getMockBuilder( 'WC_Logger_Interface' )->getMock();
		$logger->expects( $this->once() )
			->method( 'debug' )
			->with( 'Failed to get ExtendSchema instance.', $this->anything() );

		$inject_logger = function () use ( $logger ) {
			return $logger;
		};
		add_filter( 'woocommerce_logging_class', $inject_logger );

		try {
			$attempted->setValue( false );
			$instance->setValue( null );

			$result = WCServices_Throwing_Store_Api_Extend_Schema::instance();

			$this->assertNull( $result, 'instance() must return null - not fatal - when the container throws a non-Exception Throwable.' );
		} finally {
			remove_filter( 'woocommerce_logging_class', $inject_logger );
			$attempted->setValue( $orig_attempted );
			$instance->setValue( $orig_instance );
		}
	}

	/**
	 * Hooks that `load_admin_dependencies()` attaches callbacks to. Snapshotted around the
	 * legacy DHL note tests so wiring the admin dependencies does not leak into other tests.
	 *
	 * @var string[]
	 */
	private static $admin_dependency_hooks = array(
		'woocommerce_debug_tools',
		'woocommerce_admin_status_tabs',
		'woocommerce_admin_status_content_connect',
		'wp_ajax_wcs_download_tax_backup',
		'admin_notices',
		'woocommerce_get_sections_shipping',
		'woocommerce_settings_shipping',
	);

	/**
	 * The plugin never calls `load_dependencies()` under WC_UNIT_TESTING, so whether a class has
	 * been required is a function of test execution order. Require everything these tests touch.
	 */
	private function require_admin_dependency_classes() {
		$classes = __DIR__ . '/../../classes/';

		require_once $classes . 'class-wc-connect-logger.php';
		require_once $classes . 'class-wc-connect-api-client.php';
		require_once $classes . 'class-wc-connect-api-client-live.php';
		require_once $classes . 'class-wc-connect-service-schemas-store.php';
		require_once $classes . 'class-wc-connect-service-settings-store.php';
		require_once $classes . 'class-wc-connect-taxjar-integration.php';
		require_once $classes . 'class-wc-connect-tracks.php';
		require_once $classes . 'class-wc-connect-error-notice.php';
		require_once $classes . 'class-wc-connect-help-view.php';
		require_once $classes . 'class-wc-connect-continents.php';
		require_once $classes . 'class-wc-connect-note-dhl-live-rates-available.php';
	}

	/**
	 * Skip when the environment cannot exercise the legacy DHL note path at all, so a green
	 * assertion always means the guard behaved, never that the path was inert.
	 */
	private function require_legacy_dhl_note_preconditions() {
		if ( WC_Connect_Loader::is_wc_shipping_activated() ) {
			$this->markTestSkipped( 'WooCommerce Shipping is active here, so the legacy DHL note path is inert.' );
		}

		if ( ! WC_Connect_Loader::can_add_wc_admin_notice() ) {
			$this->markTestSkipped( 'The WooCommerce admin-note data store is unavailable in this environment.' );
		}
	}

	/**
	 * Build a loader wired with the dependencies `load_admin_dependencies()` dereferences, over a
	 * schemas store that reports the legacy DHL Express live rates schema.
	 *
	 * @param object $schema_call_expectation PHPUnit invocation matcher for get_all_shipping_method_ids().
	 *
	 * @return PHPUnit\Framework\MockObject\MockObject|WC_Connect_Loader
	 */
	private function mock_admin_loader_for_legacy_dhl_store( $schema_call_expectation ) {
		$store = $this->getMockBuilder( 'WC_Connect_Service_Schemas_Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_all_shipping_method_ids' ) )
			->getMock();

		$store->expects( $schema_call_expectation )
			->method( 'get_all_shipping_method_ids' )
			->will( $this->returnValue( array( 'wc_services_dhlexpress' ) ) );

		$loader = $this->mockLoader( $store );

		$loader->set_api_client(
			$this->getMockBuilder( 'WC_Connect_API_Client_Live' )->disableOriginalConstructor()->getMock()
		);
		$loader->set_service_settings_store(
			$this->getMockBuilder( 'WC_Connect_Service_Settings_Store' )->disableOriginalConstructor()->getMock()
		);
		$loader->set_taxjar(
			$this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )->disableOriginalConstructor()->getMock()
		);

		return $loader;
	}

	/**
	 * Copy the callbacks currently attached to the hooks `load_admin_dependencies()` writes to.
	 *
	 * @return array
	 */
	private function snapshot_admin_dependency_hooks() {
		$snapshot = array();

		foreach ( self::$admin_dependency_hooks as $hook ) {
			$snapshot[ $hook ] = isset( $GLOBALS['wp_filter'][ $hook ] ) ? clone $GLOBALS['wp_filter'][ $hook ] : null;
		}

		return $snapshot;
	}

	/**
	 * Restore the callbacks captured by snapshot_admin_dependency_hooks().
	 *
	 * @param array $snapshot Snapshot to restore.
	 */
	private function restore_admin_dependency_hooks( $snapshot ) {
		foreach ( $snapshot as $hook => $wp_hook ) {
			if ( null === $wp_hook ) {
				unset( $GLOBALS['wp_filter'][ $hook ] );
			} else {
				$GLOBALS['wp_filter'][ $hook ] = $wp_hook;
			}
		}
	}

	/**
	 * An eligible legacy DHL store must still be offered the Inbox note on an ordinary wp-admin
	 * page load. The AJAX guard only skips the work while admin-ajax.php is being served, so this
	 * is the non-AJAX path that keeps grandfathered stores whole.
	 *
	 * @testdox load_admin_dependencies() still registers the legacy DHL note outside admin AJAX.
	 * @covers WC_Connect_Loader::load_admin_dependencies
	 */
	public function test_load_admin_dependencies_registers_legacy_dhl_note_outside_ajax() {
		$this->require_admin_dependency_classes();
		$this->require_legacy_dhl_note_preconditions();

		WC_Connect_Note_DHL_Live_Rates_Available::possibly_delete_note();
		$this->assertFalse(
			WC_Connect_Note_DHL_Live_Rates_Available::note_exists(),
			'The fixture must start without the note, or the assertion below proves nothing.'
		);

		$loader   = $this->mock_admin_loader_for_legacy_dhl_store( $this->once() );
		$snapshot = $this->snapshot_admin_dependency_hooks();

		add_filter( 'wc_connect_has_only_tax_functionality', '__return_false' );

		try {
			$loader->load_admin_dependencies();

			$this->assertTrue(
				WC_Connect_Note_DHL_Live_Rates_Available::note_exists(),
				'An eligible legacy DHL store must still receive the Inbox note on a non-AJAX admin request.'
			);
		} finally {
			remove_filter( 'wc_connect_has_only_tax_functionality', '__return_false' );
			$this->restore_admin_dependency_hooks( $snapshot );
			WC_Connect_Note_DHL_Live_Rates_Available::possibly_delete_note();
		}
	}

	/**
	 * `is_admin()` is also true while WordPress serves admin-ajax.php, so the legacy DHL note path
	 * used to run a service-schema read and an Inbox note lookup on every admin AJAX request. The
	 * guard must skip that block only - the rest of the admin dependency wiring still runs, so
	 * subclasses that override it keep getting called.
	 *
	 * @testdox load_admin_dependencies() skips the legacy DHL note during admin AJAX.
	 * @covers WC_Connect_Loader::load_admin_dependencies
	 */
	public function test_load_admin_dependencies_skips_legacy_dhl_note_during_ajax() {
		$this->require_admin_dependency_classes();
		$this->require_legacy_dhl_note_preconditions();

		WC_Connect_Note_DHL_Live_Rates_Available::possibly_delete_note();

		$loader   = $this->mock_admin_loader_for_legacy_dhl_store( $this->never() );
		$snapshot = $this->snapshot_admin_dependency_hooks();

		add_filter( 'wc_connect_has_only_tax_functionality', '__return_false' );
		add_filter( 'wp_doing_ajax', '__return_true' );

		try {
			$loader->load_admin_dependencies();

			$this->assertFalse(
				WC_Connect_Note_DHL_Live_Rates_Available::note_exists(),
				'The legacy DHL Inbox note must not be evaluated or created while serving admin-ajax.php.'
			);
			$this->assertInstanceOf(
				'WC_Connect_Settings_Pages',
				$loader->get_settings_pages(),
				'The guard must skip only the Inbox note - the rest of the admin dependencies still load during AJAX.'
			);
			$this->assertInstanceOf(
				'WC_Connect_Help_View',
				$loader->get_help_view(),
				'The guard must skip only the Inbox note - the rest of the admin dependencies still load during AJAX.'
			);
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wc_connect_has_only_tax_functionality', '__return_false' );
			$this->restore_admin_dependency_hooks( $snapshot );
			WC_Connect_Note_DHL_Live_Rates_Available::possibly_delete_note();
		}
	}
}
