<?php

require_once __DIR__ . '/class-wcs-test-nux-redirect.php';
require_once __DIR__ . '/class-wcs-test-jetpack-connection.php';

class WP_Test_WC_Connect_NUX extends WC_Unit_Test_Case {

	/**
	 * Request URI the banner links are assumed to have been clicked from.
	 *
	 * @var string
	 */
	const REQUEST_URI = '/wp-admin/plugins.php';

	/**
	 * The request URI to put back after each test.
	 *
	 * @var string|null
	 */
	private $original_request_uri;

	/**
	 * The store country to put back after each test.
	 *
	 * @var mixed
	 */
	private $original_default_country;

	public static function set_up_before_class() {
		require_once __DIR__ . '/../../classes/class-wc-connect-nux.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-options.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';
	}

	/**
	 * Give the banner handlers a request to build their links and redirects from, and
	 * turn the redirect that ends a verified action into an exception.
	 *
	 * The handlers call exit immediately after redirecting. Left alone that ends the
	 * PHPUnit process with status 0, so an action that got through the nonce check
	 * would end the run green instead of failing it. Throwing from the wp_redirect
	 * filter unwinds before the exit is reached, which both keeps the rejection tests
	 * honest and makes the success path assertable.
	 */
	public function set_up() {
		parent::set_up();

		$this->original_request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: null;
		$_SERVER['REQUEST_URI']     = self::REQUEST_URI;

		$this->original_default_country = get_option( 'woocommerce_default_country' );

		add_filter( 'wp_redirect', array( $this, 'throw_on_redirect' ) );
	}

	/**
	 * Stand in for the redirect that ends a verified banner action.
	 *
	 * @param string $location Redirect target.
	 *
	 * @throws WCS_Test_Nux_Redirect Always.
	 */
	public function throw_on_redirect( $location ) {
		throw new WCS_Test_Nux_Redirect( esc_html( $location ) );
	}

	/**
	 * Put an admin on the Plugins page of a US store, where the banners render.
	 *
	 * The site is connected with an administrator as the Jetpack connection owner. By default
	 * the current user is that owner; otherwise it is a second administrator.
	 *
	 * @param bool $as_owner Whether the current user is the connection owner.
	 * @return WC_Connect_Nux
	 */
	private function arm_banner( $as_owner = true ) {
		$owner = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		WCS_Test_Jetpack_Connection::connect( $owner );

		wp_set_current_user( $as_owner ? $owner : $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		update_option( 'woocommerce_default_country', 'US:CA' );
		set_current_screen( 'plugins' );

		$nux = $this->get_nux();

		// The verified paths record a Tracks event; keep that from reaching the network.
		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )
			->disableOriginalConstructor()
			->setMethods( array( 'opted_in' ) )
			->getMock();

		$property = new ReflectionProperty( 'WC_Connect_Nux', 'tracks' );
		$property->setAccessible( true );
		$property->setValue( $nux, $tracks );

		return $nux;
	}

	/**
	 * Run a banner handler, swallowing the banner markup it prints when it does not redirect.
	 *
	 * @param WC_Connect_Nux $nux    Instance under test.
	 * @param string         $method Handler to call.
	 * @return array{ output: string, redirect: string|null }
	 */
	private function run_banner( $nux, $method ) {
		$redirect = null;

		ob_start();
		try {
			$nux->$method();
		} catch ( WCS_Test_Nux_Redirect $e ) {
			$redirect = $e->getMessage();
		}
		$output = ob_get_clean();

		return array(
			'output'   => $output,
			'redirect' => $redirect,
		);
	}

	/**
	 * Set the banners up as admin_init does, then render admin_notices.
	 *
	 * @param WC_Connect_Nux $nux Instance under test.
	 * @return array{ output: string, redirect: string|null }
	 */
	private function run_admin_notices( $nux ) {
		remove_all_actions( 'admin_notices' );
		$nux->set_up_nux_notices();

		$redirect = null;

		ob_start();
		try {
			do_action( 'admin_notices' );
		} catch ( WCS_Test_Nux_Redirect $e ) {
			$redirect = $e->getMessage();
		}
		$output = ob_get_clean();

		return array(
			'output'   => $output,
			'redirect' => $redirect,
		);
	}

	/**
	 * The rendered "Connect" link carries a nonce for the accept action.
	 */
	public function test_tos_banner_link_carries_a_nonce() {
		$nux    = $this->arm_banner();
		$result = $this->run_banner( $nux, 'show_tos_banner' );

		$this->assertNull( $result['redirect'], 'Rendering the banner must not redirect.' );
		$this->assertStringContainsString( 'wcs-nux-tos=accept', $result['output'] );
		$this->assertMatchesRegularExpression( '/_wpnonce=[0-9a-f]{10}/', $result['output'] );
	}

	/**
	 * Loading the accept URL without a nonce must not accept the Terms of Service.
	 */
	public function test_tos_acceptance_without_nonce_is_ignored() {
		$nux = $this->arm_banner();

		$_GET['wcs-nux-tos'] = 'accept';

		$result = $this->run_banner( $nux, 'show_tos_banner' );

		$this->assertNull( $result['redirect'] );
		$this->assertFalse( WC_Connect_Options::get_option( 'tos_accepted', false ) );
		$this->assertStringContainsString( 'wcs-nux__notice', $result['output'], 'The banner should render again.' );
	}

	/**
	 * A forged nonce must not accept the Terms of Service either.
	 */
	public function test_tos_acceptance_with_invalid_nonce_is_ignored() {
		$nux = $this->arm_banner();

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = 'not-a-valid-nonce';

		$result = $this->run_banner( $nux, 'show_tos_banner' );

		$this->assertNull( $result['redirect'] );
		$this->assertFalse( WC_Connect_Options::get_option( 'tos_accepted', false ) );
	}

	/**
	 * A nonce minted for the other banner action must not be accepted here.
	 */
	public function test_tos_acceptance_with_nonce_for_another_action_is_ignored() {
		$nux = $this->arm_banner();

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = wp_create_nonce( WC_Connect_Nux::DISMISS_AFTER_CXN_BANNER_NONCE_ACTION );

		$result = $this->run_banner( $nux, 'show_tos_banner' );

		$this->assertNull( $result['redirect'] );
		$this->assertFalse( WC_Connect_Options::get_option( 'tos_accepted', false ) );
	}

	/**
	 * With a valid nonce the Terms of Service are accepted and the admin is sent back
	 * to a URL carrying neither the action nor the nonce.
	 */
	public function test_tos_acceptance_with_valid_nonce_accepts_and_redirects() {
		$nux = $this->arm_banner();

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = wp_create_nonce( WC_Connect_Nux::ACCEPT_TOS_NONCE_ACTION );

		$result = $this->run_banner( $nux, 'show_tos_banner' );

		$this->assertNotNull( $result['redirect'], 'A verified acceptance should redirect.' );
		$this->assertStringNotContainsString( 'wcs-nux-tos', $result['redirect'] );
		$this->assertStringNotContainsString( '_wpnonce', $result['redirect'] );
		$this->assertTrue( WC_Connect_Options::get_option( 'tos_accepted', false ) );
	}

	/**
	 * The rendered "Got it, thanks!" link carries a nonce for the dismiss action.
	 */
	public function test_after_connection_banner_link_carries_a_nonce() {
		$nux = $this->arm_banner();
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$result = $this->run_banner( $nux, 'show_banner_after_connection' );

		$this->assertNull( $result['redirect'], 'Rendering the banner must not redirect.' );
		$this->assertStringContainsString( 'wcs-nux-notice=dismiss', $result['output'] );
		$this->assertMatchesRegularExpression( '/_wpnonce=[0-9a-f]{10}/', $result['output'] );
	}

	/**
	 * Loading the dismiss URL without a nonce must leave the banner armed.
	 */
	public function test_after_connection_banner_dismissal_without_nonce_is_ignored() {
		$nux = $this->arm_banner();
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$_GET['wcs-nux-notice'] = 'dismiss';

		$result = $this->run_banner( $nux, 'show_banner_after_connection' );

		$this->assertNull( $result['redirect'] );
		$this->assertTrue( WC_Connect_Options::get_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, false ) );
	}

	/**
	 * With a valid nonce the banner is dismissed and the admin is sent back to a URL
	 * carrying neither the action nor the nonce.
	 */
	public function test_after_connection_banner_dismissal_with_valid_nonce_dismisses_and_redirects() {
		$nux = $this->arm_banner();
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$_GET['wcs-nux-notice'] = 'dismiss';
		$_GET['_wpnonce']       = wp_create_nonce( WC_Connect_Nux::DISMISS_AFTER_CXN_BANNER_NONCE_ACTION );

		$result = $this->run_banner( $nux, 'show_banner_after_connection' );

		$this->assertNotNull( $result['redirect'], 'A verified dismissal should redirect.' );
		$this->assertStringNotContainsString( 'wcs-nux-notice', $result['redirect'] );
		$this->assertStringNotContainsString( '_wpnonce', $result['redirect'] );
		$this->assertFalse( WC_Connect_Options::get_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, false ) );
	}

	/**
	 * A non-owner admin never gets the accept link, so even a valid accept nonce minted for
	 * them does not accept the terms.
	 */
	public function test_non_owner_cannot_accept_through_the_tos_link() {
		$nux = $this->arm_banner( false );
		WC_Connect_Options::update_option( 'tos_accepted', false );

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = wp_create_nonce( WC_Connect_Nux::ACCEPT_TOS_NONCE_ACTION );

		$result = $this->run_admin_notices( $nux );

		$this->assertFalse( has_action( 'admin_notices', array( $nux, 'show_tos_banner' ) ) );
		$this->assertNotFalse( has_action( 'admin_notices', array( $nux, 'show_tos_informational_banner' ) ) );
		$this->assertNull( $result['redirect'] );
		$this->assertFalse( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
	}

	/**
	 * The connection owner accepts through the same link.
	 */
	public function test_owner_accepts_through_the_tos_link() {
		$nux = $this->arm_banner();
		WC_Connect_Options::update_option( 'tos_accepted', false );

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = wp_create_nonce( WC_Connect_Nux::ACCEPT_TOS_NONCE_ACTION );

		$result = $this->run_admin_notices( $nux );

		$this->assertNotNull( $result['redirect'] );
		$this->assertTrue( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
	}

	/**
	 * Called directly for a non-owner admin with a valid nonce, the accept handler does not
	 * accept the terms and shows the informational banner instead.
	 */
	public function test_tos_handler_does_not_accept_for_a_non_owner() {
		$nux = $this->arm_banner( false );
		WC_Connect_Options::update_option( 'tos_accepted', false );

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = wp_create_nonce( WC_Connect_Nux::ACCEPT_TOS_NONCE_ACTION );

		$result = $this->run_banner( $nux, 'show_tos_banner' );

		$this->assertNull( $result['redirect'] );
		$this->assertFalse( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
		$this->assertStringContainsString( 'needs to accept the Terms of Service', $result['output'] );
	}

	/**
	 * In offline mode there is no owner to wait for, so any admin gets the link and can accept.
	 */
	public function test_offline_mode_lets_any_admin_accept() {
		$nux = $this->arm_banner( false );
		WCS_Test_Jetpack_Connection::set_offline( true );
		WC_Connect_Options::update_option( 'tos_accepted', false );

		$_GET['wcs-nux-tos'] = 'accept';
		$_GET['_wpnonce']    = wp_create_nonce( WC_Connect_Nux::ACCEPT_TOS_NONCE_ACTION );

		$result = $this->run_admin_notices( $nux );

		$this->assertNotFalse( has_action( 'admin_notices', array( $nux, 'show_tos_banner' ) ) );
		$this->assertNotNull( $result['redirect'] );
		$this->assertTrue( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
	}

	/**
	 * A non-owner admin in the after-connection state gets the informational banner, and
	 * loading the page does not accept the terms.
	 */
	public function test_non_owner_after_connection_gets_the_informational_banner() {
		$nux = $this->arm_banner( false );
		WC_Connect_Options::update_option( 'tos_accepted', false );
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$result = $this->run_admin_notices( $nux );

		$this->assertFalse( has_action( 'admin_notices', array( $nux, 'show_banner_after_connection' ) ) );
		$this->assertNotFalse( has_action( 'admin_notices', array( $nux, 'show_tos_informational_banner' ) ) );
		$this->assertStringContainsString( 'needs to accept the Terms of Service', $result['output'] );
		$this->assertFalse( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
		$this->assertTrue( (bool) WC_Connect_Options::get_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER ), 'The owner still gets the banner later.' );
	}

	/**
	 * Once the owner accepted, a non-owner admin in the after-connection state gets no banner.
	 */
	public function test_non_owner_after_connection_with_terms_accepted_gets_no_banner() {
		$nux = $this->arm_banner( false );
		WC_Connect_Options::update_option( 'tos_accepted', true );
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$this->run_admin_notices( $nux );

		$this->assertFalse( has_action( 'admin_notices', array( $nux, 'show_banner_after_connection' ) ) );
		$this->assertFalse( has_action( 'admin_notices', array( $nux, 'show_tos_informational_banner' ) ) );
		$this->assertFalse( has_action( 'admin_notices', array( $nux, 'show_tos_banner' ) ) );
	}

	/**
	 * The connection owner still accepts the terms by seeing the after-connection banner.
	 */
	public function test_owner_after_connection_accepts_on_render() {
		$nux = $this->arm_banner();
		WC_Connect_Options::update_option( 'tos_accepted', false );
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$result = $this->run_admin_notices( $nux );

		$this->assertNotFalse( has_action( 'admin_notices', array( $nux, 'show_banner_after_connection' ) ) );
		$this->assertNull( $result['redirect'] );
		$this->assertTrue( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
	}

	/**
	 * Called directly for a non-owner admin, the after-connection handler does not accept the
	 * terms and shows the informational banner instead.
	 */
	public function test_after_connection_handler_does_not_accept_for_a_non_owner() {
		$nux = $this->arm_banner( false );
		WC_Connect_Options::update_option( 'tos_accepted', false );
		WC_Connect_Options::update_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER, true );

		$result = $this->run_banner( $nux, 'show_banner_after_connection' );

		$this->assertFalse( (bool) WC_Connect_Options::get_option( 'tos_accepted' ) );
		$this->assertStringContainsString( 'needs to accept the Terms of Service', $result['output'] );
		$this->assertStringNotContainsString( 'Setup complete.', $result['output'] );
	}

	public function test_get_banner_type_to_display_dev_jp() {
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status' => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
				)
			),
			false
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => null, // irrelevant here, TOS is accepted (DEV)
					'should_display_after_cxn_banner' => false,
				)
			),
			false
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => null, // irrelevant here, TOS will be accepted in "after" banner
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			'tos_only_banner'
		);
	}

	public function test_get_banner_type_to_display_no_jp_cxn_without_tos_acceptance() {
		// before going through connection
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false, // no master user, not DEV
					'should_display_after_cxn_banner' => false,
				)
			),
			'before_jetpack_connection'
		);

		// after going through connection, TOS was never accepted
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => null, // irrelevant here, TOS will be accepted in "after" banner
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);
	}

	public function test_get_banner_type_to_display_no_jp_cxn_with_tos_acceptance() {
		// before going through connection
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			'before_jetpack_connection'
		);

		// after going through connection, TOS was already previously accepted
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);
	}

	public function test_get_banner_type_to_display_with_jp_cxn_without_tos_acceptance() {
		// Jetpack is already connected, TOS was not yet accepted
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			'tos_only_banner'
		);

		// Regression test for changing order of "tos accepted" and "should display after cxn banner"
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);
	}

	/**
	 * A site that registered but never had an account linked gets its own banner.
	 *
	 * Jetpack reports this state as "not connected" - WC_Connect_Jetpack::is_connected()
	 * requires a connected owner - so without this branch the store is told to connect a site
	 * that is already connected, and the only missing step is never named.
	 */
	public function test_get_banner_type_to_display_site_only_connection() {
		$this->assertEquals(
			'site_only_connection',
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => false,
					'is_site_only_connection'         => true,
				)
			)
		);

		// TOS acceptance is irrelevant to it: nothing can be accepted without an owner anyway.
		$this->assertEquals(
			'site_only_connection',
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
					'is_site_only_connection'         => true,
				)
			)
		);
	}

	/**
	 * A store with no connection at all keeps the original "connect your site" banner.
	 */
	public function test_get_banner_type_to_display_not_connected_is_not_site_only() {
		$this->assertEquals(
			'before_jetpack_connection',
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => false,
					'is_site_only_connection'         => false,
				)
			)
		);
	}

	/**
	 * Callers that never pass the new key keep the exact behaviour they had before it existed.
	 *
	 * This method is public static on a globally-named class, so third-party code can call it
	 * with the four keys it has always taken. Omitting the key must not reach the new banner.
	 */
	public function test_get_banner_type_to_display_without_the_site_only_key_is_unchanged() {
		$this->assertEquals(
			'before_jetpack_connection',
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => false,
				)
			)
		);
	}

	/**
	 * The new flag only speaks for the not-connected branch and must not divert a real
	 * connection, in offline mode or otherwise.
	 */
	public function test_site_only_flag_does_not_affect_connected_states() {
		$this->assertEquals(
			'tos_only_banner',
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
					'is_site_only_connection'         => true,
				)
			)
		);

		$this->assertEquals(
			'after_jetpack_connection',
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => true,
					'is_site_only_connection'         => true,
				)
			)
		);

		$this->assertFalse(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
					'is_site_only_connection'         => true,
				)
			)
		);
	}

	public function test_get_banner_type_to_display_with_jp_cxn_without_tos_acceptance_non_owner() {
		// Jetpack is already connected, TOS was not yet accepted, user is not the connection owner
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => false,
				)
			),
			'tos_informational_banner'
		);
	}

	public function test_get_banner_type_to_display_with_jp_cxn_with_tos_acceptance() {
		// Jetpack is already connected, TOS is already accepted
		// did not show before connection banner
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			false
		);
	}

	/**
	 * The banner gate does not touch instance state, so build the object without
	 * running the constructor to avoid pulling in the tracks and shipping label deps.
	 */
	private function get_nux() {
		$reflection = new ReflectionClass( 'WC_Connect_Nux' );

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Build a minimal stand-in for WP_Screen carrying just the properties the gate reads.
	 *
	 * @param string $base Screen base.
	 * @return stdClass
	 */
	private function make_screen( $base ) {
		$screen       = new stdClass();
		$screen->base = $base;
		$screen->id   = $base;

		return $screen;
	}

	/**
	 * Clear the request globals, options and screen the banner code reads between tests.
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'throw_on_redirect' ) );

		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}

		if ( false === $this->original_default_country ) {
			delete_option( 'woocommerce_default_country' );
		} else {
			update_option( 'woocommerce_default_country', $this->original_default_country );
		}

		unset( $_GET['tab'], $_GET['section'], $_GET['wcs-nux-tos'], $_GET['wcs-nux-notice'], $_GET['_wpnonce'] );
		WC_Connect_Options::delete_option( 'tos_accepted' );
		WC_Connect_Options::delete_option( WC_Connect_Nux::SHOULD_SHOW_AFTER_CXN_BANNER );
		$GLOBALS['current_screen'] = null;
		wp_set_current_user( 0 );
		WCS_Test_Jetpack_Connection::reset();

		parent::tear_down();
	}

	/**
	 * The banner renders on WooCommerce » Settings » Tax.
	 */
	public function test_should_display_nux_notice_on_the_tax_settings_tab() {
		$_GET['tab'] = 'tax';

		$this->assertTrue(
			$this->get_nux()->should_display_nux_notice_on_screen(
				$this->make_screen( 'woocommerce_page_wc-settings' )
			)
		);
	}

	/**
	 * Every section of the Tax tab, including the rate tables, still qualifies.
	 */
	public function test_should_display_nux_notice_on_every_section_of_the_tax_settings_tab() {
		$_GET['tab'] = 'tax';

		foreach ( array( 'standard', 'reduced-rate', 'zero-rate' ) as $section ) {
			$_GET['section'] = $section;

			$this->assertTrue(
				$this->get_nux()->should_display_nux_notice_on_screen(
					$this->make_screen( 'woocommerce_page_wc-settings' )
				),
				"Expected the banner to be allowed on the {$section} tax section."
			);
		}
	}

	/**
	 * Other WooCommerce settings tabs no longer show the banner.
	 */
	public function test_should_not_display_nux_notice_on_other_settings_tabs() {
		foreach ( array( 'general', 'products', 'shipping', 'checkout', 'advanced' ) as $tab ) {
			$_GET['tab'] = $tab;

			$this->assertFalse(
				$this->get_nux()->should_display_nux_notice_on_screen(
					$this->make_screen( 'woocommerce_page_wc-settings' )
				),
				"Expected the banner to be suppressed on the {$tab} settings tab."
			);
		}
	}

	/**
	 * The settings landing page defaults to General, so it must not show the banner.
	 */
	public function test_should_not_display_nux_notice_on_settings_without_a_tab() {
		$this->assertFalse(
			$this->get_nux()->should_display_nux_notice_on_screen(
				$this->make_screen( 'woocommerce_page_wc-settings' )
			)
		);
	}

	/**
	 * The Plugins page stays a connection surface regardless of any tab in the URL.
	 */
	public function test_should_display_nux_notice_on_the_plugins_page() {
		$this->assertTrue(
			$this->get_nux()->should_display_nux_notice_on_screen( $this->make_screen( 'plugins' ) )
		);

		// The Plugins page carries no tab, and an unrelated one must not suppress it.
		$_GET['tab'] = 'general';

		$this->assertTrue(
			$this->get_nux()->should_display_nux_notice_on_screen( $this->make_screen( 'plugins' ) )
		);
	}

	/**
	 * Product, order, extension and dashboard screens no longer show the banner.
	 */
	public function test_should_not_display_nux_notice_on_non_tax_screens() {
		$_GET['tab'] = 'tax';

		$screens = array(
			'edit',
			'post',
			'woocommerce_page_wc-addons',
			'woocommerce_page_wc-orders',
			'dashboard',
		);

		foreach ( $screens as $base ) {
			$this->assertFalse(
				$this->get_nux()->should_display_nux_notice_on_screen( $this->make_screen( $base ) ),
				"Expected the banner to be suppressed on the {$base} screen."
			);
		}
	}

	/**
	 * A missing screen is handled without a fatal.
	 */
	public function test_should_not_display_nux_notice_without_a_screen() {
		$_GET['tab'] = 'tax';

		$this->assertFalse( $this->get_nux()->should_display_nux_notice_on_screen( null ) );
	}
}
