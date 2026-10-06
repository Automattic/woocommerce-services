<?php
/**
 * Tests for WC_REST_Connect_Tos_Controller.
 *
 * @package WooCommerce_Services
 */

require_once __DIR__ . '/../class-wcs-test-jetpack-connection.php';

/**
 * Only a user who may accept the Terms of Service (the connection owner, or anyone in
 * offline mode) can read or accept them through the REST route.
 */
class WP_Test_WC_REST_Connect_Tos_Controller extends WC_REST_Unit_Test_Case {

	/**
	 * Route under test.
	 *
	 * @var string
	 */
	const ROUTE = '/wc/v1/connect/tos';

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
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$classes = __DIR__ . '/../../../classes/';
		require_once $classes . 'class-wc-connect-options.php';
		require_once $classes . 'class-wc-connect-nux.php';
		require_once $classes . 'class-wc-connect-api-client.php';
		require_once $classes . 'class-wc-connect-service-settings-store.php';
		require_once $classes . 'class-wc-connect-logger.php';
		require_once $classes . 'class-wc-rest-connect-base-controller.php';
		require_once $classes . 'class-wc-rest-connect-tos-controller.php';
	}

	/**
	 * Register the route and connect the site with an owner, terms not accepted.
	 *
	 * Overrides setUp() rather than set_up() because WC_REST_Unit_Test_Case only creates
	 * the REST server after the polyfilled set_up() has returned.
	 *
	 * @see WC_REST_Unit_Test_Case::setUp()
	 */
	public function setUp(): void {
		parent::setUp();

		// WC_REST_Unit_Test_Case::setUp() already installs a spy server with every route.
		// This one holds only the route under test.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		$this->server              = $GLOBALS['wp_rest_server'];
		$this->register_routes();

		$this->owner       = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->other_admin = self::factory()->user->create( array( 'role' => 'administrator' ) );

		WCS_Test_Jetpack_Connection::connect( $this->owner );
		WC_Connect_Options::update_option( 'tos_accepted', false );
	}

	/**
	 * Undo the connection and the acceptance.
	 */
	public function tear_down() {
		WCS_Test_Jetpack_Connection::reset();
		WC_Connect_Options::delete_option( 'tos_accepted' );

		parent::tear_down();
	}

	/**
	 * Register the controller under test.
	 */
	public function register_routes() {
		( new WC_REST_Connect_Tos_Controller(
			$this->createMock( WC_Connect_API_Client::class ),
			$this->createMock( WC_Connect_Service_Settings_Store::class ),
			$this->createMock( WC_Connect_Logger::class )
		) )->register_routes();
	}

	/**
	 * Skip a test that needs a site administrator to pass the route's capability check.
	 */
	private function skip_on_multisite() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'On multisite install_plugins and activate_plugins are super admin capabilities, so a site administrator never passes the capability check on this route.' );
		}
	}

	/**
	 * POST an acceptance as the current user.
	 *
	 * @return WP_REST_Response
	 */
	private function post_acceptance() {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'accepted' => true ) ) );

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
	 * An administrator who is not the connection owner cannot accept the terms.
	 */
	public function test_non_owner_admin_cannot_accept() {
		wp_set_current_user( $this->other_admin );

		$response = $this->post_acceptance();

		$this->assertSame( 403, $response->get_status() );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * The connection owner can.
	 */
	public function test_owner_can_accept() {
		$this->skip_on_multisite();
		wp_set_current_user( $this->owner );

		$response = $this->post_acceptance();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $this->tos_accepted() );
	}

	/**
	 * The read shares the check: a non-owner administrator cannot read the terms state.
	 */
	public function test_non_owner_admin_cannot_read() {
		wp_set_current_user( $this->other_admin );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * The connection owner can read it.
	 */
	public function test_owner_can_read() {
		$this->skip_on_multisite();
		wp_set_current_user( $this->owner );

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', self::ROUTE ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( $response->get_data()['accepted'] );
	}

	/**
	 * In offline mode there is no owner to wait for, so any administrator may accept.
	 */
	public function test_any_admin_can_accept_in_offline_mode() {
		$this->skip_on_multisite();
		WCS_Test_Jetpack_Connection::set_offline( true );
		wp_set_current_user( $this->other_admin );

		$response = $this->post_acceptance();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $this->tos_accepted() );
	}

	/**
	 * The capability checks still apply: a shop manager cannot install plugins.
	 */
	public function test_shop_manager_cannot_accept() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$response = $this->post_acceptance();

		$this->assertSame( 403, $response->get_status() );
		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * Nobody logged in: refused as unauthenticated.
	 */
	public function test_anonymous_cannot_accept() {
		wp_set_current_user( 0 );

		$response = $this->post_acceptance();

		$this->assertSame( 401, $response->get_status() );
		$this->assertFalse( $this->tos_accepted() );
	}
}
