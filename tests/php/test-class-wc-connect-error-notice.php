<?php
/**
 * Tests for the dismissal guard on WC_Connect_Error_Notice.
 *
 * @package WC_Connect
 */

/**
 * Class WP_Test_WC_Connect_Error_Notice
 */
class WP_Test_WC_Connect_Error_Notice extends WC_Unit_Test_Case {

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once __DIR__ . '/../../classes/class-wc-connect-options.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-error-notice.php';
	}

	/**
	 * Reset the request and the stored notice between tests.
	 */
	public function tear_down() {
		unset( $_GET['wc-connect-error-notice'], $_GET[ WC_Connect_Error_Notice::DISMISS_NONCE_NAME ] );
		WC_Connect_Options::delete_option( 'error_notice' );
		wp_set_current_user( 0 );

		parent::tear_down();
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
}
