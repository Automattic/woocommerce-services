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
		add_filter( 'rest_request_before_callbacks', array( 'WC_Connect_Nux', 'restrict_tos_change_through_core_options' ), 10, 3 );

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
	 * Whether the terms are accepted.
	 *
	 * @return bool
	 */
	private function tos_accepted() {
		return (bool) WC_Connect_Options::get_option( 'tos_accepted' );
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
		remove_all_filters( 'rest_request_before_callbacks' );

		$loader->pre_wc_init();

		$this->assertSame( 10, has_filter( 'rest_request_before_callbacks', array( 'WC_Connect_Nux', 'restrict_tos_change_through_core_options' ) ) );
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
	 * A non-owner may still change other keys while leaving the acceptance as it is.
	 */
	public function test_non_owner_can_change_other_keys() {
		update_option( 'wc_connect_options', array( 'tos_accepted' => true ) );
		wp_set_current_user( $this->shop_manager );

		$response = $this->send(
			array(
				'wc_connect_options' => array(
					'tos_accepted' => true,
					'other_key'    => 'value',
				),
			)
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'value', get_option( 'wc_connect_options' )['other_key'] );
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
}
