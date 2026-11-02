<?php
/**
 * Tests for TaxJar lookups that fail because TaxJar or the proxy is down.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WCServices\StoreNotices\StoreNoticesNotifier;

/**
 * Class WP_Test_WC_Connect_TaxJar_Failed_Lookup
 *
 * Only 200, 400 and 404 answers are cached. A server error, a rate limit or a timeout
 * was asked again every time the cart was totalled, and phone order plugins total it
 * several times per request. A failed lookup is now remembered until the request ends.
 *
 * A shopper waits on every cart total, and each checkout step is its own request. So a
 * cart lookup also has a short time limit, and its failure is remembered for a minute
 * across requests. Placing the order asks once more, because that total is what the
 * shopper pays.
 */
class WP_Test_WC_Connect_TaxJar_Failed_Lookup extends WC_Unit_Test_Case {

	/**
	 * Number of requests sent to the proxy.
	 *
	 * @var int
	 */
	private $proxy_requests = 0;

	/**
	 * What the proxy answers.
	 *
	 * @var array|WP_Error
	 */
	private $proxy_response;

	/**
	 * Time limit each request to the proxy would be sent with.
	 *
	 * @var int[]
	 */
	private $seen_timeouts = array();

	/**
	 * Time limit another URL would have been sent with during the last request to the proxy.
	 *
	 * @var int|null
	 */
	private $seen_other_url_timeout = null;

	/**
	 * Body of the last request sent to the proxy.
	 *
	 * @var string|null
	 */
	private $last_lookup_body = null;

	/**
	 * Failures remembered across requests, as transient name => time in seconds.
	 *
	 * @var array<string, int>
	 */
	private $remembered_failures = array();

	/**
	 * $100 product in the cart.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * WC()->session before a test set one up, put back in tear_down(). False when untouched.
	 *
	 * @var WC_Session|null|false
	 */
	private $saved_wc_session = false;

	/**
	 * Load required classes before running tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-functions.php';
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		// Hooks added here are restored by the parent.
		if ( $this->product ) {
			WC()->cart->empty_cart();
			WC_Helper_Product::delete_product( $this->product->get_id() );
			delete_option( 'woocommerce_calc_taxes' );
		}

		if ( false !== $this->saved_wc_session ) {
			StoreNoticesNotifier::clear_notices();
			WC()->session           = $this->saved_wc_session;
			$this->saved_wc_session = false;
		}

		parent::tear_down();
	}

	/**
	 * A new integration, as each request builds one, whose proxy answers with $this->proxy_response.
	 *
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function new_integration() {
		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )
			->disableOriginalConstructor()
			->getMock();
		$api_client->method( 'proxy_request' )->willReturnCallback(
			function ( $path = '', $args = array() ) {
				++$this->proxy_requests;
				$this->last_lookup_body = $args['body'] ?? null;

				// proxy_request() sends every request with a 60 s limit, then WP_Http runs this filter.
				$sent                  = apply_filters( 'http_request_args', array( 'timeout' => 60 ), 'https://api.woocommerce.com/' . $path );
				$this->seen_timeouts[] = $sent['timeout'];

				$other                        = apply_filters( 'http_request_args', array( 'timeout' => 60 ), 'https://api.woocommerce.com/paypal/nvp/live' );
				$this->seen_other_url_timeout = $other['timeout'];

				if ( $this->proxy_response instanceof Exception ) {
					throw $this->proxy_response;
				}

				return $this->proxy_response;
			}
		);

		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->getMock();

		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )
			->disableOriginalConstructor()
			->getMock();

		// Real constructor, so the cache times are set. The store is in Marquette, MI.
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->setConstructorArgs( array( $api_client, $logger, 'https://example.com', $tracks, new StoreNoticesNotifier( false ) ) )
			->onlyMethods( array( 'get_store_settings' ) )
			->getMock();
		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'MI',
				'postcode' => '49855',
				'city'     => 'Marquette',
				'street'   => '1 Main St',
			)
		);

		return $integration;
	}

	/**
	 * A TaxJar request body for an address in Gwinn, MI.
	 *
	 * @param string $to_zip Destination ZIP.
	 * @return string
	 */
	private function request_body( $to_zip = '49841' ) {
		return wp_json_encode(
			array(
				'from_country' => 'US',
				'from_state'   => 'MI',
				'from_zip'     => '49855',
				'from_city'    => 'Marquette',
				'to_country'   => 'US',
				'to_state'     => 'MI',
				'to_zip'       => $to_zip,
				'to_city'      => 'Gwinn',
				'shipping'     => '0',
				'plugin'       => 'woo',
				'line_items'   => array(
					array(
						'id'               => '1-abc-0',
						'quantity'         => 1,
						'product_tax_code' => '',
						'unit_price'       => '100.00',
						'discount'         => '0',
					),
				),
			)
		);
	}

	/**
	 * A proxy answer with the given HTTP status.
	 *
	 * @param int $code HTTP status.
	 * @return array
	 */
	private function proxy_answer( $code ) {
		return array(
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'body'     => '{"error":"Error","detail":"Something went wrong"}',
		);
	}

	/**
	 * Answers that mean TaxJar or the proxy could not answer. None of them is cached.
	 *
	 * @return array
	 */
	public function provide_outages() {
		return array(
			'server error 500' => array( 500 ),
			'bad gateway 502'  => array( 502 ),
			'unavailable 503'  => array( 503 ),
			'rate limited 429' => array( 429 ),
			'unauthorized 401' => array( 401 ),
			'forbidden 403'    => array( 403 ),
			'timeout'          => array( 'timeout' ),
		);
	}

	/**
	 * Point the proxy at the given outage.
	 *
	 * @param int|string $outage HTTP status, or 'timeout' for a request that never got an answer.
	 */
	private function proxy_answers_with( $outage ) {
		$this->proxy_response = 'timeout' === $outage
			? new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out after 60000 milliseconds' )
			: $this->proxy_answer( $outage );
	}

	/**
	 * A lookup that failed is not sent again in the same request, and the second answer is the same.
	 *
	 * @dataProvider provide_outages
	 *
	 * @param int|string $outage HTTP status, or 'timeout'.
	 */
	public function test_failed_lookup_is_sent_once_per_request( $outage ) {
		$this->proxy_answers_with( $outage );
		$integration = $this->new_integration();

		$first  = $integration->smartcalcs_cache_request( $this->request_body(), 'MI' );
		$second = $integration->smartcalcs_cache_request( $this->request_body(), 'MI' );

		$this->assertSame( 1, $this->proxy_requests );
		$this->assertEmpty( $first );
		$this->assertSame( $first, $second );
	}

	/**
	 * The next request asks again: a failed lookup is not stored anywhere that outlives the request.
	 *
	 * @dataProvider provide_outages
	 *
	 * @param int|string $outage HTTP status, or 'timeout'.
	 */
	public function test_failed_lookup_is_sent_again_in_the_next_request( $outage ) {
		$this->proxy_answers_with( $outage );

		$this->new_integration()->smartcalcs_cache_request( $this->request_body(), 'MI' );
		$this->new_integration()->smartcalcs_cache_request( $this->request_body(), 'MI' );

		$this->assertSame( 2, $this->proxy_requests );
	}

	/**
	 * A different cart is a different lookup, so it is still sent after another one failed.
	 */
	public function test_failed_lookup_does_not_stop_a_different_lookup() {
		$this->proxy_answers_with( 503 );
		$integration = $this->new_integration();

		$integration->smartcalcs_cache_request( $this->request_body( '49841' ), 'MI' );
		$integration->smartcalcs_cache_request( $this->request_body( '49855' ), 'MI' );

		$this->assertSame( 2, $this->proxy_requests );
	}

	/**
	 * Once TaxJar answers, the answer is used and cached as before.
	 */
	public function test_lookup_that_succeeds_after_a_new_request_is_used() {
		$this->proxy_answers_with( 503 );
		$this->new_integration()->smartcalcs_cache_request( $this->request_body(), 'MI' );

		$this->proxy_response = array(
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'body'     => '{"tax":{"amount_to_collect":6}}',
		);
		$integration          = $this->new_integration();
		$answer               = $integration->smartcalcs_cache_request( $this->request_body(), 'MI' );
		$integration->smartcalcs_cache_request( $this->request_body(), 'MI' );

		$this->assertSame( 2, $this->proxy_requests );
		$this->assertSame( $this->proxy_response, $answer );
	}

	/**
	 * A 400 answer is still cached for later requests, as before.
	 */
	public function test_bad_request_answer_is_still_cached_across_requests() {
		$this->proxy_answers_with( 400 );

		$this->new_integration()->smartcalcs_cache_request( $this->request_body(), 'MI' );
		$this->new_integration()->smartcalcs_cache_request( $this->request_body(), 'MI' );

		$this->assertSame( 1, $this->proxy_requests );
	}

	/**
	 * A lookup our own validation stopped sent nothing, so it is checked again every
	 * time. Each total clears the shopper's notices first, and checking again is what
	 * shows the notice about their ZIP code again, so it stays on the page.
	 */
	public function test_lookup_stopped_by_validation_shows_its_notice_after_every_total() {
		$this->saved_wc_session = WC()->session;
		if ( empty( WC()->session ) ) {
			WC()->initialize_session();
		}
		WC()->session->set( StoreNoticesNotifier::WC_SESSION_KEY, array() );

		$this->proxy_answers_with( 503 );
		$integration = $this->new_integration();

		$integration->smartcalcs_cache_request( $this->request_body( 'ABCDE' ), 'MI' );
		$this->assertNotEmpty( StoreNoticesNotifier::get_notices()['error'] ?? array(), 'The first total showed no notice, so this test proves nothing.' );

		$integration->smartcalcs_cache_request( $this->request_body( 'ABCDE' ), 'MI' );

		$this->assertNotEmpty( StoreNoticesNotifier::get_notices()['error'] ?? array(), 'The second total cleared the notice and did not show it again.' );
		$this->assertSame( 0, $this->proxy_requests );
	}

	/**
	 * During an outage, a checkout that totals the cart three times in one request,
	 * as phone order plugins do, sends one lookup instead of three.
	 */
	public function test_checkout_totalled_three_times_during_an_outage_sends_one_lookup() {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( 100 );
		$this->product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->customer->set_billing_location( 'US', 'MI', '49841', 'Gwinn' );
		WC()->customer->set_shipping_location( 'US', 'MI', '49841', 'Gwinn' );

		// Hooked after the cart is built, because add_to_cart() totals the cart too.
		$this->proxy_answers_with( 503 );
		$integration = $this->new_integration();
		add_action( 'woocommerce_after_calculate_totals', array( $integration, 'maybe_calculate_totals' ), 20 );
		add_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $integration, 'override_cart_item_tax_rates' ), 10, 3 );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		WC()->cart->calculate_totals();
		WC()->cart->calculate_totals();
		WC()->cart->calculate_totals();

		$this->assertSame( 1, $this->proxy_requests );
	}

	/**
	 * Fill the cart with the $100 product, for an address in Gwinn, MI.
	 *
	 * @param string $postcode Customer postcode.
	 */
	private function fill_cart( $postcode = '49841' ) {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( 100 );
		$this->product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$this->set_customer_postcode( $postcode );
		add_filter( 'woocommerce_is_checkout', '__return_true' );
		add_action(
			'set_transient',
			function ( $transient, $value, $expiration ) {
				if ( false !== strpos( $transient, 'failed' ) ) {
					$this->remembered_failures[ $transient ] = $expiration;
				}
			},
			10,
			3
		);
	}

	/**
	 * Set the customer's postcode.
	 *
	 * @param string $postcode Customer postcode.
	 */
	private function set_customer_postcode( $postcode ) {
		WC()->customer->set_billing_location( 'US', 'MI', $postcode, 'Gwinn' );
		WC()->customer->set_shipping_location( 'US', 'MI', $postcode, 'Gwinn' );
	}

	/**
	 * Total the cart the way a new request does: with a new integration and nothing kept in memory.
	 */
	private function total_cart_in_new_request() {
		// calculate_totals() adds its hook back, so remove it by priority.
		remove_all_actions( 'woocommerce_after_calculate_totals', 20 );
		remove_all_filters( 'woocommerce_cart_totals_get_item_tax_rates', 10 );

		$integration = $this->new_integration();
		add_action( 'woocommerce_after_calculate_totals', array( $integration, 'maybe_calculate_totals' ), 20 );
		add_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $integration, 'override_cart_item_tax_rates' ), 10, 3 );

		WC()->cart->calculate_totals();
	}

	/**
	 * The two ways an order gets placed, as the action that says so.
	 *
	 * @return array
	 */
	public function provide_place_order_actions() {
		return array(
			'block checkout'   => array( 'woocommerce_store_api_checkout_update_customer_from_request' ),
			'classic checkout' => array( 'woocommerce_before_checkout_process' ),
		);
	}

	/**
	 * Fire the action that says an order is being placed.
	 *
	 * @param string $action Action name.
	 */
	private function start_placing_order( $action ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- WooCommerce core actions.
		do_action( $action, WC()->customer, new WP_REST_Request() );
	}

	/**
	 * A cart lookup that failed is not sent again by the next checkout step.
	 *
	 * @dataProvider provide_outages
	 *
	 * @param int|string $outage HTTP status, or 'timeout'.
	 */
	public function test_failed_cart_lookup_is_not_sent_again_by_the_next_request( $outage ) {
		$this->fill_cart();
		$this->proxy_answers_with( $outage );
		$this->proxy_requests = 0;

		$this->total_cart_in_new_request();
		$this->total_cart_in_new_request();
		$this->total_cart_in_new_request();

		$this->assertSame( 1, $this->proxy_requests );
	}

	/**
	 * A failure is remembered for one minute, not for the five minutes of a cached error answer.
	 */
	public function test_failed_cart_lookup_is_remembered_for_one_minute() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );

		$this->total_cart_in_new_request();

		$this->assertSame( array( 60 ), array_values( $this->remembered_failures ) );
	}

	/**
	 * Once the minute is over, the next cart total asks again.
	 */
	public function test_cart_lookup_is_sent_again_when_the_failure_has_expired() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->proxy_requests = 0;

		$this->total_cart_in_new_request();
		$this->assertCount( 1, $this->remembered_failures );
		delete_transient( array_keys( $this->remembered_failures )[0] );
		$this->total_cart_in_new_request();

		$this->assertSame( 2, $this->proxy_requests );
	}

	/**
	 * One address failing does not stop the lookup for another address.
	 */
	public function test_failed_cart_lookup_does_not_stop_a_different_lookup() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->proxy_requests = 0;

		$this->total_cart_in_new_request();
		$this->set_customer_postcode( '49855' );
		$this->total_cart_in_new_request();

		$this->assertSame( 2, $this->proxy_requests );
	}

	/**
	 * Placing the order asks again after a failure, once, even if the cart is totalled several times.
	 *
	 * @dataProvider provide_place_order_actions
	 *
	 * @param string $action Action that says an order is being placed.
	 */
	public function test_placing_the_order_asks_once_more_after_a_failure( $action ) {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->proxy_requests = 0;
		$this->total_cart_in_new_request();

		$this->start_placing_order( $action );
		$this->total_cart_in_new_request();

		$this->assertSame( 2, $this->proxy_requests );
	}

	/**
	 * In the request that places the order, a cart total repeated with the same
	 * integration is answered from memory, so one order costs one wait.
	 *
	 * @dataProvider provide_place_order_actions
	 *
	 * @param string $action Action that says an order is being placed.
	 */
	public function test_placing_the_order_waits_once_when_the_cart_is_totalled_twice( $action ) {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->proxy_requests = 0;
		$this->total_cart_in_new_request();
		$this->proxy_requests = 0;

		$this->start_placing_order( $action );
		$this->total_cart_in_new_request();
		WC()->cart->calculate_totals();

		$this->assertSame( 1, $this->proxy_requests );
	}

	/**
	 * A normal order sends one lookup. Placing it uses the cached answer and adds no call.
	 *
	 * @dataProvider provide_place_order_actions
	 *
	 * @param string $action Action that says an order is being placed.
	 */
	public function test_normal_order_sends_no_extra_lookup_when_placed( $action ) {
		$this->fill_cart();
		$this->proxy_response = $this->proxy_answer( 200 );
		$this->proxy_requests = 0;

		$this->total_cart_in_new_request();
		$this->start_placing_order( $action );
		$this->total_cart_in_new_request();

		$this->assertSame( 1, $this->proxy_requests );
		$this->assertSame( array(), $this->remembered_failures );
	}

	/**
	 * When the retry at Place Order gets an answer, the next total uses it and asks nothing.
	 */
	public function test_answer_from_the_retry_replaces_the_failure() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->proxy_requests = 0;
		$this->total_cart_in_new_request();

		$this->proxy_response = $this->proxy_answer( 200 );
		$this->start_placing_order( 'woocommerce_before_checkout_process' );
		$this->total_cart_in_new_request();
		$this->total_cart_in_new_request();

		$this->assertSame( 2, $this->proxy_requests );
	}

	/**
	 * Answers that are cached, or lookups that never went out, are not outages to remember.
	 * A remembered validation stop would lose the shopper's ZIP notice after the first total.
	 */
	public function test_only_failed_lookups_are_remembered() {
		$this->fill_cart();
		$this->proxy_response = $this->proxy_answer( 400 );
		$this->total_cart_in_new_request();
		$this->assertSame( array(), $this->remembered_failures, 'A 400 answer has its own cache.' );

		$this->proxy_response = $this->proxy_answer( 200 );
		$this->set_customer_postcode( '49855' );
		$this->total_cart_in_new_request();
		$this->assertSame( array(), $this->remembered_failures, 'A 200 answer has its own cache.' );

		$this->set_customer_postcode( 'ABCDE' );
		$this->total_cart_in_new_request();
		$this->assertSame( array(), $this->remembered_failures, 'Validation stopped this lookup, so nothing went out.' );
	}

	/**
	 * A cart lookup gets 10 seconds, and only that request gets the shorter limit.
	 */
	public function test_cart_lookup_has_a_short_time_limit_for_that_call_only() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->seen_timeouts = array();

		$this->total_cart_in_new_request();

		$this->assertSame( array( 10 ), $this->seen_timeouts );

		$after = apply_filters( 'http_request_args', array( 'timeout' => 60 ), 'https://api.woocommerce.com/taxjar/v2/taxes' );
		$this->assertSame( 60, $after['timeout'], 'The shorter time limit must not outlive the call.' );
	}

	/**
	 * Placing the order gets the same short limit.
	 */
	public function test_retry_when_placing_the_order_has_the_short_time_limit() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->total_cart_in_new_request();
		$this->seen_timeouts = array();

		$this->start_placing_order( 'woocommerce_before_checkout_process' );
		$this->total_cart_in_new_request();

		$this->assertSame( array( 10 ), $this->seen_timeouts );
	}

	/**
	 * While a cart lookup runs, other requests through the proxy keep their own limit.
	 */
	public function test_short_time_limit_leaves_other_urls_alone() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );

		$this->total_cart_in_new_request();

		$this->assertSame( 60, $this->seen_other_url_timeout );
	}

	/**
	 * A lookup for an order keeps the long time limit and asks even if a cart lookup just failed.
	 * Nobody is waiting at a checkout, and a failed answer there keeps the order's saved tax.
	 */
	public function test_order_lookup_keeps_its_time_limit_and_ignores_a_remembered_failure() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->total_cart_in_new_request();
		$body                 = $this->last_lookup_body;
		$this->proxy_requests = 0;
		$this->seen_timeouts  = array();

		$this->new_integration()->smartcalcs_cache_request( $body, 'MI' );

		$this->assertSame( 1, $this->proxy_requests );
		$this->assertSame( array( 60 ), $this->seen_timeouts );
	}

	/**
	 * A failed order lookup is not remembered, so it cannot stop a later cart lookup.
	 */
	public function test_failed_order_lookup_is_not_remembered() {
		$this->fill_cart();
		$this->proxy_answers_with( 'timeout' );
		$this->total_cart_in_new_request();
		$body = $this->last_lookup_body;
		delete_transient( array_keys( $this->remembered_failures )[0] );
		$this->remembered_failures = array();

		$this->new_integration()->smartcalcs_cache_request( $body, 'MI' );

		$this->assertSame( array(), $this->remembered_failures );
	}

	/**
	 * A callback throwing during a cart lookup does not leave the short limit behind.
	 */
	public function test_exception_during_a_cart_lookup_leaves_nothing_behind() {
		$this->fill_cart();
		$this->proxy_response = new RuntimeException( 'A callback threw.' );

		remove_all_actions( 'woocommerce_after_calculate_totals', 20 );
		remove_all_filters( 'woocommerce_cart_totals_get_item_tax_rates', 10 );
		$integration = $this->new_integration();
		add_action( 'woocommerce_after_calculate_totals', array( $integration, 'maybe_calculate_totals' ), 20 );

		try {
			WC()->cart->calculate_totals();
			$this->fail( 'The exception should reach the caller.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'A callback threw.', $e->getMessage() );
		}

		$after = apply_filters( 'http_request_args', array( 'timeout' => 60 ), 'https://api.woocommerce.com/taxjar/v2/taxes' );
		$this->assertSame( 60, $after['timeout'] );

		// The same integration then looks up an order, which is not a cart lookup.
		$this->proxy_answers_with( 'timeout' );
		$this->seen_timeouts = array();
		$integration->smartcalcs_cache_request( $this->request_body( '49855' ), 'MI' );
		$this->assertSame( array( 60 ), $this->seen_timeouts );
	}
}
