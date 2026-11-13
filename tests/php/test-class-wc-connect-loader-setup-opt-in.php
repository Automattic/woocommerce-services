<?php
/**
 * Tests for how WC_Connect_Loader::pre_wc_init() turns woocommerce_setup_jetpack_opted_in
 * into an accepted Terms of Service.
 *
 * @package WooCommerce_Services
 */

use Automattic\Jetpack\Constants;

require_once __DIR__ . '/class-wcs-test-jetpack-connection.php';

/**
 * The opt-in flag is only turned into an accepted ToS on a request made by a user who may
 * accept the terms. Any other request leaves the flag for that user's next request.
 */
class WP_Test_WC_Connect_Loader_Setup_Opt_In extends WC_Unit_Test_Case {

	/**
	 * Option WooCommerce core sets when the merchant opts in during onboarding.
	 *
	 * @var string
	 */
	const OPT_IN = 'woocommerce_setup_jetpack_opted_in';

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
	 * A customer.
	 *
	 * @var int
	 */
	private $customer;

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
	 * Connect the site with an owner, terms not accepted, opt-in flag set.
	 */
	public function set_up() {
		parent::set_up();

		$this->owner        = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->other_admin  = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->shop_manager = self::factory()->user->create( array( 'role' => 'shop_manager' ) );
		$this->customer     = self::factory()->user->create( array( 'role' => 'customer' ) );

		WCS_Test_Jetpack_Connection::connect( $this->owner );
		update_option( 'wc_connect_options', array( 'tos_accepted' => false ) );
		update_option( self::OPT_IN, true );
	}

	/**
	 * Undo the connection, the Atomic constants and the options.
	 */
	public function tear_down() {
		Constants::clear_constants();
		WCS_Test_Jetpack_Connection::reset();
		delete_option( 'wc_connect_options' );
		delete_option( self::OPT_IN );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Run pre_wc_init() as the given user.
	 *
	 * @param int $user_id User ID, 0 for a logged-out request.
	 */
	private function run_pre_wc_init_as( $user_id ) {
		wp_set_current_user( $user_id );
		( new WC_Connect_Loader() )->pre_wc_init();
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
	 * Whether the opt-in flag is still stored.
	 *
	 * @return bool
	 */
	private function opt_in_kept() {
		return (bool) get_option( self::OPT_IN );
	}

	/**
	 * Requests that must not consume the flag on a connected store.
	 *
	 * @return array
	 */
	public function provide_users_who_may_not_accept() {
		return array(
			'non-owner administrator' => array( 'other_admin' ),
			'shop manager'            => array( 'shop_manager' ),
			'customer'                => array( 'customer' ),
			'logged out'              => array( null ),
		);
	}

	/**
	 * A request by anyone but the owner leaves the terms unaccepted and the flag in place.
	 *
	 * @dataProvider provide_users_who_may_not_accept
	 *
	 * @param string|null $user Property holding the user ID, null for a logged-out request.
	 */
	public function test_opt_in_is_kept_for_users_who_may_not_accept( $user ) {
		$this->run_pre_wc_init_as( null === $user ? 0 : $this->$user );

		$this->assertFalse( $this->tos_accepted() );
		$this->assertTrue( $this->opt_in_kept() );
	}

	/**
	 * A request by the owner accepts the terms and deletes the flag.
	 */
	public function test_owner_request_accepts_and_deletes_opt_in() {
		$this->run_pre_wc_init_as( $this->owner );

		$this->assertTrue( $this->tos_accepted() );
		$this->assertFalse( $this->opt_in_kept() );
	}

	/**
	 * Without the flag, an owner request accepts nothing.
	 */
	public function test_owner_request_without_opt_in_accepts_nothing() {
		delete_option( self::OPT_IN );

		$this->run_pre_wc_init_as( $this->owner );

		$this->assertFalse( $this->tos_accepted() );
	}

	/**
	 * In offline mode there is no owner, so a shop manager's request accepts the terms.
	 */
	public function test_offline_mode_shop_manager_request_accepts() {
		WCS_Test_Jetpack_Connection::reset();
		WCS_Test_Jetpack_Connection::set_offline( true );

		$this->run_pre_wc_init_as( $this->shop_manager );

		$this->assertTrue( $this->tos_accepted() );
		$this->assertFalse( $this->opt_in_kept() );
	}

	/**
	 * In offline mode a logged-out request (front end, cron, WP-CLI) or a customer's
	 * request still leaves the flag in place.
	 *
	 * @testWith [ null ]
	 *           [ "customer" ]
	 *
	 * @param string|null $user Property holding the user ID, null for a logged-out request.
	 */
	public function test_offline_mode_keeps_opt_in_for_users_without_store_access( $user ) {
		WCS_Test_Jetpack_Connection::reset();
		WCS_Test_Jetpack_Connection::set_offline( true );

		$this->run_pre_wc_init_as( null === $user ? 0 : $this->$user );

		$this->assertFalse( $this->tos_accepted() );
		$this->assertTrue( $this->opt_in_kept() );
	}

	/**
	 * On a store that is not connected yet nobody may accept, so the flag waits for the
	 * owner, whose first request after connecting accepts the terms.
	 */
	public function test_not_connected_keeps_opt_in_until_owner_connects() {
		WCS_Test_Jetpack_Connection::reset();
		WCS_Test_Jetpack_Connection::set_offline( false );

		$this->run_pre_wc_init_as( $this->other_admin );

		$this->assertFalse( $this->tos_accepted() );
		$this->assertTrue( $this->opt_in_kept() );

		WCS_Test_Jetpack_Connection::connect( $this->other_admin );
		$this->run_pre_wc_init_as( $this->other_admin );

		$this->assertTrue( $this->tos_accepted() );
		$this->assertFalse( $this->opt_in_kept() );
	}

	/**
	 * On Atomic the terms are accepted on any request, as before, and the flag deleted.
	 *
	 * @testWith [ true ]
	 *           [ false ]
	 *
	 * @param bool $opted_in Whether the opt-in flag is set.
	 */
	public function test_atomic_site_accepts_on_any_request( $opted_in ) {
		if ( ! $opted_in ) {
			delete_option( self::OPT_IN );
		}
		Constants::set_constant( 'ATOMIC_SITE_ID', 1 );
		Constants::set_constant( 'ATOMIC_CLIENT_ID', 2 );
		Constants::set_constant( 'WPCOMSH__PLUGIN_FILE', 'wpcomsh/wpcomsh.php' );
		WCS_Test_Jetpack_Connection::reset_caches();

		$this->run_pre_wc_init_as( 0 );

		$this->assertTrue( $this->tos_accepted() );
		$this->assertFalse( $this->opt_in_kept() );
	}
}
