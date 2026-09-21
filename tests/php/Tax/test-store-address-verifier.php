<?php
/**
 * Tests for Automattic\WCServices\Tax\StoreAddressVerifier.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WCServices\Tax\Address;
use Automattic\WCServices\Tax\StoreAddressVerifier;

/**
 * Class WP_Test_WCServices_Tax_Store_Address_Verifier
 */
class WP_Test_WCServices_Tax_Store_Address_Verifier extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StoreAddressVerifier
	 */
	private $sut;

	/**
	 * Mocked API client.
	 *
	 * @var WC_Connect_API_Client|\PHPUnit\Framework\MockObject\MockObject
	 */
	private $api_client;

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

		$this->api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )
			->disableOriginalConstructor()
			->getMock();

		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->getMock();

		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )
			->disableOriginalConstructor()
			->getMock();

		$taxjar = new WC_Connect_TaxJar_Integration( $this->api_client, $logger, 'https://example.com', $tracks );

		$this->sut = new StoreAddressVerifier( $this->api_client, $taxjar );

		$this->set_store_address( 'US:CO', '81323', 'Dolores', '123 Main St' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		delete_option( StoreAddressVerifier::OPTION_NAME );
		wp_clear_scheduled_hook( StoreAddressVerifier::CRON_HOOK );
		remove_all_filters( 'taxjar_store_settings' );
		wp_set_current_user( 0 );

		parent::tear_down();
	}

	/**
	 * Save a store address in the WooCommerce settings.
	 *
	 * @param string $country_state Country and state, e.g. "US:CO".
	 * @param string $postcode      Postcode.
	 * @param string $city          City.
	 * @param string $street        Street.
	 */
	private function set_store_address( $country_state, $postcode, $city, $street ) {
		update_option( 'woocommerce_default_country', $country_state );
		update_option( 'woocommerce_store_postcode', $postcode );
		update_option( 'woocommerce_store_city', $city );
		update_option( 'woocommerce_store_address', $street );
	}

	/**
	 * A response shaped like WC_Connect_API_Client::proxy_request()'s.
	 *
	 * @param int   $code HTTP status.
	 * @param mixed $body Body; arrays are JSON-encoded.
	 * @return array
	 */
	private function response( $code, $body ) {
		return array(
			'response' => array( 'code' => $code ),
			'body'     => is_array( $body ) ? wp_json_encode( $body ) : $body,
		);
	}

	/**
	 * The address the tests check against.
	 *
	 * @return Address
	 */
	private function typed_address() {
		return Address::from_store_settings(
			array(
				'country'  => 'US',
				'state'    => 'CO',
				'postcode' => '81323',
				'city'     => 'Dolores',
				'street'   => '123 Main St',
			)
		);
	}

	/**
	 * Candidate as TaxJar returns it.
	 *
	 * @param string $state  State.
	 * @param string $zip    ZIP.
	 * @param string $city   City.
	 * @param string $street Street.
	 * @return array
	 */
	private function candidate( $state, $zip, $city = 'DOLORES', $street = '123 MAIN ST' ) {
		return array(
			'country' => 'US',
			'state'   => $state,
			'zip'     => $zip,
			'city'    => $city,
			'street'  => $street,
		);
	}

	/* ──── classify_response() ──── */

	/**
	 * @testdox A match in the same state and 5-digit ZIP is verified, even when street, city and ZIP+4 differ.
	 */
	public function test_same_state_and_zip5_is_verified() {
		$response = $this->response( 200, array( 'addresses' => array( $this->candidate( 'CO', '81323-9704', 'DELORES', '123 S MAIN ST' ) ) ) );

		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_VERIFIED, $result['status'] );
		$this->assertNull( $result['suggestion'] );
	}

	/**
	 * @testdox A single match in a different ZIP suggests only TaxJar's state and 5-digit ZIP.
	 */
	public function test_different_zip5_is_a_suggestion() {
		$response = $this->response( 200, array( 'addresses' => array( $this->candidate( 'CO', '81321-1234', 'CORTEZ', '9 ELM ST' ) ) ) );

		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_SUGGESTION, $result['status'] );
		$this->assertSame(
			array(
				'state'    => 'CO',
				'postcode' => '81321',
			),
			$result['suggestion']
		);
	}

	/**
	 * @testdox A single match in a different state is a suggestion with the new state, uppercased.
	 */
	public function test_different_state_is_a_suggestion() {
		$response = $this->response( 200, array( 'addresses' => array( $this->candidate( 'ut', '81323' ) ) ) );

		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_SUGGESTION, $result['status'] );
		$this->assertSame( 'UT', $result['suggestion']['state'] );
	}

	/**
	 * @testdox Several matches that all agree on state and 5-digit ZIP are verified.
	 */
	public function test_several_agreeing_matches_are_verified() {
		$response = $this->response(
			200,
			array(
				'addresses' => array(
					$this->candidate( 'CO', '81323-9704' ),
					$this->candidate( 'CO', '81323-9705' ),
				),
			)
		);

		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_VERIFIED, $result['status'] );
	}

	/**
	 * @testdox Several matches in different places are ambiguous, with no suggestion.
	 */
	public function test_several_disagreeing_matches_are_ambiguous() {
		$response = $this->response(
			200,
			array(
				'addresses' => array(
					$this->candidate( 'CO', '81323-9704' ),
					$this->candidate( 'CO', '81321-0001' ),
				),
			)
		);

		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_AMBIGUOUS, $result['status'] );
		$this->assertNull( $result['suggestion'] );
	}

	/**
	 * @testdox A 404 means TaxJar could not find the address.
	 */
	public function test_404_is_not_found() {
		$response = $this->response(
			404,
			array(
				'error'  => 'Not Found',
				'detail' => 'Resource can not be found',
			)
		);

		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_NOT_FOUND, $result['status'] );
	}

	/**
	 * @testdox A 200 with no matches means TaxJar could not find the address.
	 */
	public function test_empty_match_list_is_not_found() {
		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $this->response( 200, array( 'addresses' => array() ) ) );

		$this->assertSame( StoreAddressVerifier::STATUS_NOT_FOUND, $result['status'] );
	}

	/**
	 * Responses that say nothing about the address.
	 *
	 * @return array
	 */
	public function provide_failed_responses() {
		return array(
			'transport error' => array( new WP_Error( 'http_request_failed', 'timed out' ) ),
			'server error'    => array(
				array(
					'response' => array( 'code' => 500 ),
					'body'     => '',
				),
			),
			'unauthorized'    => array(
				array(
					'response' => array( 'code' => 401 ),
					'body'     => '{}',
				),
			),
			'rate limited'    => array(
				array(
					'response' => array( 'code' => 429 ),
					'body'     => '{}',
				),
			),
			'not JSON'        => array(
				array(
					'response' => array( 'code' => 200 ),
					'body'     => '<html>',
				),
			),
			'no address list' => array(
				array(
					'response' => array( 'code' => 200 ),
					'body'     => '{"foo":1}',
				),
			),
		);
	}

	/**
	 * @testdox A response that says nothing about the address is an error.
	 * @dataProvider provide_failed_responses
	 *
	 * @param mixed $response Response.
	 */
	public function test_failed_response_is_an_error( $response ) {
		$result = StoreAddressVerifier::classify_response( $this->typed_address(), $response );

		$this->assertSame( StoreAddressVerifier::STATUS_ERROR, $result['status'] );
		$this->assertNull( $result['suggestion'] );
	}

	/* ──── verify() ──── */

	/**
	 * @testdox The store address is sent to TaxJar's address validation, and the answer is stored for that address.
	 */
	public function test_verify_sends_the_store_address_and_stores_the_result() {
		$this->api_client->expects( $this->once() )
			->method( 'proxy_request' )
			->with(
				'taxjar/v2/addresses/validate',
				$this->callback(
					function ( $args ) {
						$this->assertSame( 'POST', $args['method'] );
						$this->assertSame(
							array(
								'country' => 'US',
								'state'   => 'CO',
								'zip'     => '81323',
								'city'    => 'Dolores',
								'street'  => '123 Main St',
							),
							json_decode( $args['body'], true )
						);
						return true;
					}
				)
			)
			->willReturn( $this->response( 200, array( 'addresses' => array( $this->candidate( 'CO', '81323-9704' ) ) ) ) );

		$this->sut->verify();

		$stored = $this->sut->get_current_result();
		$this->assertNotNull( $stored, 'The result should be stored for the current address.' );
		$this->assertSame( StoreAddressVerifier::STATUS_VERIFIED, $stored['status'] );
		$this->assertFalse( $this->sut->is_check_due() );
	}

	/**
	 * @testdox A store outside the US is not sent to TaxJar.
	 */
	public function test_non_us_store_is_not_sent() {
		$this->set_store_address( 'CA:ON', 'M5V 2T6', 'Toronto', '1 King St' );
		$this->api_client->expects( $this->never() )->method( 'proxy_request' );

		$result = $this->sut->verify();

		$this->assertSame( StoreAddressVerifier::STATUS_SKIPPED, $result['status'] );
	}

	/**
	 * @testdox A US store without a ZIP is reported without asking TaxJar.
	 */
	public function test_missing_zip_is_reported_without_a_request() {
		$this->set_store_address( 'US:CO', '', 'Dolores', '123 Main St' );
		$this->api_client->expects( $this->never() )->method( 'proxy_request' );

		$result = $this->sut->verify();

		$this->assertSame( StoreAddressVerifier::STATUS_ZIP_MISSING, $result['status'] );
	}

	/**
	 * @testdox The address checked is the one tax is calculated from, including a taxjar_store_settings filter.
	 */
	public function test_verify_checks_the_filtered_address() {
		add_filter(
			'taxjar_store_settings',
			function () {
				return array(
					'state'    => 'UT',
					'postcode' => '84101',
				);
			}
		);

		$this->api_client->expects( $this->once() )
			->method( 'proxy_request' )
			->with(
				$this->anything(),
				$this->callback(
					function ( $args ) {
						$body = json_decode( $args['body'], true );
						return 'UT' === $body['state'] && '84101' === $body['zip'];
					}
				)
			)
			->willReturn( $this->response( 404, '{}' ) );

		$this->sut->verify();
	}

	/**
	 * @testdox The settings-screen check shortens the request's time limit for that call only.
	 */
	public function test_save_check_shortens_the_time_limit_for_that_call_only() {
		$validate_url = 'https://api.woocommerce.com/taxjar/v2/addresses/validate';
		$seen_timeout = null;

		$this->api_client->method( 'proxy_request' )->willReturnCallback(
			function () use ( $validate_url, &$seen_timeout ) {
				$args         = apply_filters( 'http_request_args', array( 'timeout' => 60 ), $validate_url );
				$seen_timeout = $args['timeout'];
				return $this->response( 200, array( 'addresses' => array( $this->candidate( 'CO', '81323' ) ) ) );
			}
		);

		$this->sut->verify_after_settings_save();

		$this->assertSame( StoreAddressVerifier::SAVE_TIMEOUT, $seen_timeout );

		$after = apply_filters( 'http_request_args', array( 'timeout' => 60 ), $validate_url );
		$this->assertSame( 60, $after['timeout'], 'The shorter time limit must not outlive the call.' );
	}

	/**
	 * @testdox The settings-screen check does not call TaxJar again when the address has not changed.
	 */
	public function test_save_check_skips_an_unchanged_address() {
		$this->api_client->expects( $this->once() )
			->method( 'proxy_request' )
			->willReturn( $this->response( 200, array( 'addresses' => array( $this->candidate( 'CO', '81323' ) ) ) ) );

		$this->sut->verify_after_settings_save();
		$this->sut->verify_after_settings_save();
	}

	/* ──── When a check is due ──── */

	/**
	 * @testdox A check is due again once the store address changes.
	 */
	public function test_check_is_due_after_the_address_changes() {
		$this->api_client->method( 'proxy_request' )
			->willReturn( $this->response( 200, array( 'addresses' => array( $this->candidate( 'CO', '81323' ) ) ) ) );
		$this->sut->verify();

		$this->set_store_address( 'US:CO', '80202', 'Denver', '1 Main St' );

		$this->assertTrue( $this->sut->is_check_due() );
		$this->assertNull( $this->sut->get_current_result(), 'A result for the old address must not be shown for the new one.' );
	}

	/**
	 * @testdox A failed check is retried only after the retry delay.
	 */
	public function test_failed_check_is_retried_after_the_delay() {
		$this->api_client->method( 'proxy_request' )->willReturn( new WP_Error( 'http_request_failed', 'timed out' ) );
		$this->sut->verify();

		$this->assertFalse( $this->sut->is_check_due(), 'A failed check should not be retried straight away.' );

		$stored               = get_option( StoreAddressVerifier::OPTION_NAME );
		$stored['checked_at'] = time() - StoreAddressVerifier::RETRY_AFTER;
		update_option( StoreAddressVerifier::OPTION_NAME, $stored );

		$this->assertTrue( $this->sut->is_check_due() );
	}

	/**
	 * @testdox A store manager's admin page load schedules one background check.
	 */
	public function test_admin_load_schedules_one_background_check() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$this->api_client->expects( $this->never() )->method( 'proxy_request' );

		$this->sut->maybe_schedule_check();
		$first = wp_next_scheduled( StoreAddressVerifier::CRON_HOOK );
		$this->sut->maybe_schedule_check();

		$this->assertNotFalse( $first, 'A background check should be scheduled.' );
		$this->assertSame( $first, wp_next_scheduled( StoreAddressVerifier::CRON_HOOK ), 'Only one check should be scheduled.' );
	}

	/**
	 * @testdox A check that cron hasn't run yet is not scheduled again, even after core's 10-minute duplicate window.
	 */
	public function test_stalled_check_is_not_scheduled_again() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$stalled_at = time() - HOUR_IN_SECONDS;
		wp_schedule_single_event( $stalled_at, StoreAddressVerifier::CRON_HOOK );

		$this->sut->maybe_schedule_check();

		$crons  = _get_cron_array();
		$events = 0;
		foreach ( $crons as $hooks ) {
			$events += isset( $hooks[ StoreAddressVerifier::CRON_HOOK ] ) ? count( $hooks[ StoreAddressVerifier::CRON_HOOK ] ) : 0;
		}
		$this->assertSame( 1, $events, 'The stalled event should be the only one.' );
	}

	/**
	 * @testdox Users who can't manage the store never trigger a check.
	 */
	public function test_other_users_do_not_schedule_a_check() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'author' ) ) );

		$this->sut->maybe_schedule_check();

		$this->assertFalse( wp_next_scheduled( StoreAddressVerifier::CRON_HOOK ) );
		$this->assertNull( $this->sut->get_result() );
	}

	/**
	 * @testdox A missing ZIP is reported on the same page load, with nothing scheduled.
	 */
	public function test_admin_load_reports_a_missing_zip_immediately() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );
		$this->set_store_address( 'US:CO', '', 'Dolores', '123 Main St' );

		$this->sut->maybe_schedule_check();

		$this->assertFalse( wp_next_scheduled( StoreAddressVerifier::CRON_HOOK ) );
		$this->assertSame( StoreAddressVerifier::STATUS_ZIP_MISSING, $this->sut->get_current_result()['status'] );
	}

	/* ──── dismiss() / mark_verified() ──── */

	/**
	 * @testdox Dismissing hides the result for this address, and a new address brings a fresh one.
	 */
	public function test_dismiss_lasts_until_the_address_changes() {
		$this->api_client->method( 'proxy_request' )->willReturn( $this->response( 404, '{}' ) );
		$this->sut->verify();

		$this->sut->dismiss();
		$this->assertTrue( $this->sut->get_current_result()['dismissed'] );

		$this->set_store_address( 'US:CO', '80202', 'Denver', '1 Main St' );
		$this->sut->verify();
		$this->assertFalse( $this->sut->get_current_result()['dismissed'] );
	}

	/**
	 * @testdox mark_verified() stores the current address as verified without calling TaxJar.
	 */
	public function test_mark_verified_does_not_call_taxjar() {
		$this->api_client->expects( $this->never() )->method( 'proxy_request' );

		$this->sut->mark_verified();

		$this->assertSame( StoreAddressVerifier::STATUS_VERIFIED, $this->sut->get_current_result()['status'] );
	}
}
