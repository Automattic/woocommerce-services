<?php
/**
 * Tests for the dismissal guard on WC_Connect_Error_Notice.
 *
 * @package WC_Connect
 */

require_once __DIR__ . '/class-wcs-test-error-notice-redirect.php';

/**
 * Class WP_Test_WC_Connect_Error_Notice
 */
class WP_Test_WC_Connect_Error_Notice extends WC_Unit_Test_Case {

	/**
	 * Request URI the dismissal is assumed to have been clicked from.
	 *
	 * @var string
	 */
	const REQUEST_URI = '/wp-admin/index.php';

	/**
	 * The request URI to put back after each test.
	 *
	 * @var string|null
	 */
	private $original_request_uri;

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once __DIR__ . '/../../classes/class-wc-connect-options.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-error-notice.php';
	}

	/**
	 * Give the dismissal a request to build its redirect from, and turn that redirect
	 * into an exception.
	 *
	 * render_notice() calls exit immediately after redirecting. Left alone that ends the
	 * PHPUnit process with status 0, so a dismissal that got through the guard would end
	 * the run green instead of failing it. Throwing from the wp_redirect filter unwinds
	 * before the exit is reached, which both keeps the rejection tests honest and makes
	 * the success path assertable.
	 */
	public function set_up() {
		parent::set_up();

		$this->original_request_uri = isset( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: null;
		$_SERVER['REQUEST_URI']     = self::REQUEST_URI;

		add_filter( 'wp_redirect', array( $this, 'throw_on_redirect' ) );
	}

	/**
	 * Reset the request and the stored notice between tests.
	 */
	public function tear_down() {
		remove_filter( 'wp_redirect', array( $this, 'throw_on_redirect' ) );

		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}

		unset( $_GET['wc-connect-error-notice'], $_GET[ WC_Connect_Error_Notice::DISMISS_NONCE_NAME ] );
		WC_Connect_Options::delete_option( 'error_notice' );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Stand in for the redirect that ends a successful dismissal.
	 *
	 * @param string $location Redirect target.
	 *
	 * @throws WCS_Test_Error_Notice_Redirect Always.
	 */
	public function throw_on_redirect( $location ) {
		throw new WCS_Test_Error_Notice_Redirect( esc_html( $location ) );
	}

	/**
	 * Store an error so the notice is armed.
	 *
	 * @return void
	 */
	private function arm_notice() {
		WC_Connect_Options::update_option( 'error_notice', new WP_Error( 'wcs_test_error', 'Test error' ) );
	}

	/**
	 * A dismissal request that carries no nonce must not clear the stored notice.
	 */
	public function test_dismissal_without_nonce_is_ignored() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->arm_notice();

		$_GET['wc-connect-error-notice'] = 'disable';

		WC_Connect_Error_Notice::instance()->render_notice();

		$this->assertWPError( WC_Connect_Options::get_option( 'error_notice', false ) );
	}

	/**
	 * A dismissal request with a forged nonce must not clear the stored notice.
	 */
	public function test_dismissal_with_invalid_nonce_is_ignored() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->arm_notice();

		$_GET['wc-connect-error-notice']                     = 'disable';
		$_GET[ WC_Connect_Error_Notice::DISMISS_NONCE_NAME ] = 'not-a-valid-nonce';

		WC_Connect_Error_Notice::instance()->render_notice();

		$this->assertWPError( WC_Connect_Options::get_option( 'error_notice', false ) );
	}

	/**
	 * A user without `manage_woocommerce` must not be able to dismiss, even with a good nonce.
	 */
	public function test_dismissal_without_capability_is_ignored() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'author' ) ) );
		$this->arm_notice();

		$_GET['wc-connect-error-notice']                     = 'disable';
		$_GET[ WC_Connect_Error_Notice::DISMISS_NONCE_NAME ] = wp_create_nonce( WC_Connect_Error_Notice::DISMISS_NONCE_ACTION );

		WC_Connect_Error_Notice::instance()->render_notice();

		$this->assertWPError( WC_Connect_Options::get_option( 'error_notice', false ) );
	}

	/**
	 * A store manager with a valid nonce clears the notice and is sent back to the
	 * screen they were on, without the dismissal arguments.
	 */
	public function test_dismissal_with_nonce_and_capability_clears_the_notice() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->arm_notice();

		$_GET['wc-connect-error-notice']                     = 'disable';
		$_GET[ WC_Connect_Error_Notice::DISMISS_NONCE_NAME ] = wp_create_nonce( WC_Connect_Error_Notice::DISMISS_NONCE_ACTION );

		$redirect = null;

		try {
			WC_Connect_Error_Notice::instance()->render_notice();
		} catch ( WCS_Test_Error_Notice_Redirect $e ) {
			$redirect = $e->getMessage();
		}

		$this->assertNotNull( $redirect, 'A valid dismissal should redirect.' );
		$this->assertStringNotContainsString( 'wc-connect-error-notice', $redirect );
		$this->assertStringNotContainsString( WC_Connect_Error_Notice::DISMISS_NONCE_NAME, $redirect );
		$this->assertNotWPError( WC_Connect_Options::get_option( 'error_notice', false ) );
	}
}
