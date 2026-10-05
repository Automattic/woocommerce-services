<?php
/**
 * Tests for WC_Connect_Loader::log_rest_api_errors().
 *
 * @package WooCommerce_Services
 */

/**
 * The filter runs before the route's permission check, so it must log only what the
 * route allows, never touch the admin error notice, and keep the logged body short.
 */
class WP_Test_WC_Connect_Loader_Rest_Error_Log extends WC_REST_Unit_Test_Case {

	/**
	 * Loader under test, built without its constructor.
	 *
	 * @var WC_Connect_Loader
	 */
	private $loader;

	/**
	 * Messages written to the WooCommerce log.
	 *
	 * @var string[]
	 */
	private $logged = array();

	/**
	 * Notice stored before each test, which none of them may replace.
	 *
	 * @var WP_Error
	 */
	private $stored_notice;

	/**
	 * Previous error_log destination, restored after each test.
	 *
	 * @var string|false
	 */
	private $previous_error_log;

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$classes = __DIR__ . '/../../classes/';
		require_once $classes . 'class-wc-connect-options.php';
		require_once $classes . 'class-wc-connect-logger.php';
		require_once $classes . 'class-wc-connect-error-notice.php';
		require_once $classes . 'class-wc-connect-api-client.php';
		require_once $classes . 'class-wc-connect-service-settings-store.php';
		require_once $classes . 'class-wc-connect-shipping-label.php';
		require_once $classes . 'class-wc-connect-payment-methods-store.php';
		require_once $classes . 'class-wc-rest-connect-base-controller.php';
		require_once $classes . 'class-wc-rest-connect-self-help-controller.php';
		require_once $classes . 'class-wc-rest-connect-tos-controller.php';
		require_once $classes . 'class-wc-rest-connect-shipping-label-eligibility-controller.php';
	}

	/**
	 * Register the routes, attach the filter and store a notice.
	 */
	public function set_up() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		parent::set_up();

		// log() also writes to error_log() under WP_DEBUG; keep that out of the test output.
		$this->previous_error_log = ini_set( 'error_log', '/dev/null' ); // phpcs:ignore WordPress.PHP.IniSet.Risky

		$this->loader = ( new ReflectionClass( WC_Connect_Loader::class ) )->newInstanceWithoutConstructor();
		add_filter( 'rest_request_before_callbacks', array( $this->loader, 'log_rest_api_errors' ), 10, 3 );

		$this->stored_notice = new WP_Error( 'product_missing_weight', 'Missing weight.', array( 'product_id' => 1 ) );
		WC_Connect_Options::update_option( 'error_notice', $this->stored_notice );

		$this->use_logger( false );
	}

	/**
	 * Restore error_log and the options touched.
	 */
	public function tear_down() {
		ini_set( 'error_log', (string) $this->previous_error_log ); // phpcs:ignore WordPress.PHP.IniSet.Risky
		remove_filter( 'rest_request_before_callbacks', array( $this->loader, 'log_rest_api_errors' ), 10 );
		WC_Connect_Options::delete_option( 'error_notice' );
		WC_Connect_Options::delete_option( 'debug_logging_enabled' );

		parent::tear_down();
	}

	/**
	 * Register the Connect routes the tests call.
	 */
	public function register_routes() {
		$api_client     = $this->createMock( WC_Connect_API_Client::class );
		$settings_store = $this->createMock( WC_Connect_Service_Settings_Store::class );
		$logger         = $this->createMock( WC_Connect_Logger::class );

		( new WC_REST_Connect_Self_Help_Controller( $api_client, $settings_store, $logger ) )->register_routes();
		( new WC_REST_Connect_Tos_Controller( $api_client, $settings_store, $logger ) )->register_routes();
		( new WC_REST_Connect_Shipping_Label_Eligibility_Controller(
			$api_client,
			$settings_store,
			$logger,
			$this->createMock( WC_Connect_Shipping_Label::class ),
			$this->createMock( WC_Connect_Payment_Methods_Store::class ),
			false
		) )->register_routes();
	}

	/**
	 * Give the loader a logger that records what reaches the WooCommerce log.
	 *
	 * @param bool $logging_enabled Whether the merchant turned debug logging on.
	 */
	private function use_logger( $logging_enabled ) {
		WC_Connect_Options::update_option( 'debug_logging_enabled', $logging_enabled );

		$this->logged = array();
		$wc_logger    = $this->createMock( WC_Logger::class );
		$wc_logger->method( 'add' )->willReturnCallback(
			function ( $handle, $message ) {
				$this->logged[] = $message;
				return true;
			}
		);

		$this->loader->set_logger( new WC_Connect_Logger( $wc_logger ) );
	}

	/**
	 * A POST with a body that is not JSON.
	 *
	 * @param string $route Route to post to.
	 * @param string $body  Request body.
	 * @return WP_REST_Response
	 */
	private function post_malformed_json( $route, $body = "{bad\nFAKE LOG LINE" ) {
		$request = new WP_REST_Request( 'POST', $route );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( $body );

		return $this->server->dispatch( $request );
	}

	/**
	 * Assert the notice stored in set_up() is still the stored one.
	 */
	private function assert_notice_untouched() {
		$notice = WC_Connect_Options::get_option( 'error_notice' );

		$this->assertInstanceOf( WP_Error::class, $notice );
		$this->assertSame( 'product_missing_weight', $notice->get_error_code() );
	}

	/**
	 * An anonymous malformed body writes nothing and keeps the notice.
	 */
	public function test_anonymous_malformed_body_is_not_logged() {
		wp_set_current_user( 0 );

		$response = $this->post_malformed_json( '/wc/v1/connect/self-help' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'rest_invalid_json', $response->get_data()['code'] );
		$this->assertSame( array(), $this->logged );
		$this->assert_notice_untouched();
	}

	/**
	 * A logged-in user the route does not allow writes nothing either.
	 */
	public function test_customer_malformed_body_is_not_logged() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$this->post_malformed_json( '/wc/v1/connect/self-help' );

		$this->assertSame( array(), $this->logged );
		$this->assert_notice_untouched();
	}

	/**
	 * With debug logging off, an allowed request logs the error line only.
	 */
	public function test_allowed_request_logs_the_error_line_without_the_body() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$this->post_malformed_json( '/wc/v1/connect/self-help' );

		$this->assertCount( 1, $this->logged );
		$this->assertStringStartsWith( 'rest_invalid_json ', $this->logged[0] );
		$this->assertStringEndsWith( ' (POST /wc/v1/connect/self-help)', $this->logged[0] );
		$this->assertStringNotContainsString( 'FAKE LOG LINE', $this->logged[0] );
		$this->assert_notice_untouched();
	}

	/**
	 * With debug logging on, the body is added, escaped and cut to the limit.
	 */
	public function test_allowed_request_logs_a_short_escaped_body_when_logging_is_on() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->use_logger( true );

		$body = "{bad\n" . str_repeat( 'x', 5000 );
		$this->post_malformed_json( '/wc/v1/connect/self-help', $body );

		$this->assertCount( 2, $this->logged );
		$this->assertStringStartsWith( 'rest_invalid_json ', $this->logged[0] );
		$this->assertSame(
			'POST /wc/v1/connect/self-help (body, 5005 bytes: "{bad\n' . str_repeat( 'x', 1019 ) . '"...)',
			$this->logged[1]
		);
		foreach ( $this->logged as $message ) {
			$this->assertStringNotContainsString( "\n", $message );
		}
		$this->assert_notice_untouched();
	}

	/**
	 * An invalid parameter on a route that declares args is logged for an allowed user.
	 */
	public function test_allowed_request_with_an_invalid_param_is_logged() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/creation_eligibility' );
		$request->set_query_params( array( 'order_id' => 'abc' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertCount( 1, $this->logged );
		$this->assertStringStartsWith( 'rest_invalid_param ', $this->logged[0] );
		$this->assert_notice_untouched();
	}

	/**
	 * The same invalid parameter from an anonymous request is not logged.
	 */
	public function test_anonymous_request_with_an_invalid_param_is_not_logged() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/creation_eligibility' );
		$request->set_query_params( array( 'order_id' => 'abc' ) );
		$response = $this->server->dispatch( $request );

		$this->assertSame( 'rest_invalid_param', $response->get_data()['code'] );
		$this->assertSame( array(), $this->logged );
		$this->assert_notice_untouched();
	}

	/**
	 * The route's own check decides: a shop manager may not use the terms route.
	 */
	public function test_request_the_route_itself_denies_is_not_logged() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->assertTrue( current_user_can( 'manage_woocommerce' ) );
		$this->assertFalse( current_user_can( 'install_plugins' ) );

		$this->post_malformed_json( '/wc/v1/connect/tos' );

		$this->assertSame( array(), $this->logged );
		$this->assert_notice_untouched();
	}

	/**
	 * A missing or non-callable permission check counts as denied.
	 */
	public function test_handler_without_a_callable_permission_check_is_not_logged() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$error   = new WP_Error( 'rest_invalid_json', 'Invalid JSON body passed.' );
		$request = new WP_REST_Request( 'POST', '/wc/v1/connect/self-help' );

		$this->assertSame( $error, $this->loader->log_rest_api_errors( $error, array( 'permission_callback' => 'wcs_test_no_such_function' ), $request ) );
		$this->assertSame( $error, $this->loader->log_rest_api_errors( $error, array(), $request ) );
		$this->assertSame( $error, $this->loader->log_rest_api_errors( $error, null, $request ) );

		$this->assertSame( array(), $this->logged );
	}

	/**
	 * Routes outside the Connect namespace are left alone.
	 */
	public function test_route_outside_connect_is_not_logged() {
		$error   = new WP_Error( 'rest_invalid_json', 'Invalid JSON body passed.' );
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );

		$this->assertSame( $error, $this->loader->log_rest_api_errors( $error, array( 'permission_callback' => '__return_true' ), $request ) );
		$this->assertSame( array(), $this->logged );
	}

	/**
	 * The filter hands back what it was given, logged or not.
	 */
	public function test_response_is_passed_through() {
		$request = new WP_REST_Request( 'POST', '/wc/v1/connect/self-help' );
		$error   = new WP_Error( 'rest_invalid_json', 'Invalid JSON body passed.' );
		$ok      = new WP_REST_Response( array( 'ok' => true ) );

		$this->assertSame( $error, $this->loader->log_rest_api_errors( $error, array( 'permission_callback' => '__return_true' ), $request ) );
		$this->assertSame( $error, $this->loader->log_rest_api_errors( $error, array( 'permission_callback' => '__return_false' ), $request ) );
		$this->assertSame( $ok, $this->loader->log_rest_api_errors( $ok, array( 'permission_callback' => '__return_true' ), $request ) );
		$this->assertCount( 1, $this->logged );
	}

	/**
	 * A route ending in a line break still matches ("$" allows it) and is logged escaped.
	 */
	public function test_route_with_a_line_break_is_logged_escaped() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$response = $this->post_malformed_json( "/wc/v1/connect/self-help\n" );

		$this->assertSame( 'rest_invalid_json', $response->get_data()['code'] );
		$this->assertCount( 1, $this->logged );
		$this->assertStringEndsWith( ' (POST /wc/v1/connect/self-help\n)', $this->logged[0] );
		$this->assertStringNotContainsString( "\n", $this->logged[0] );
	}

	/**
	 * A carriage return in the route cannot start a line of its own in the log.
	 */
	public function test_route_with_a_carriage_return_is_logged_escaped() {
		$this->use_logger( true );
		$request = new WP_REST_Request( 'POST', "/wc/v1/connect/self-help\rFAKE LOG LINE" );
		$request->set_body( '{bad' );

		$this->loader->log_rest_api_errors( new WP_Error( 'rest_invalid_json', 'Invalid JSON body passed.' ), array( 'permission_callback' => '__return_true' ), $request );

		$this->assertCount( 2, $this->logged );
		$this->assertStringEndsWith( ' (POST /wc/v1/connect/self-help\rFAKE LOG LINE)', $this->logged[0] );
		$this->assertStringStartsWith( 'POST /wc/v1/connect/self-help\rFAKE LOG LINE (body, 4 bytes: ', $this->logged[1] );
		foreach ( $this->logged as $message ) {
			$this->assertStringNotContainsString( "\r", $message );
		}
	}

	/**
	 * The cut at the limit never splits a multibyte character.
	 */
	public function test_body_is_not_cut_inside_a_multibyte_character() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->use_logger( true );

		// 1023 bytes, then a 3-byte euro sign across the 1024-byte limit.
		$body = '{bad' . str_repeat( 'x', 1019 ) . "\xE2\x82\xAC" . str_repeat( 'y', 10 );
		$this->post_malformed_json( '/wc/v1/connect/self-help', $body );

		$this->assertCount( 2, $this->logged );
		$this->assertSame(
			'POST /wc/v1/connect/self-help (body, 1036 bytes: "{bad' . str_repeat( 'x', 1019 ) . '"...)',
			$this->logged[1]
		);
	}

	/**
	 * Invalid UTF-8 is replaced rather than dropping the whole excerpt.
	 */
	public function test_invalid_utf8_in_the_body_is_substituted() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->use_logger( true );

		$this->post_malformed_json( '/wc/v1/connect/self-help', "{bad\xFF\xFE" );

		$this->assertCount( 2, $this->logged );
		$this->assertSame(
			'POST /wc/v1/connect/self-help (body, 6 bytes: "{bad' . "\xEF\xBF\xBD\xEF\xBF\xBD" . '")',
			$this->logged[1]
		);
	}
}
