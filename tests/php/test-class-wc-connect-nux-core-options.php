<?php
/**
 * Tests for the Terms of Service guard on the WooCommerce core options REST route.
 *
 * @package WooCommerce_Services
 */

use Automattic\WooCommerce\Admin\API\Options;

require_once __DIR__ . '/class-wcs-test-jetpack-connection.php';

/**
 * PUT /wc-admin/options can write wc_connect_options and woocommerce_setup_jetpack_opted_in
 * for any shop manager. Only a user who may accept the Terms of Service may change the
 * acceptance that way.
 */
class WP_Test_WC_Connect_Nux_Core_Options extends WC_REST_Unit_Test_Case {

	/**
	 * Route under test.
	 *
	 * @var string
	 */
	const ROUTE = '/wc-admin/options';

	/**
	 * The Jetpack connection owner, an administrator.
	 *
	 * @var int
	 */
	private $owner;

	/**
	 * An administrator who is not the connection owner.
	 *
	 * @var int
	 */
	private $other_admin;

	/**
	 * A shop manager.
	 *
	 * @var int
	 */
	private $shop_manager;

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$classes = __DIR__ . '/../../classes/';
		require_once $classes . 'class-wc-connect-options.php';
		require_once $classes . 'class-wc-connect-jetpack.php';
		require_once $classes . 'class-wc-connect-nux.php';
		require_once $classes . 'class-wc-connect-tracks.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}

	/**
	 * Register the core route and the guard, and connect the site with an owner, terms not
	 * accepted.
	 *
	 * Overrides setUp() rather than set_up() because WC_REST_Unit_Test_Case only creates
	 * the REST server after the polyfilled set_up() has returned.
	 *
	 * @see WC_REST_Unit_Test_Case::setUp()
	 */
	public function setUp(): void {
		parent::setUp();

		// A server holding only the core options route.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		$this->server              = $GLOBALS['wp_rest_server'];
		( new Options() )->register_routes();

		// The loader adds this in pre_wc_init(); adding the same callback again is a no-op.
		add_filter( 'rest_dispatch_request', array( 'WC_Connect_Nux', 'restrict_tos_change_through_core_options' ), PHP_INT_MAX, 4 );

		$this->owner        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->other_admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->shop_manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );

		WCS_Test_Jetpack_Connection::connect( $this->owner );
		update_option( 'wc_connect_options', array( 'tos_accepted' => false ) );
		delete_option( 'woocommerce_setup_jetpack_opted_in' );
	}

	/**
	 * Undo the connection and the options.
	 */
	public function tear_down() {
		WCS_Test_Jetpack_Connection::reset();
		delete_option( 'wc_connect_options' );
		delete_option( 'woocommerce_setup_jetpack_opted_in' );
		unset( $GLOBALS['HTTP_RAW_POST_DATA'], $_SERVER['REQUEST_METHOD'], $_SERVER['CONTENT_TYPE'], $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] );

		parent::tear_down();
	}

	/**
	 * Send a JSON request to the options route as the current user.
	 *
	 * @param array  $params Options to write.
	 * @param string $method HTTP method.
	 * @param string $route  Route as requested.
	 * @return WP_REST_Response
	 */
	private function send( $params, $method = 'PUT', $route = self::ROUTE ) {
		$request = new WP_REST_Request( $method, $route );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $params ) );

		return $this->server->dispatch( $request );
	}

	/**
	 * Serve a JSON request the way WordPress serves an HTTP one, so the route comes from
	 * the path (as ?rest_route= passes it) and the method can be overridden by header.
	 *
	 * @param string      $path            Route as requested.
	 * @param string      $method          HTTP method.
	 * @param array       $params          Options to write.
	 * @param string|null $method_override X-HTTP-Method-Override header value.
	 * @return array{ status: int, data: mixed }
	 */
	private function serve( $path, $method, $params, $method_override = null ) {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- WP_REST_Server::get_raw_data() reads the body from here.
		$GLOBALS['HTTP_RAW_POST_DATA'] = wp_json_encode( $params );
		$_SERVER['REQUEST_METHOD']     = $method;
		$_SERVER['CONTENT_TYPE']       = 'application/json';
		if ( null !== $method_override ) {
			$_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] = $method_override;
		}

		$this->server->serve_request( $path );

		return array(
			'status' => $this->server->status,
			'data'   => json_decode( $this->server->sent_body, true ),
		);
	}

	/**
	 * Whether the terms are accepted.
	 *
	 * @return bool
	 */
	private function tos_accepted() {
		return (bool) WC_Connect_Options::get_option( 'tos_accepted' );
	}

	/**
	 * Run pre_wc_init() as the given user, as the start of their next request does.
	 *
	 * @param int $user_id User ID.
	 */
	private function run_next_request_as( $user_id ) {
		wp_set_current_user( $user_id );
		( new WC_Connect_Loader() )->pre_wc_init();
	}

	/**
	 * Assert a response is the guard's refusal.
	 *
	 * @param WP_REST_Response $response Response.
	 */
	private function assert_refused( $response ) {
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'wc_connect_tos_owner_only', $response->get_data()['code'] );
	}

	/**
	 * The loader registers the guard.
	 */
	public function test_loader_registers_the_guard() {
		$loader = new WC_Connect_Loader();
		remove_all_filters( 'rest_dispatch_request' );

		$loader->pre_wc_init();

		$this->assertSame( PHP_INT_MAX, has_filter( 'rest_dispatch_request', array( 'WC_Connect_Nux', 'restrict_tos_change_through_core_options' ) ) );
	}

	/**
	 * Users who are not the connection owner cannot accept the terms through the route.
	 *
	 * @return array
	 */
	public function provide_non_owners() {
		return array(
			'non-owner administrator' => array( 'other_admin' ),
			'shop manager'            => array( 'shop_manager' ),
		);
	}

	/**
	 * A non-owner cannot accept the terms through wc_connect_options.
	 *
	 * @dataProvider provide_non_owners
	 *
	 * @param string $user Property holding the user ID.
	 */
	public function test_non_owner_cannot_accept_tos( $user ) {
		wp_set_current_user( $this->$user );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) );

		$this->assert_refused( $response );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * A non-owner cannot set woocommerce_setup_jetpack_opted_in on a connected store, which
	 * pre_wc_init() would turn into an accepted ToS on the next request.
	 *
	 * @dataProvider provide_non_owners
	 *
	 * @param string $user Property holding the user ID.
	 */
	public function test_non_owner_cannot_opt_in_on_connected_store( $user ) {
		wp_set_current_user( $this->$user );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) );

		$this->assert_refused( $response );
		$this->assertFalse( get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * Values PHP reads as true are refused too.
	 *
	 * @return array
	 */
	public function provide_truthy_values() {
		return array(
			'string 1'     => array( '1' ),
			'integer 1'    => array( 1 ),
			'string false' => array( 'false' ),
		);
	}

	/**
	 * Truthy non-boolean acceptance values are refused.
	 *
	 * @dataProvider provide_truthy_values
	 *
	 * @param mixed $value Value sent for tos_accepted.
	 */
	public function test_non_owner_cannot_accept_with_truthy_value( $value ) {
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => $value ) ) );

		$this->assert_refused( $response );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * Truthy non-boolean opt-in values are refused.
	 *
	 * @dataProvider provide_truthy_values
	 *
	 * @param mixed $value Value sent for woocommerce_setup_jetpack_opted_in.
	 */
	public function test_non_owner_cannot_opt_in_with_truthy_value( $value ) {
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => $value ) );

		$this->assert_refused( $response );
		$this->assertFalse( get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * Payloads that would revoke accepted terms.
	 *
	 * @return array
	 */
	public function provide_revoking_payloads() {
		return array(
			'empty options'      => array( array() ),
			'tos_accepted false' => array( array( 'tos_accepted' => false ) ),
			'string'             => array( 'revoked' ),
			'null'               => array( null ),
		);
	}

	/**
	 * A non-owner cannot revoke accepted terms either.
	 *
	 * @dataProvider provide_revoking_payloads
	 *
	 * @param mixed $options Value sent for wc_connect_options.
	 */
	public function test_non_owner_cannot_revoke_tos( $options ) {
		update_option( 'wc_connect_options', array( 'tos_accepted' => true ) );
		wp_set_current_user( $this->other_admin );

		$response = $this->send( array( 'wc_connect_options' => $options ) );

		$this->assert_refused( $response );
		$this->assertTrue( $this->tos_accepted() );
	}

	/**
	 * The connection owner can accept the terms through the route.
	 */
	public function test_owner_can_accept_tos() {
		wp_set_current_user( $this->owner );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $this->tos_accepted() );
	}

	/**
	 * The connection owner can set the opt-in.
	 */
	public function test_owner_can_opt_in() {
		wp_set_current_user( $this->owner );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( (bool) get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * In offline mode there is no owner to wait for, so any shop manager may accept.
	 */
	public function test_anyone_can_accept_in_offline_mode() {
		WCS_Test_Jetpack_Connection::set_offline( true );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'wc_connect_options'                 => array( 'tos_accepted' => true ),
				'woocommerce_setup_jetpack_opted_in' => true,
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $this->tos_accepted() );
	}

	/**
	 * A non-owner cannot arm the after-connection banner, which accepts the terms when it
	 * renders for the owner.
	 */
	public function test_non_owner_cannot_arm_after_connection_banner() {
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'wc_connect_options' => array(
					'tos_accepted' => false,
					WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER => true,
				),
			)
		);

		$this->assert_refused( $response );
		$this->assertSame( array( 'tos_accepted' => false ), get_option( 'wc_connect_options' ) );

		// The owner's next admin page load must not accept the terms.
		$this->render_owner_banners();
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * A non-owner cannot change other keys either, such as the store GUID.
	 */
	public function test_non_owner_cannot_change_other_keys() {
		update_option(
			'wc_connect_options',
			array(
				'tos_accepted' => true,
				'store_guid'   => 'original-guid',
			)
		);
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'wc_connect_options' => array(
					'tos_accepted' => true,
					'store_guid'   => 'attacker-guid',
				),
			)
		);

		$this->assert_refused( $response );
		$this->assertSame( 'original-guid', get_option( 'wc_connect_options' )['store_guid'] );
	}

	/**
	 * Writing back the stored value unchanged, in any key order, is allowed.
	 */
	public function test_non_owner_identical_write_allowed() {
		update_option(
			'wc_connect_options',
			array(
				'tos_accepted' => true,
				'store_guid'   => 'original-guid',
				'nested'       => array(
					'a' => 1,
					'b' => 'x',
				),
			)
		);
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'wc_connect_options' => array(
					'nested'       => array(
						'b' => 'x',
						'a' => 1,
					),
					'store_guid'   => 'original-guid',
					'tos_accepted' => true,
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Writing back what a read returned is allowed when the stored value holds objects, as
	 * the cached payment methods of a store with saved cards do.
	 */
	public function test_non_owner_identical_write_with_stored_objects_allowed() {
		update_option(
			'wc_connect_options',
			array(
				'tos_accepted'    => true,
				'payment_methods' => array(
					(object) array(
						'payment_method_id' => 1,
						'name'              => 'Jane Doe',
						'card_type'         => 'visa',
						'card_digits'       => '4242',
						'expiry'            => '2030-01-31',
					),
				),
			)
		);
		wp_set_current_user( $this->shop_manager );

		// What a read of the option sends to the client.
		$as_read = json_decode( wp_json_encode( get_option( 'wc_connect_options' ) ), true );

		$response = $this->send( array( 'wc_connect_options' => $as_read ) );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * The connection owner can change other keys.
	 */
	public function test_owner_can_change_other_keys() {
		wp_set_current_user( $this->owner );

		$response = $this->send(
			array(
				'wc_connect_options' => array(
					'tos_accepted' => false,
					'store_guid'   => 'new-guid',
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'new-guid', get_option( 'wc_connect_options' )['store_guid'] );
	}

	/**
	 * In offline mode any shop manager can change other keys.
	 */
	public function test_anyone_can_change_other_keys_in_offline_mode() {
		WCS_Test_Jetpack_Connection::set_offline( true );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'wc_connect_options' => array(
					'tos_accepted' => false,
					'store_guid'   => 'new-guid',
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'new-guid', get_option( 'wc_connect_options' )['store_guid'] );
	}

	/**
	 * Load an admin page as the connection owner: set the banners up as admin_init does,
	 * then render admin_notices.
	 */
	private function render_owner_banners() {
		wp_set_current_user( $this->owner );
		update_option( 'woocommerce_default_country', 'US:CA' );
		set_current_screen( 'plugins' );

		$nux = ( new ReflectionClass( 'WC_Connect_Nux' ) )->newInstanceWithoutConstructor();

		// The banner that accepts the terms records a Tracks event; keep it off the network.
		$tracks   = $this->getMockBuilder( 'WC_Connect_Tracks' )
			->disableOriginalConstructor()
			->setMethods( array( 'opted_in' ) )
			->getMock();
		$property = new ReflectionProperty( 'WC_Connect_Nux', 'tracks' );
		$property->setAccessible( true );
		$property->setValue( $nux, $tracks );

		remove_all_actions( 'admin_notices' );
		$nux->set_up_nux_notices();

		ob_start();
		do_action( 'admin_notices' );
		ob_end_clean();

		set_current_screen( 'front' );
	}

	/**
	 * Without a connection the opt-in stays writable: WooCommerce sets it in its tax task
	 * before the store connects.
	 */
	public function test_opt_in_allowed_when_not_connected() {
		WCS_Test_Jetpack_Connection::reset();
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( (bool) get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * A site-only connection has no owner who could accept, so it is treated as not
	 * connected.
	 */
	public function test_opt_in_allowed_on_site_only_connection() {
		Jetpack_Options::delete_option( array( 'user_tokens', 'master_user' ) );
		WCS_Test_Jetpack_Connection::reset_caches();
		$this->assertTrue( WC_Connect_Jetpack::is_site_only_connection() );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( (bool) get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * On a connected store the opt-in a shop manager tries to set is refused, and when
	 * other code sets it (as WooCommerce does when Jetpack registers the site) their next
	 * request accepts nothing. The owner's opt-in, as WooCommerce's tax task sends it, is
	 * accepted on the owner's next request.
	 */
	public function test_opt_in_is_accepted_only_for_the_owner() {
		wp_set_current_user( $this->shop_manager );
		$this->assert_refused( $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) ) );

		update_option( 'woocommerce_setup_jetpack_opted_in', true );
		$this->run_next_request_as( $this->shop_manager );
		$this->assertFalse( $this->tos_accepted(), 'A shop manager request must not accept the terms.' );
		$this->assertTrue( (bool) get_option( 'woocommerce_setup_jetpack_opted_in' ), 'The opt-in must wait for the owner.' );

		delete_option( 'woocommerce_setup_jetpack_opted_in' );

		wp_set_current_user( $this->owner );
		$this->assertSame( 200, $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) )->get_status() );

		$this->run_next_request_as( $this->owner );
		$this->assertTrue( $this->tos_accepted(), 'The owner request must accept the terms.' );
		$this->assertFalse( (bool) get_option( 'woocommerce_setup_jetpack_opted_in' ), 'The accepted opt-in must be deleted.' );
	}

	/**
	 * An opt-in a shop manager sets before the store connects waits for the owner: no
	 * request accepts it until the store has an owner, and the owner's first request
	 * after connecting does.
	 */
	public function test_opt_in_set_before_connecting_waits_for_the_owner() {
		WCS_Test_Jetpack_Connection::reset();
		WCS_Test_Jetpack_Connection::set_offline( false );
		wp_set_current_user( $this->shop_manager );
		$this->assertSame( 200, $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) )->get_status() );

		$this->run_next_request_as( $this->owner );
		$this->assertFalse( $this->tos_accepted(), 'Without a connection nobody may accept the terms.' );

		WCS_Test_Jetpack_Connection::connect( $this->owner );
		$this->run_next_request_as( $this->owner );
		$this->assertTrue( $this->tos_accepted(), 'The owner request after connecting must accept the terms.' );
	}

	/**
	 * A falsy opt-in changes nothing pre_wc_init() acts on.
	 */
	public function test_falsy_opt_in_allowed() {
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => false ) );

		$this->assertSame( 200, $response->get_status() );
	}

	/**
	 * Other ways of reaching the same handler are refused too.
	 *
	 * @return array
	 */
	public function provide_route_variants() {
		return array(
			'mixed case route' => array( 'PUT', '/WC-Admin/Options' ),
			'POST'             => array( 'POST', self::ROUTE ),
		);
	}

	/**
	 * The handler is matched, not the route string.
	 *
	 * @dataProvider provide_route_variants
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route as requested.
	 */
	public function test_route_variants_refused( $method, $route ) {
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ), $method, $route );

		$this->assert_refused( $response );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * A request served the way rest_api_loaded() serves ?rest_route=, with the route in another case, is refused.
	 */
	public function test_rest_route_query_variant_refused() {
		wp_set_current_user( $this->shop_manager );

		$result = $this->serve( '/WC-Admin/Options', 'PUT', array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) );

		$this->assertSame( 403, $result['status'] );
		$this->assertSame( 'wc_connect_tos_owner_only', $result['data']['code'] );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * A POST overridden to PUT by header is refused.
	 */
	public function test_method_override_refused() {
		wp_set_current_user( $this->shop_manager );

		$result = $this->serve( self::ROUTE, 'POST', array( 'wc_connect_options' => array( 'tos_accepted' => true ) ), 'PUT' );

		$this->assertSame( 403, $result['status'] );
		$this->assertSame( 'wc_connect_tos_owner_only', $result['data']['code'] );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * A logged-out request gets WooCommerce's own error, which says nothing about the
	 * terms or the owner.
	 */
	public function test_logged_out_request_gets_core_error() {
		wp_set_current_user( 0 );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_update', $response->get_data()['code'] );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * So does a user without access to the route.
	 */
	public function test_subscriber_gets_core_error() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = $this->send( array( 'woocommerce_setup_jetpack_opted_in' => true ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'woocommerce_rest_cannot_update', $response->get_data()['code'] );
	}

	/**
	 * A result an earlier filter already returned is passed on unchanged, and the request
	 * is not run.
	 */
	public function test_earlier_dispatch_result_returned_unchanged() {
		$earlier = new WP_REST_Response( array( 'handled' => 'earlier' ), 202 );
		add_filter(
			'rest_dispatch_request',
			function () use ( $earlier ) {
				return $earlier;
			},
			5
		);
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertSame( array( 'handled' => 'earlier' ), $response->get_data() );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * A later filter that returns null whatever it is given cannot undo the refusal.
	 */
	public function test_later_filter_returning_null_does_not_undo_refusal() {
		remove_all_filters( 'rest_dispatch_request' );
		( new WC_Connect_Loader() )->pre_wc_init();
		add_filter( 'rest_dispatch_request', '__return_null', PHP_INT_MAX - 1 );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) );

		$this->assert_refused( $response );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * Reading the options is not affected.
	 */
	public function test_get_untouched() {
		wp_set_current_user( $this->shop_manager );

		$request = new WP_REST_Request( 'GET', self::ROUTE );
		$request->set_query_params( array( 'options' => 'wc_connect_options' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'tos_accepted' => false ), $response->get_data()['wc_connect_options'] );
	}

	/**
	 * A refused request writes none of its options.
	 */
	public function test_mixed_request_writes_nothing() {
		update_option( 'woocommerce_default_country', 'US:CA' );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'woocommerce_default_country' => 'GB',
				'wc_connect_options'          => array( 'tos_accepted' => true ),
			)
		);

		$this->assert_refused( $response );
		$this->assertSame( 'US:CA', get_option( 'woocommerce_default_country' ) );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * Spellings of the two option names that WooCommerce does not allow-list but that
	 * update_option() (which trims the name) and the database collation (which ignores case
	 * and accents) resolve to the real option.
	 *
	 * @return array
	 */
	public function provide_option_name_variants() {
		return array(
			'leading space'  => array( ' wc_connect_options', ' woocommerce_setup_jetpack_opted_in' ),
			'trailing space' => array( 'wc_connect_options ', 'woocommerce_setup_jetpack_opted_in ' ),
			'upper case'     => array( 'WC_CONNECT_OPTIONS', 'WOOCOMMERCE_SETUP_JETPACK_OPTED_IN' ),
			'accented'       => array( 'wc_cönnect_options', 'woocommerce_setup_jetpack_öpted_in' ),
			'zero-width'     => array( "wc_connect\u{200B}_options", "woocommerce_setup\u{200B}_jetpack_opted_in" ),
			'full-width'     => array( 'ｗｃ_connect_options', 'ｗｏｏｃｏｍｍｅｒｃｅ_setup_jetpack_opted_in' ),
			'full-width _'   => array( 'wc＿connect＿options', 'woocommerce＿setup＿jetpack＿opted＿in' ),
		);
	}

	/**
	 * Run the guard as WooCommerce dispatches the core options update.
	 *
	 * WooCommerce 11.1 lets an administrator write any option name it does not allow-list
	 * on a production site; later versions refuse such names in the route's permission
	 * check, before the guard runs. Calling the guard directly covers both.
	 *
	 * @param array $params Options to write.
	 * @return mixed The guard's result.
	 */
	private function run_guard( $params ) {
		$request = new WP_REST_Request( 'PUT', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $params ) );

		return WC_Connect_Nux::restrict_tos_change_through_core_options(
			null,
			$request,
			self::ROUTE,
			array( 'callback' => array( new Options(), 'update_options' ) )
		);
	}

	/**
	 * A non-owner cannot reach wc_connect_options or the opt-in under another spelling.
	 *
	 * @dataProvider provide_option_name_variants
	 *
	 * @param string $options_name Spelling of wc_connect_options.
	 * @param string $opt_in_name  Spelling of woocommerce_setup_jetpack_opted_in.
	 */
	public function test_non_owner_cannot_use_option_name_variant( $options_name, $opt_in_name ) {
		wp_set_current_user( $this->other_admin );

		$refused_options = $this->run_guard( array( $options_name => array( 'tos_accepted' => true ) ) );
		$refused_opt_in  = $this->run_guard( array( $opt_in_name => true ) );

		$this->assertWPError( $refused_options );
		$this->assertSame( 'wc_connect_tos_owner_only', $refused_options->get_error_code() );
		$this->assertWPError( $refused_opt_in );
		$this->assertSame( 'wc_connect_tos_owner_only', $refused_opt_in->get_error_code() );
	}

	/**
	 * Through the route, whichever of WooCommerce or the guard refuses it, a variant
	 * spelling leaves the terms and the opt-in unchanged.
	 *
	 * @dataProvider provide_option_name_variants
	 *
	 * @param string $options_name Spelling of wc_connect_options.
	 * @param string $opt_in_name  Spelling of woocommerce_setup_jetpack_opted_in.
	 */
	public function test_option_name_variant_through_route_changes_nothing( $options_name, $opt_in_name ) {
		// WooCommerce reports a name it does not allow-list as a deprecated use of the route.
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Admin\API\Options::update_options' );
		wp_set_current_user( $this->other_admin );

		$options_response = $this->send( array( $options_name => array( 'tos_accepted' => true ) ) );
		$opt_in_response  = $this->send( array( $opt_in_name => true ) );

		$this->assertSame( 403, $options_response->get_status() );
		$this->assertSame( 403, $opt_in_response->get_status() );
		$this->assertFalse( $this->tos_accepted() );
		$this->assertFalse( get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * The owner is not refused under a variant spelling.
	 */
	public function test_owner_not_refused_for_option_name_variant() {
		wp_set_current_user( $this->owner );

		$this->assertNull( $this->run_guard( array( 'WC_CONNECT_OPTIONS' => array( 'tos_accepted' => true ) ) ) );
	}

	/**
	 * An accented spelling is refused whatever the site language. For German,
	 * remove_accents() turns ö into oe, while the database collation still reads it as o.
	 */
	public function test_accented_option_name_refused_in_any_locale() {
		add_filter(
			'locale',
			function () {
				return 'de_DE';
			}
		);
		wp_set_current_user( $this->other_admin );

		$refused = $this->run_guard( array( 'wc_cönnect_options' => array( 'tos_accepted' => true ) ) );

		$this->assertWPError( $refused );
		$this->assertSame( 'wc_connect_tos_owner_only', $refused->get_error_code() );
	}

	/**
	 * A name that only contains one of the two names is not mistaken for it.
	 */
	public function test_unrelated_option_name_not_refused() {
		wp_set_current_user( $this->shop_manager );

		$this->assertNull( $this->run_guard( array( 'wc_connect_options_backup' => array( 'tos_accepted' => true ) ) ) );
	}

	/**
	 * Without an owner nobody may accept, so a non-owner's change to wc_connect_options is
	 * refused before the store connects too, alone or together with an opt-in that is
	 * allowed there.
	 *
	 * @return array
	 */
	public function provide_ownerless_wc_connect_options_payloads() {
		return array(
			'options alone'      => array( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) ),
			'options and opt-in' => array(
				array(
					'wc_connect_options'                 => array( 'tos_accepted' => true ),
					'woocommerce_setup_jetpack_opted_in' => true,
				),
			),
		);
	}

	/**
	 * Not connected.
	 *
	 * @dataProvider provide_ownerless_wc_connect_options_payloads
	 *
	 * @param array $params Options to write.
	 */
	public function test_wc_connect_options_change_refused_when_not_connected( $params ) {
		WCS_Test_Jetpack_Connection::reset();
		WCS_Test_Jetpack_Connection::set_offline( false );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( $params );

		$this->assert_refused( $response );
		$this->assertFalse( $this->tos_accepted() );
		$this->assertFalse( get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * Site-only connection.
	 *
	 * @dataProvider provide_ownerless_wc_connect_options_payloads
	 *
	 * @param array $params Options to write.
	 */
	public function test_wc_connect_options_change_refused_on_site_only_connection( $params ) {
		Jetpack_Options::delete_option( array( 'user_tokens', 'master_user' ) );
		WCS_Test_Jetpack_Connection::reset_caches();
		$this->assertTrue( WC_Connect_Jetpack::is_site_only_connection() );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( $params );

		$this->assert_refused( $response );
		$this->assertFalse( $this->tos_accepted() );
		$this->assertFalse( get_option( 'woocommerce_setup_jetpack_opted_in' ) );
	}

	/**
	 * The loader registers the guard on a store that is not connected, where the opt-in is
	 * commonly set.
	 */
	public function test_loader_registers_the_guard_when_not_connected() {
		WCS_Test_Jetpack_Connection::reset();
		WCS_Test_Jetpack_Connection::set_offline( false );
		remove_all_filters( 'rest_dispatch_request' );

		( new WC_Connect_Loader() )->pre_wc_init();

		$this->assertSame( PHP_INT_MAX, has_filter( 'rest_dispatch_request', array( 'WC_Connect_Nux', 'restrict_tos_change_through_core_options' ) ) );
	}

	/**
	 * Another route's handler is left alone, even when its body would be refused on the
	 * core options route.
	 */
	public function test_other_handler_untouched() {
		$this->server->register_route(
			'wcs-test/v1',
			'/wcs-test/v1/echo',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => function () {
						return array( 'handled' => true );
					},
					'permission_callback' => '__return_true',
				),
			)
		);
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ), 'PUT', '/wcs-test/v1/echo' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'handled' => true ), $response->get_data() );
	}

	/**
	 * Another controller's update_options method is left alone: only WooCommerce's options
	 * controller is matched.
	 */
	public function test_other_controller_update_options_untouched() {
		$controller = new class() {
			/**
			 * Echo handler.
			 *
			 * @return array
			 */
			public function update_options() {
				return array( 'handled' => true );
			}
		};
		$this->server->register_route(
			'wcs-test/v1',
			'/wcs-test/v1/options',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $controller, 'update_options' ),
					'permission_callback' => '__return_true',
				),
			)
		);
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ), 'PUT', '/wcs-test/v1/options' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( array( 'handled' => true ), $response->get_data() );
	}

	/**
	 * The core read handler is left alone when its request carries a JSON body.
	 */
	public function test_get_with_json_body_untouched() {
		wp_set_current_user( $this->shop_manager );

		$request = new WP_REST_Request( 'GET', self::ROUTE );
		$request->set_query_params( array( 'options' => 'wc_connect_options' ) );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'wc_connect_options' => array( 'tos_accepted' => true ) ) ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * Writing back an option that does not exist yet as an empty array is a change: the
	 * comparison is strict.
	 */
	public function test_loosely_equal_value_refused() {
		delete_option( 'wc_connect_options' );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send( array( 'wc_connect_options' => array() ) );

		$this->assert_refused( $response );
		$this->assertFalse( get_option( 'wc_connect_options' ) );
	}

	/**
	 * A JSON body that is not an object is left to WooCommerce, which writes nothing for it.
	 */
	public function test_non_object_json_body_left_to_woocommerce() {
		wp_set_current_user( $this->shop_manager );

		$request = new WP_REST_Request( 'PUT', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( '"wc_connect_options"' );

		$result = WC_Connect_Nux::restrict_tos_change_through_core_options(
			null,
			$request,
			self::ROUTE,
			array( 'callback' => array( new Options(), 'update_options' ) )
		);

		$this->assertNull( $result );
	}

	/**
	 * Legacy standalone options that WC_Connect_Options migrates into wc_connect_options,
	 * under their own and variant spellings.
	 *
	 * @return array
	 */
	public function provide_legacy_grouped_option_names() {
		return array(
			'terms acceptance'          => array( 'wc_connect_tos_accepted', true ),
			'after-connection banner'   => array( 'wc_connect_should_display_nux_after_jp_cxn_banner', true ),
			'store GUID'                => array( 'wc_connect_store_guid', 'attacker-guid' ),
			'upper case'                => array( 'WC_CONNECT_TOS_ACCEPTED', true ),
			'surrounded by white space' => array( ' wc_connect_tos_accepted ', true ),
		);
	}

	/**
	 * A non-owner cannot write a legacy wc_connect_<key> option, which the next read of that
	 * key copies into wc_connect_options (WOOTAX-451).
	 *
	 * @dataProvider provide_legacy_grouped_option_names
	 *
	 * @param string $name  Option name as sent.
	 * @param mixed  $value Value to write.
	 */
	public function test_non_owner_cannot_write_legacy_grouped_option( $name, $value ) {
		wp_set_current_user( $this->other_admin );

		$refused = $this->run_guard( array( $name => $value ) );

		$this->assertWPError( $refused );
		$this->assertSame( 'wc_connect_tos_owner_only', $refused->get_error_code() );
	}

	/**
	 * The payload from WOOTAX-451, sent before the terms are accepted, leaves them unaccepted
	 * after the next read migrates legacy options, whichever of WooCommerce or the guard
	 * refuses it.
	 */
	public function test_legacy_tos_option_through_route_does_not_accept_tos() {
		// WooCommerce reports a name it does not allow-list as a deprecated use of the route.
		$this->setExpectedDeprecated( 'Automattic\WooCommerce\Admin\API\Options::update_options' );
		delete_option( 'wc_connect_options' );
		wp_set_current_user( $this->other_admin );

		$response = $this->send( array( 'wc_connect_tos_accepted' => true ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertFalse( get_option( 'wc_connect_tos_accepted' ) );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * The owner may write a legacy option, as may anyone in offline mode.
	 */
	public function test_owner_and_offline_mode_can_write_legacy_grouped_option() {
		wp_set_current_user( $this->owner );
		$this->assertNull( $this->run_guard( array( 'wc_connect_tos_accepted' => true ) ) );

		WCS_Test_Jetpack_Connection::set_offline( true );
		wp_set_current_user( $this->shop_manager );
		$this->assertNull( $this->run_guard( array( 'wc_connect_tos_accepted' => true ) ) );
	}

	/**
	 * Writing back a legacy option's stored value, or false for one that is not stored, is
	 * allowed: a falsy legacy value is never migrated.
	 */
	public function test_non_owner_identical_legacy_write_allowed() {
		update_option( 'wc_connect_store_guid', 'original-guid' );
		wp_set_current_user( $this->other_admin );

		$this->assertNull( $this->run_guard( array( 'wc_connect_store_guid' => 'original-guid' ) ) );
		$this->assertNull( $this->run_guard( array( 'wc_connect_tos_accepted' => false ) ) );

		delete_option( 'wc_connect_store_guid' );
	}

	/**
	 * Names that are not a migrated legacy key are not refused: one that only starts with a
	 * legacy name, and a standalone option WC_Connect_Options reads directly.
	 */
	public function test_non_migrated_wc_connect_option_names_not_refused() {
		wp_set_current_user( $this->other_admin );

		$this->assertNull( $this->run_guard( array( 'wc_connect_tos_accepted_backup' => true ) ) );
		$this->assertNull( $this->run_guard( array( 'wc_connect_services' => array( 'a' => 1 ) ) ) );
	}
}
