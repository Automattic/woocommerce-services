<?php

/**
 * Which wc/v1/connect routes WC_Connect_Loader::rest_api_init() registers for each store type.
 */
class WP_Test_WC_Connect_Loader_REST_Routes extends WC_REST_Unit_Test_Case {

	const WC_SHIPPING_PLUGIN = 'woocommerce-shipping/woocommerce-shipping.php';

	/**
	 * Routes registered only when shipping features load: WC Shipping inactive and not tax-only.
	 *
	 * @var string[]
	 */
	private static $shipping_routes = array(
		'/wc/v1/connect/packages',
		'/wc/v1/connect/label/(?P<order_id>\d+)',
		'/wc/v1/connect/label/(?P<order_id>\d+)/(?P<label_ids>(\d+)(,\d+)*)',
		'/wc/v1/connect/label/(?P<order_id>\d+)/(?P<label_id>\d+)/refund',
		'/wc/v1/connect/label/preview',
		'/wc/v1/connect/label/print',
		'/wc/v1/connect/label/(?P<order_id>\d+)/rates',
		'/wc/v1/connect/normalize-address',
		'/wc/v1/connect/assets',
		'/wc/v1/connect/shipping/carrier',
		'/wc/v1/connect/subscription/(?P<subscription_key>.+)/activate',
		'/wc/v1/connect/shipping/carrier/(?P<carrier_id>.+)',
		'/wc/v1/connect/shipping/carrier-types',
	);

	/**
	 * Routes registered on every store that is not tax-only, whether WC Shipping is active or not.
	 *
	 * @var string[]
	 */
	private static $grandfathered_routes = array(
		'/wc/v1/connect/migration-flag',
		'/wc/v1/connect/shipping/carriers',
		'/wc/v1/connect/subscriptions',
	);

	/**
	 * Routes registered on every store.
	 *
	 * @var string[]
	 */
	private static $always_routes = array(
		'/wc/v1/connect/account/settings',
		'/wc/v1/connect/services/(?P<id>[a-z_]+)\/(?P<instance>[\d]+)',
		'/wc/v1/connect/self-help',
		'/wc/v1/connect/service-data-refresh',
		'/wc/v1/connect/label/creation_eligibility',
		'/wc/v1/connect/label/(?P<order_id>\d+)/creation_eligibility',
	);

	/**
	 * @inherit
	 */
	public static function set_up_before_class() {
		$classes = __DIR__ . '/../../classes/';
		require_once $classes . 'class-wc-connect-logger.php';
		require_once $classes . 'class-wc-connect-api-client.php';
		require_once $classes . 'class-wc-connect-api-client-live.php';
		require_once $classes . 'class-wc-connect-service-schemas-store.php';
		require_once $classes . 'class-wc-connect-service-settings-store.php';
		require_once $classes . 'class-wc-connect-payment-methods-store.php';
		require_once $classes . 'class-wc-connect-shipping-label.php';
		require_once $classes . 'class-wc-connect-tracks.php';
		require_once $classes . 'class-wc-connect-package-settings.php';
		require_once $classes . 'class-wc-connect-account-settings.php';
	}

	/**
	 * Swap in an empty REST server. Overrides setUp() rather than set_up() because
	 * WC_REST_Unit_Test_Case only creates the REST server after set_up() returns.
	 */
	public function setUp(): void {
		parent::setUp();

		// The base controller sends a no-cache header on dispatch; the spy server records it instead.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		$this->server              = $GLOBALS['wp_rest_server'];

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Remove the store-type filters.
	 */
	public function tearDown(): void {
		remove_all_filters( 'wc_connect_has_only_tax_functionality' );
		remove_all_filters( 'option_active_plugins' );

		parent::tearDown();
	}

	/**
	 * Build a loader whose dependencies are mocks, so rest_api_init() makes no remote calls.
	 *
	 * @return WC_Connect_Loader
	 */
	private function build_loader() {
		$loader = $this->getMockBuilder( 'WC_Connect_Loader' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();

		$loader->set_logger( $this->createMock( WC_Connect_Logger::class ) );
		$loader->set_api_client( $this->createMock( WC_Connect_API_Client_Live::class ) );
		$loader->set_service_schemas_store( $this->createMock( WC_Connect_Service_Schemas_Store::class ) );
		$loader->set_service_settings_store( $this->createMock( WC_Connect_Service_Settings_Store::class ) );
		$loader->set_payment_methods_store( $this->createMock( WC_Connect_Payment_Methods_Store::class ) );
		$loader->set_shipping_label( $this->createMock( WC_Connect_Shipping_Label::class ) );
		$loader->set_tracks( $this->createMock( WC_Connect_Tracks::class ) );

		return $loader;
	}

	/**
	 * Register the plugin's routes for a store of the given type.
	 *
	 * @param bool $has_only_tax_functionality Whether the store is tax-only.
	 * @param bool $wc_shipping_active         Whether WooCommerce Shipping is active.
	 */
	private function register_routes_for_store( $has_only_tax_functionality, $wc_shipping_active ) {
		add_filter( 'wc_connect_has_only_tax_functionality', $has_only_tax_functionality ? '__return_true' : '__return_false' );
		add_filter(
			'option_active_plugins',
			function ( $plugins ) use ( $wc_shipping_active ) {
				$plugins = array_diff( (array) $plugins, array( self::WC_SHIPPING_PLUGIN ) );
				if ( $wc_shipping_active ) {
					$plugins[] = self::WC_SHIPPING_PLUGIN;
				}
				return array_values( $plugins );
			}
		);

		$this->build_loader()->rest_api_init();
	}

	/**
	 * The wc/v1/connect routes on the server, except the WC Shipping compatibility packages route,
	 * which is registered by its own class through wcservices_rest_api_init.
	 *
	 * @return string[]
	 */
	private function connect_routes() {
		$routes = array_filter(
			array_keys( $this->server->get_routes() ),
			function ( $route ) {
				return 0 === strpos( $route, '/wc/v1/connect/' ) && 0 !== strpos( $route, '/wc/v1/connect/wcservices/' );
			}
		);
		sort( $routes );

		return array_values( $routes );
	}

	/**
	 * @param string[] ...$lists Route lists.
	 * @return string[]
	 */
	private function sorted( ...$lists ) {
		$routes = array_merge( ...$lists );
		sort( $routes );

		return $routes;
	}

	/**
	 * @testdox A tax-only store registers no shipping routes, only the eligibility and shared routes.
	 */
	public function test_tax_only_store_registers_no_shipping_routes() {
		$this->register_routes_for_store( true, false );

		$this->assertSame( $this->sorted( self::$always_routes ), $this->connect_routes() );
	}

	/**
	 * @testdox A tax-only store answers 404 on the label purchase, rates and carrier routes.
	 */
	public function test_tax_only_store_returns_404_on_shipping_routes() {
		$this->register_routes_for_store( true, false );

		$requests = array(
			array( 'POST', '/wc/v1/connect/label/123' ),
			array( 'POST', '/wc/v1/connect/label/123/rates' ),
			array( 'POST', '/wc/v1/connect/shipping/carrier' ),
			array( 'DELETE', '/wc/v1/connect/shipping/carrier/ups' ),
		);

		foreach ( $requests as list( $method, $path ) ) {
			$response = $this->server->dispatch( new WP_REST_Request( $method, $path ) );

			$this->assertSame( 404, $response->get_status(), "$method $path" );
			$this->assertSame( 'rest_no_route', $response->get_data()['code'], "$method $path" );
		}
	}

	/**
	 * @testdox A grandfathered store without WC Shipping registers every route it had before.
	 */
	public function test_grandfathered_store_registers_every_route() {
		$this->register_routes_for_store( false, false );

		$this->assertSame(
			$this->sorted( self::$always_routes, self::$shipping_routes, self::$grandfathered_routes ),
			$this->connect_routes()
		);
	}

	/**
	 * @testdox A grandfathered store dispatches a label route and the migration flag route to their handlers.
	 */
	public function test_grandfathered_store_dispatches_label_and_migration_flag_routes() {
		$this->register_routes_for_store( false, false );

		// No label ids: the print handler answers 400 itself, without a remote call.
		$print_request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/print' );
		$print_request->set_query_params( array( 'paper_size' => 'label' ) );
		$print_response = $this->server->dispatch( $print_request );

		$this->assertSame( 400, $print_response->get_status() );
		$this->assertSame( 'invalid_pdf_request', $print_response->get_data()['code'] );

		// A state past COMPLETED is acknowledged without writing anything.
		$flag_request = new WP_REST_Request( 'POST', '/wc/v1/connect/migration-flag' );
		$flag_request->set_header( 'Content-Type', 'application/json' );
		$flag_request->set_body( wp_json_encode( array( 'migration_state' => PHP_INT_MAX ) ) );
		$flag_response = $this->server->dispatch( $flag_request );

		$this->assertSame( 200, $flag_response->get_status() );
		$this->assertArrayHasKey( 'result', $flag_response->get_data() );
	}

	/**
	 * @testdox A grandfathered store with WC Shipping active keeps the migration flag, carriers and subscriptions routes.
	 */
	public function test_grandfathered_store_with_wc_shipping_keeps_routes_outside_shipping_block() {
		$this->register_routes_for_store( false, true );

		$this->assertSame(
			$this->sorted( self::$always_routes, self::$grandfathered_routes ),
			$this->connect_routes()
		);
	}
}
