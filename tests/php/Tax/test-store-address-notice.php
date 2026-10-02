<?php
/**
 * Tests for Automattic\WCServices\Tax\StoreAddressNotice.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WCServices\Tax\StoreAddressNotice;
use Automattic\WCServices\Tax\StoreAddressVerifier;

/**
 * Class WP_Test_WCServices_Tax_Store_Address_Notice
 */
class WP_Test_WCServices_Tax_Store_Address_Notice extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StoreAddressNotice
	 */
	private $sut;

	/**
	 * Verifier the notice reads from.
	 *
	 * @var StoreAddressVerifier
	 */
	private $verifier;

	/**
	 * Load required classes before running tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-tracks.php';
	}

	/**
	 * Set up before each test.
	 */
	public function set_up() {
		parent::set_up();

		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock();
		$logger     = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$tracks     = $this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock();
		$taxjar     = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks );

		$this->verifier = new StoreAddressVerifier( $api_client, $taxjar );
		$this->sut      = new StoreAddressNotice( $this->verifier );

		update_option( 'woocommerce_default_country', 'US:CO' );
		update_option( 'woocommerce_store_postcode', '81323' );
		update_option( 'woocommerce_store_city', 'Dolores' );
		update_option( 'woocommerce_store_address', '123 Main St' );

		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'woocommerce_page_wc-settings' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		delete_option( StoreAddressVerifier::OPTION_NAME );
		remove_all_filters( 'taxjar_store_settings' );
		wp_set_current_user( 0 );
		set_current_screen( 'front' );

		parent::tear_down();
	}

	/**
	 * Store a result for the current store address.
	 *
	 * @param string     $status     Status.
	 * @param array|null $suggestion Suggested address.
	 * @param bool       $dismissed  Dismissed.
	 */
	private function store_result( $status, $suggestion = null, $dismissed = false ) {
		update_option(
			StoreAddressVerifier::OPTION_NAME,
			array(
				'hash'       => StoreAddressVerifier::hash_address( $this->verifier->get_store_address() ),
				'status'     => $status,
				'suggestion' => $suggestion,
				'checked_at' => time(),
				'dismissed'  => $dismissed,
			)
		);
	}

	/**
	 * Suggested address.
	 *
	 * @param string $state    State.
	 * @param string $postcode ZIP.
	 * @return array
	 */
	private function suggestion( $state, $postcode ) {
		return array(
			'state'    => $state,
			'postcode' => $postcode,
		);
	}

	/**
	 * Render the notice and return its HTML.
	 *
	 * @return string
	 */
	private function render() {
		ob_start();
		$this->sut->render();
		return ob_get_clean();
	}

	/* ──── render() ──── */

	/**
	 * @testdox A missing ZIP shows an error that can't be dismissed.
	 */
	public function test_missing_zip_shows_an_undismissable_error() {
		$this->store_result( StoreAddressVerifier::STATUS_ZIP_MISSING );

		$html = $this->render();

		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'has no ZIP code', $html );
		$this->assertStringNotContainsString( 'notice-dismiss', $html );
	}

	/**
	 * @testdox An address TaxJar can't find shows a dismissible error.
	 */
	public function test_not_found_shows_a_dismissible_error() {
		$this->store_result( StoreAddressVerifier::STATUS_NOT_FOUND );

		$html = $this->render();

		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'couldn&#039;t find your store address', $html );
		$this->assertStringContainsString( 'notice-dismiss', $html );
	}

	/**
	 * @testdox An address matching several places shows a warning.
	 */
	public function test_ambiguous_shows_a_warning() {
		$this->store_result( StoreAddressVerifier::STATUS_AMBIGUOUS );

		$html = $this->render();

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( 'more than one place', $html );
	}

	/**
	 * @testdox A suggestion with a different state is an error offering to apply it.
	 */
	public function test_state_suggestion_is_an_error_with_apply() {
		$this->store_result( StoreAddressVerifier::STATUS_SUGGESTION, $this->suggestion( 'UT', '84101' ) );

		$html = $this->render();

		$this->assertStringContainsString( 'notice-error', $html );
		$this->assertStringContainsString( 'wrong state', $html );
		$this->assertStringContainsString( 'UT 84101', $html );
		$this->assertStringContainsString( 'Update state and ZIP code', $html );
		$this->assertStringContainsString( StoreAddressNotice::APPLY_ACTION, $html );
	}

	/**
	 * @testdox A suggestion with only a different ZIP is a warning.
	 */
	public function test_zip_suggestion_is_a_warning() {
		$this->store_result( StoreAddressVerifier::STATUS_SUGGESTION, $this->suggestion( 'CO', '81321' ) );

		$html = $this->render();

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringContainsString( 'ZIP code may be wrong', $html );
		$this->assertStringContainsString( 'Update ZIP code', $html );
	}

	/**
	 * @testdox When custom code sets the store address, the notice doesn't offer to apply the suggestion.
	 */
	public function test_filtered_address_gets_no_apply_button() {
		add_filter(
			'taxjar_store_settings',
			function () {
				return array( 'postcode' => '81320' );
			}
		);
		$this->store_result( StoreAddressVerifier::STATUS_SUGGESTION, $this->suggestion( 'CO', '81321' ) );

		$html = $this->render();

		$this->assertStringNotContainsString( 'Update ZIP code', $html );
		$this->assertStringContainsString( 'set by custom code', $html );
	}

	/**
	 * Situations where nothing is shown.
	 *
	 * @return array
	 */
	public function provide_silent_cases() {
		return array(
			'verified'                 => array( StoreAddressVerifier::STATUS_VERIFIED, false, 'administrator', 'woocommerce_page_wc-settings' ),
			'skipped'                  => array( StoreAddressVerifier::STATUS_SKIPPED, false, 'administrator', 'woocommerce_page_wc-settings' ),
			'error'                    => array( StoreAddressVerifier::STATUS_ERROR, false, 'administrator', 'woocommerce_page_wc-settings' ),
			'dismissed'                => array( StoreAddressVerifier::STATUS_NOT_FOUND, true, 'administrator', 'woocommerce_page_wc-settings' ),
			'not a WooCommerce screen' => array( StoreAddressVerifier::STATUS_NOT_FOUND, false, 'administrator', 'edit-post' ),
			'not a store manager'      => array( StoreAddressVerifier::STATUS_NOT_FOUND, false, 'author', 'woocommerce_page_wc-settings' ),
		);
	}

	/**
	 * @testdox Nothing is shown when there is nothing to fix, or it isn't the place or person to show it.
	 * @dataProvider provide_silent_cases
	 *
	 * @param string $status    Status.
	 * @param bool   $dismissed Dismissed.
	 * @param string $role      User role.
	 * @param string $screen    Screen id.
	 */
	public function test_nothing_is_shown( $status, $dismissed, $role, $screen ) {
		$this->store_result( $status, null, $dismissed );
		wp_set_current_user( $this->factory->user->create( array( 'role' => $role ) ) );
		set_current_screen( $screen );

		$this->assertSame( '', $this->render() );
	}

	/**
	 * @testdox A result for an earlier address is not shown.
	 */
	public function test_stale_result_is_not_shown() {
		$this->store_result( StoreAddressVerifier::STATUS_NOT_FOUND );
		update_option( 'woocommerce_store_postcode', '80202' );

		$this->assertSame( '', $this->render() );
	}

	/* ──── apply_suggestion() ──── */

	/**
	 * @testdox Applying writes only the state and ZIP, leaves the street and city as typed, and marks the address verified.
	 */
	public function test_apply_writes_the_suggestion() {
		$this->store_result( StoreAddressVerifier::STATUS_SUGGESTION, $this->suggestion( 'UT', '84101' ) );

		$this->assertTrue( $this->sut->apply_suggestion() );

		$this->assertSame( 'US:UT', get_option( 'woocommerce_default_country' ) );
		$this->assertSame( '84101', get_option( 'woocommerce_store_postcode' ) );
		$this->assertSame( 'Dolores', get_option( 'woocommerce_store_city' ) );
		$this->assertSame( '123 Main St', get_option( 'woocommerce_store_address' ) );
		$this->assertSame( StoreAddressVerifier::STATUS_VERIFIED, $this->verifier->get_current_result()['status'] );
	}

	/**
	 * Situations where applying must change nothing.
	 *
	 * @return array
	 */
	public function provide_refused_applies() {
		return array(
			'not a store manager'   => array( 'author', false, false ),
			'address changed since' => array( 'administrator', true, false ),
			'set by custom code'    => array( 'administrator', false, true ),
		);
	}

	/**
	 * @testdox Applying changes nothing unless a store manager applies a current suggestion to settings that tax reads.
	 * @dataProvider provide_refused_applies
	 *
	 * @param string $role              User role.
	 * @param bool   $address_changed   Whether the address changed after the check.
	 * @param bool   $filtered_address  Whether custom code sets the address.
	 */
	public function test_apply_is_refused( $role, $address_changed, $filtered_address ) {
		$this->store_result( StoreAddressVerifier::STATUS_SUGGESTION, $this->suggestion( 'UT', '84101' ) );
		wp_set_current_user( $this->factory->user->create( array( 'role' => $role ) ) );
		if ( $address_changed ) {
			update_option( 'woocommerce_store_postcode', '81321' );
		}
		if ( $filtered_address ) {
			// The stored result is for the address before the filter; store it again for the filtered one.
			add_filter(
				'taxjar_store_settings',
				function () {
					return array( 'postcode' => '81320' );
				}
			);
			$this->store_result( StoreAddressVerifier::STATUS_SUGGESTION, $this->suggestion( 'UT', '84101' ) );
		}
		$before = get_option( 'woocommerce_default_country' );

		$this->assertFalse( $this->sut->apply_suggestion() );
		$this->assertSame( $before, get_option( 'woocommerce_default_country' ) );
	}

	/**
	 * @testdox Applying does nothing when the current result isn't a suggestion.
	 */
	public function test_apply_needs_a_suggestion() {
		$this->store_result( StoreAddressVerifier::STATUS_NOT_FOUND );

		$this->assertFalse( $this->sut->apply_suggestion() );
		$this->assertSame( 'US:CO', get_option( 'woocommerce_default_country' ) );
	}
}
