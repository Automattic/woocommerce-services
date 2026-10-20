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
	 * $100 product in the cart.
	 *
	 * @var WC_Product
	 */
	private $product;

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
			function () {
				++$this->proxy_requests;

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
}
