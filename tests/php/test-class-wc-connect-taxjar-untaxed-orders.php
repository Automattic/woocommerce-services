<?php
/**
 * Tests for orders that get no tax because the tax service did not answer.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WCServices\StoreNotices\StoreNoticesNotifier;
use Automattic\WCServices\Tax\UntaxedOrdersNotice;

/**
 * Class WP_Test_WC_Connect_TaxJar_Untaxed_Orders
 *
 * The rule: when the tax lookup gets no answer (an outage, a timeout, a server error)
 * and the store has no saved tax rates for the address, the order is placed or kept
 * with $0 tax, and the merchant is told with an order note and an admin notice. The
 * same at checkout (classic, block, a phone order plugin's admin cart), on an unpaid
 * order's Recalculate, and for a new order made through the REST API.
 *
 * With saved rates for the address, WooCommerce taxes the order from them, as before,
 * and nothing new is said. An address the tax service refused (a 400) is not this case.
 *
 * The store is in Denver, CO. The customer is in Denver too. The fake tax service
 * answers 6% (2.9% state, 3.1% city) when it answers.
 */
class WP_Test_WC_Connect_TaxJar_Untaxed_Orders extends WC_Unit_Test_Case {

	/**
	 * How the fake tax service answers: 'answer', 'no_tax' (an answer of 0%, as for an
	 * exempt product), 'timeout', an HTTP status for an outage, 'unreadable' for a 200
	 * that is not an answer, or 'zip_not_in_state' (a 400) or 'not_found' (a 404) for
	 * an answer about the address.
	 *
	 * @var string|int
	 */
	private $taxjar = 'answer';

	/**
	 * Lookups sent to the fake tax service.
	 *
	 * @var int
	 */
	private $lookups = 0;

	/**
	 * The integration, hooked by init() as on a store.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $integration;

	/**
	 * Products created for the fixture.
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Carts the woocommerce_services_cart_tax_lookup_failed action was fired with.
	 *
	 * @var WC_Cart[]
	 */
	private $failed_carts = array();

	/**
	 * Load the integration's dependencies.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-custom-surcharge.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-functions.php';
	}

	/**
	 * A Denver store with automated taxes on and an empty rate table.
	 */
	public function set_up() {
		parent::set_up();

		$this->reset_rate_table();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_default_country', 'US:CO' );
		update_option( 'woocommerce_store_address', '1 Store St' );
		update_option( 'woocommerce_store_city', 'Denver' );
		update_option( 'woocommerce_store_postcode', '80202' );
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'yes' );
		delete_option( UntaxedOrdersNotice::OPTION_NAME );

		$this->taxjar       = 'answer';
		$this->lookups      = 0;
		$this->failed_carts = array();

		$this->integration = $this->make_integration();

		add_action(
			'woocommerce_services_cart_tax_lookup_failed',
			function ( $cart ) {
				$this->failed_carts[] = $cart;
			}
		);

		WC()->customer->set_is_vat_exempt( false );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * A new integration, hooked by init() as on a store.
	 *
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function make_integration() {
		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock();
		$api_client->method( 'proxy_request' )->willReturnCallback( array( $this, 'fake_taxjar' ) );
		$logger = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$tracks = $this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock();

		$integration = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks, new StoreNoticesNotifier( false ) );
		$integration->init();

		return $integration;
	}

	/**
	 * Start the next request: unhook the integration and hook a new one, which remembers
	 * nothing from this request. The order, the session and the cart stay, as they do
	 * between page loads.
	 */
	private function start_new_request() {
		global $wp_filter;

		foreach ( $wp_filter as $hook_name => $hook ) {
			foreach ( $hook->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$function = $callback['function'];
					$owner    = is_array( $function ) ? $function[0] : null;
					if ( $function instanceof Closure ) {
						$owner = ( new ReflectionFunction( $function ) )->getClosureThis();
					}
					if ( $owner === $this->integration ) {
						remove_filter( $hook_name, $function, $priority );
					}
				}
			}
		}

		$this->integration = $this->make_integration();
	}

	/**
	 * Remove the fixture rows, products, options and request state.
	 */
	public function tear_down() {
		WC()->cart->empty_cart();
		WC()->session->set( 'order_awaiting_payment', null );
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC()->session->set( 'shipping_for_package_0', null );
		$this->reset_rate_table();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%tj_tax_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		foreach ( $this->products as $product ) {
			$product->delete( true );
		}
		$this->products = array();

		unset( $GLOBALS['current_screen'], $GLOBALS['wp']->query_vars['rest_route'] );
		$_POST = array();
		foreach ( array( 'woocommerce_calc_taxes', 'woocommerce_tax_based_on', 'woocommerce_store_address', 'woocommerce_store_city', 'woocommerce_store_postcode', WC_Connect_TaxJar_Integration::OPTION_NAME, UntaxedOrdersNotice::OPTION_NAME ) as $option ) {
			delete_option( $option );
		}

		parent::tear_down();
	}

	/**
	 * Empty the rate table.
	 */
	private function reset_rate_table() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );
	}

	/**
	 * Drop the tax service's cached answers.
	 */
	private function forget_cached_answers() {
		global $wpdb;
		$names = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE '\\_transient\\_tj\\_tax\\_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		foreach ( $names as $name ) {
			delete_transient( substr( $name, strlen( '_transient_' ) ) );
		}
	}

	/**
	 * Fake tax service, through the proxy.
	 *
	 * @param string $path Proxy path.
	 * @param array  $args Request args.
	 * @return array|WP_Error
	 */
	public function fake_taxjar( $path, $args ) {
		++$this->lookups;
		$body = json_decode( $args['body'], true );

		if ( 'timeout' === $this->taxjar ) {
			return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		}

		if ( is_int( $this->taxjar ) ) {
			return array(
				'response' => array( 'code' => $this->taxjar ),
				'body'     => '{"error":"Error","detail":"Something went wrong"}',
			);
		}

		if ( 'unreadable' === $this->taxjar ) {
			return array(
				'response' => array( 'code' => 200 ),
				'body'     => '<html><body>Bad gateway</body></html>',
			);
		}

		if ( 'not_found' === $this->taxjar ) {
			return array(
				'response' => array( 'code' => 404 ),
				'body'     => '{"status":404,"error":"Not Found","detail":"No tax rates found for the address"}',
			);
		}

		if ( 'zip_not_in_state' === $this->taxjar ) {
			return array(
				'response' => array( 'code' => 400 ),
				'body'     => wp_json_encode(
					array(
						'status' => 400,
						'error'  => 'Bad Request',
						'detail' => 'to_zip ' . $body['to_zip'] . ' is not used within to_state ' . $body['to_state'],
					)
				),
			);
		}

		$rates      = array(
			'state_tax_rate' => 'no_tax' === $this->taxjar ? 0 : 0.029,
			'city_tax_rate'  => 'no_tax' === $this->taxjar ? 0 : 0.031,
		);
		$line_items = array();
		foreach ( $body['line_items'] ?? array() as $line_item ) {
			$line_items[] = array_merge( array( 'id' => $line_item['id'] ), $rates, array( 'combined_tax_rate' => array_sum( $rates ) ) );
		}

		return array(
			'response' => array( 'code' => 200 ),
			'body'     => wp_json_encode(
				array(
					'tax' => array(
						'rate'            => array_sum( $rates ),
						'has_nexus'       => true,
						'freight_taxable' => true,
						'jurisdictions'   => array(
							'country' => 'US',
							'state'   => 'CO',
							'county'  => 'DENVER',
							'city'    => 'DENVER',
						),
						'breakdown'       => array(
							'line_items' => $line_items,
							'shipping'   => array_merge( $rates, array( 'combined_tax_rate' => array_sum( $rates ) ) ),
						),
					),
				)
			),
		);
	}

	// -------------------------------------------------------------------------
	// Fixture helpers
	// -------------------------------------------------------------------------

	/**
	 * Ways the tax service fails to answer.
	 *
	 * @return array
	 */
	public function provide_no_answer() {
		return array(
			'timeout'          => array( 'timeout' ),
			'server error 500' => array( 500 ),
			'unavailable 503'  => array( 503 ),
			'rate limited 429' => array( 429 ),
			'unreadable 200'   => array( 'unreadable' ),
		);
	}

	/**
	 * Ways the tax service answers about the address instead of with tax.
	 *
	 * @return array
	 */
	public function provide_refused_address() {
		return array(
			'zip not in state 400' => array( 'zip_not_in_state' ),
			'not found 404'        => array( 'not_found' ),
		);
	}

	/**
	 * A $100 product.
	 *
	 * @return WC_Product
	 */
	private function create_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( '100' );
		$product->save();
		$this->products[] = $product;

		return $product;
	}

	/**
	 * A saved 6% Colorado rate, as an earlier lookup or the merchant leaves in the table.
	 */
	private function save_colorado_rate() {
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '6.0000',
				'tax_rate_name'     => 'CO Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * The customer's address, in the order's field shape.
	 *
	 * @return array
	 */
	private static function address() {
		return array(
			'country'   => 'US',
			'state'     => 'CO',
			'postcode'  => '80202',
			'city'      => 'Denver',
			'address_1' => '1 Test St',
			'email'     => 'shopper@example.com',
		);
	}

	/**
	 * A cart with the $100 product, for a Denver customer, totalled on the checkout page.
	 */
	private function fill_cart_at_checkout() {
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->create_product()->get_id(), 1 );
		WC()->customer->set_billing_location( 'US', 'CO', '80202', 'Denver' );
		WC()->customer->set_shipping_location( 'US', 'CO', '80202', 'Denver' );
		WC()->customer->set_billing_address_1( '1 Test St' );
		WC()->customer->set_shipping_address_1( '1 Test St' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );

		WC()->cart->calculate_totals();
	}

	/**
	 * Create the order from the cart with WC_Checkout::create_order(), as classic
	 * checkout and phone order plugins do.
	 *
	 * @return WC_Order
	 */
	private function checkout_order() {
		$order_id = WC()->checkout()->create_order(
			array(
				'billing_email'     => 'shopper@example.com',
				'payment_method'    => 'bacs',
				'billing_country'   => 'US',
				'billing_state'     => 'CO',
				'billing_postcode'  => '80202',
				'billing_city'      => 'Denver',
				'billing_address_1' => '1 Test St',
				'shipping_country'  => 'US',
				'shipping_state'    => 'CO',
				'shipping_postcode' => '80202',
				'shipping_city'     => 'Denver',
			)
		);
		$this->assertIsInt( $order_id, 'The order was not created.' );

		return wc_get_order( $order_id );
	}

	/**
	 * Create the order from the cart as the Store API's checkout does, and fire its action.
	 *
	 * @param string        $status Status the order has when the action fires.
	 * @param WC_Order|null $retry  An unpaid order for the same cart, which the Store API
	 *                              places again when the checkout is retried.
	 * @return WC_Order
	 */
	private function store_api_order( $status = 'pending', $retry = null ) {
		$order = new WC_Order();
		if ( null !== $retry ) {
			$order = wc_get_order( $retry->get_id() );
			$order->remove_order_items();
		}
		WC()->checkout()->set_data_from_cart( $order );
		$order->set_status( $status );
		$order->save();

		do_action( 'woocommerce_store_api_checkout_order_processed', $order );

		return wc_get_order( $order->get_id() );
	}

	/**
	 * An unpaid order made in the admin: the $100 product in Denver, no tax yet.
	 *
	 * @param string $status Order status.
	 * @return WC_Order
	 */
	private function admin_order( $status = 'pending' ) {
		$order = wc_create_order();
		$order->set_address( self::address(), 'billing' );
		$order->set_address( self::address(), 'shipping' );
		$order->add_product( $this->create_product(), 1 );
		$order->set_status( $status );
		$order->calculate_totals( false );
		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Press the order screen's Recalculate button for a Denver address.
	 *
	 * @param WC_Order $order Order.
	 * @return WC_Order The order read back.
	 */
	private function press_recalculate( WC_Order $order ) {
		global $wp_filter;

		$post  = array(
			'order_id' => $order->get_id(),
			'items'    => '',
			'country'  => 'US',
			'state'    => 'CO',
			'postcode' => '80202',
			'city'     => 'Denver',
			'street'   => '1 Test St',
		);
		$_POST = $post; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- The request Recalculate posts.

		$saved_hook = $wp_filter['wp_ajax_woocommerce_calc_line_taxes'] ?? null;
		remove_all_actions( 'wp_ajax_woocommerce_calc_line_taxes' );
		add_action(
			'wp_ajax_woocommerce_calc_line_taxes',
			static function () use ( $post ) {
				wc_get_container()->get( Automattic\WooCommerce\Internal\Orders\TaxesController::class )->calc_line_taxes( $post );
			}
		);
		add_filter( 'wp_doing_ajax', '__return_true' );

		try {
			do_action( 'wp_ajax_woocommerce_calc_line_taxes' );
		} finally {
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_all_actions( 'wp_ajax_woocommerce_calc_line_taxes' );
			if ( null !== $saved_hook ) {
				$wp_filter['wp_ajax_woocommerce_calc_line_taxes'] = $saved_hook; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restores the hook this helper swapped.
			}
			$_POST = array();
		}

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Create an order through the REST API: the $100 product to Denver.
	 *
	 * @return WC_Order
	 */
	private function rest_create() {
		$request = new WP_REST_Request( 'POST', '/wc/v3/orders' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'billing'    => self::address(),
					'shipping'   => self::address(),
					'line_items' => array(
						array(
							'product_id' => $this->create_product()->get_id(),
							'quantity'   => 1,
						),
					),
				)
			)
		);

		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 201, $response->get_status(), 'REST request failed: ' . wp_json_encode( $response->get_data() ) );

		return wc_get_order( $response->get_data()['id'] );
	}

	/**
	 * The order's notes, newest first.
	 *
	 * @param WC_Order $order Order.
	 * @return string[]
	 */
	private function notes( WC_Order $order ) {
		return wp_list_pluck( wc_get_order_notes( array( 'order_id' => $order->get_id() ) ), 'content' );
	}

	/**
	 * The notes that say the order got no tax because the tax service did not answer.
	 *
	 * @param WC_Order $order Order.
	 * @return string[]
	 */
	private function untaxed_notes( WC_Order $order ) {
		return array_values(
			array_filter(
				$this->notes( $order ),
				static function ( $note ) {
					return 'Sales tax could not be calculated for this order, because the sales tax service did not answer and the store has no saved tax rates for the order\'s address.' === $note;
				}
			)
		);
	}

	/**
	 * Order ids the admin notice lists.
	 *
	 * @return int[]
	 */
	private function noticed_order_ids() {
		return ( new UntaxedOrdersNotice() )->get_stored()['order_ids'];
	}

	/**
	 * Assert the order got no tax, one note that says why, and a place in the admin notice.
	 *
	 * @param WC_Order $order Order.
	 */
	private function assert_untaxed_and_told( WC_Order $order ) {
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001, 'The order has no tax.' );
		$this->assertCount( 1, $this->untaxed_notes( $order ), 'One note says why. Notes: ' . wp_json_encode( $this->notes( $order ) ) );
		$this->assertSame( array( $order->get_id() ), $this->noticed_order_ids(), 'The admin notice lists the order.' );
	}

	/**
	 * Assert the order got the saved or looked-up 6% and nothing new is said.
	 *
	 * @param WC_Order $order Order.
	 */
	private function assert_taxed_and_quiet( WC_Order $order ) {
		$this->assertEqualsWithDelta( 6.0, (float) $order->get_total_tax(), 0.001, 'The order is taxed at 6%.' );
		$this->assertSame( array(), $this->untaxed_notes( $order ), 'No note about an unanswered lookup.' );
		$this->assertSame( array(), $this->noticed_order_ids(), 'The admin notice lists nothing.' );
	}

	// -------------------------------------------------------------------------
	// Checkout
	// -------------------------------------------------------------------------

	/**
	 * @testdox Checkout with no answer and no saved rates: the order has no tax, a note and a place in the notice.
	 *
	 * @dataProvider provide_no_answer
	 *
	 * @param string|int $outage How the tax service fails.
	 */
	public function test_checkout_without_an_answer_or_saved_rates_is_untaxed_and_told( $outage ) {
		$this->taxjar = $outage;
		$this->fill_cart_at_checkout();

		$this->assert_untaxed_and_told( $this->checkout_order() );
	}

	/**
	 * @testdox Checkout with no answer and a saved rate: the saved rate taxes it, as before, and nothing is said.
	 */
	public function test_checkout_without_an_answer_uses_saved_rates_quietly() {
		$this->save_colorado_rate();
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();

		$this->assert_taxed_and_quiet( $this->checkout_order() );
	}

	/**
	 * @testdox Checkout with an answer: taxed, nothing is said.
	 */
	public function test_checkout_with_an_answer_is_quiet() {
		$this->fill_cart_at_checkout();

		$this->assert_taxed_and_quiet( $this->checkout_order() );
		$this->assertGreaterThan( 0, $this->lookups );
	}

	/**
	 * @testdox Checkout for an address the tax service refused is not this case: no note.
	 *
	 * @dataProvider provide_refused_address
	 *
	 * @param string $refusal How the tax service refuses the address.
	 */
	public function test_checkout_for_a_refused_address_adds_no_note( $refusal ) {
		$this->taxjar = $refusal;
		$this->fill_cart_at_checkout();
		$order = $this->checkout_order();

		$this->assertGreaterThan( 0, $this->lookups, 'The address was sent.' );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	/**
	 * @testdox An order with nothing taxable on it is not noted.
	 */
	public function test_checkout_of_untaxable_products_adds_no_note() {
		$this->taxjar = 503;
		WC()->cart->empty_cart();
		$product = $this->create_product();
		$product->set_tax_status( 'none' );
		$product->save();
		WC()->cart->add_to_cart( $product->get_id(), 1 );
		WC()->customer->set_shipping_location( 'US', 'CO', '80202', 'Denver' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );
		WC()->cart->calculate_totals();

		$order = $this->checkout_order();

		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	/**
	 * A $100 product in WooCommerce's built-in Zero rate class.
	 *
	 * @return WC_Product
	 */
	private function create_zero_rate_product() {
		if ( ! in_array( 'zero-rate', WC_Tax::get_tax_class_slugs(), true ) ) {
			WC_Tax::create_tax_class( 'Zero rate' );
		}
		$product = $this->create_product();
		$product->set_tax_class( 'zero-rate' );
		$product->save();
		$this->assertSame( 'zero-rate', $product->get_tax_class(), 'The product is in the Zero rate class.' );

		return $product;
	}

	/**
	 * Check out a cart of these products for a Denver customer.
	 *
	 * @param WC_Product[] $products Products, one of each.
	 * @return WC_Order
	 */
	private function checkout_products( array $products ) {
		WC()->cart->empty_cart();
		foreach ( $products as $product ) {
			WC()->cart->add_to_cart( $product->get_id(), 1 );
		}
		WC()->customer->set_shipping_location( 'US', 'CO', '80202', 'Denver' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );
		WC()->cart->calculate_totals();

		return $this->checkout_order();
	}

	/**
	 * @testdox An order whose only taxable products are in the Zero rate class is not noted: the store charges them no tax anyway.
	 */
	public function test_checkout_of_zero_rate_products_adds_no_note() {
		$this->taxjar = 503;

		$order = $this->checkout_products( array( $this->create_zero_rate_product() ) );

		$this->assertGreaterThan( 0, $this->lookups, 'The lookup ran.' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	/**
	 * @testdox An order with a Zero rate product and a product no saved rate applies to is noted.
	 */
	public function test_checkout_of_zero_rate_and_standard_products_is_told() {
		$this->taxjar = 503;

		$this->assert_untaxed_and_told( $this->checkout_products( array( $this->create_zero_rate_product(), $this->create_product() ) ) );
	}

	/**
	 * @testdox An order a saved 0% rate applied to is not noted: the store has a saved rate for the address.
	 */
	public function test_checkout_with_a_saved_zero_percent_rate_adds_no_note() {
		WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'CO',
				'tax_rate'          => '0.0000',
				'tax_rate_name'     => 'CO Tax',
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate_order'    => 0,
				'tax_rate_class'    => '',
			)
		);
		$this->taxjar = 503;

		$order = $this->checkout_products( array( $this->create_product() ) );

		$this->assertGreaterThan( 0, $this->lookups, 'The lookup ran.' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	/**
	 * @testdox An order of Zero rate products with taxable shipping is noted when no saved rate applied to the shipping.
	 */
	public function test_checkout_of_zero_rate_products_with_shipping_is_told() {
		// The integration keeps shipping in the Standard class (woocommerce_shipping_tax_class '').
		$this->assertSame( '', get_option( 'woocommerce_shipping_tax_class' ), 'Shipping is in the Standard class.' );
		WC_Helper_Shipping::create_simple_flat_rate( 10 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );
		$this->taxjar = 503;

		$order = $this->checkout_products( array( $this->create_zero_rate_product() ) );

		$this->assertCount( 1, $order->get_items( 'shipping' ), 'The order has shipping.' );
		$this->assert_untaxed_and_told( $order );
	}

	/**
	 * @testdox An order of products in another tax class with no saved rates is noted: only the Zero rate class is skipped.
	 */
	public function test_checkout_of_products_in_a_class_with_no_saved_rates_is_told() {
		if ( ! in_array( 'reduced-rate', WC_Tax::get_tax_class_slugs(), true ) ) {
			WC_Tax::create_tax_class( 'Reduced rate' );
		}
		$product = $this->create_product();
		$product->set_tax_class( 'reduced-rate' );
		$product->save();
		$this->save_colorado_rate();
		$this->taxjar = 503;

		$this->assert_untaxed_and_told( $this->checkout_products( array( $product ) ) );
	}

	/**
	 * @testdox A later total in the same request that gets an answer clears the earlier failure.
	 */
	public function test_checkout_after_a_failure_then_an_answer_in_the_same_request_is_quiet() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();

		// The next total is for a different cart, so it is not a remembered failure.
		$this->taxjar = 'answer';
		WC()->cart->add_to_cart( $this->create_product()->get_id(), 1 );
		WC()->cart->calculate_totals();
		$order = $this->checkout_order();

		$this->assertEqualsWithDelta( 12.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
	}

	/**
	 * @testdox An answer of no tax after an earlier failure in the same request adds no note: the tax service said no tax is due.
	 */
	public function test_checkout_answered_with_no_tax_after_a_failure_in_the_same_request_is_quiet() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();

		$this->taxjar = 'no_tax';
		WC()->cart->add_to_cart( $this->create_product()->get_id(), 1 );
		WC()->cart->calculate_totals();
		$order = $this->checkout_order();

		$this->assertGreaterThan( 1, $this->lookups, 'The second total was answered.' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	/**
	 * @testdox Block checkout with no answer and no saved rates: the order has no tax, a note and a place in the notice.
	 */
	public function test_block_checkout_without_an_answer_or_saved_rates_is_untaxed_and_told() {
		$this->taxjar = 'timeout';
		$this->fill_cart_at_checkout();

		$this->assert_untaxed_and_told( $this->store_api_order() );
	}

	/**
	 * @testdox The block checkout's draft order gets no note: the order placed from it does.
	 */
	public function test_block_checkout_draft_is_not_noted() {
		$this->taxjar = 'timeout';
		$this->fill_cart_at_checkout();

		$order = $this->store_api_order( 'checkout-draft' );

		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	// -------------------------------------------------------------------------
	// The same order placed again: a resumed or retried checkout
	// -------------------------------------------------------------------------

	/**
	 * @testdox A checkout resumed in a later request for the same order during the outage adds no second note.
	 */
	public function test_resumed_checkout_during_an_outage_notes_the_order_once() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();
		$order = $this->checkout_order();
		$this->assert_untaxed_and_told( $order );
		// The payment did not go through: WooCommerce keeps the order for the cart.
		WC()->session->set( 'order_awaiting_payment', $order->get_id() );

		$this->start_new_request();
		WC()->cart->calculate_totals();
		$resumed = $this->checkout_order();

		$this->assertSame( $order->get_id(), $resumed->get_id(), 'WooCommerce resumed the same order.' );
		$this->assert_untaxed_and_told( $resumed );
		$this->assertSame( 1, ( new UntaxedOrdersNotice() )->get_stored()['count'], 'The notice counts the order once.' );
	}

	/**
	 * @testdox A block checkout retried in a later request for the same order during the outage adds no second note, and does not list the order again after a dismiss.
	 */
	public function test_retried_block_checkout_during_an_outage_notes_the_order_once() {
		$this->taxjar = 'timeout';
		$this->fill_cart_at_checkout();
		$order = $this->store_api_order();
		$this->assert_untaxed_and_told( $order );
		// The merchant dismissed the notice: they have been told about this order.
		delete_option( UntaxedOrdersNotice::OPTION_NAME );

		$this->start_new_request();
		WC()->cart->calculate_totals();
		$retried = $this->store_api_order( 'pending', $order );

		$this->assertSame( $order->get_id(), $retried->get_id(), 'The Store API placed the same order again.' );
		$this->assertEqualsWithDelta( 0.0, (float) $retried->get_total_tax(), 0.001 );
		$this->assertCount( 1, $this->untaxed_notes( $retried ), 'One note. Notes: ' . wp_json_encode( $this->notes( $retried ) ) );
		$this->assertSame( array(), $this->noticed_order_ids(), 'The order is not listed again.' );
	}

	/**
	 * @testdox Recalculate in a later request during the same outage adds no second note to an order noted at checkout.
	 */
	public function test_recalculate_during_the_same_outage_notes_a_checkout_order_once() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();
		$order = $this->checkout_order();

		$this->start_new_request();
		$order = $this->press_recalculate( $order );

		$this->assertGreaterThan( 1, $this->lookups, 'Recalculate asked again.' );
		$this->assert_untaxed_and_told( $order );
	}

	/**
	 * @testdox A REST update in a later request during the same outage adds no second note to an order noted when it was made.
	 */
	public function test_rest_update_during_the_same_outage_notes_the_order_once() {
		$this->taxjar = 503;
		$order        = $this->rest_create();
		$this->assert_untaxed_and_told( $order );
		$lookups = $this->lookups;

		$this->start_new_request();
		$request = new WP_REST_Request( 'PUT', '/wc/v3/orders/' . $order->get_id() );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'shipping' => array_merge( self::address(), array( 'postcode' => '80203' ) ) ) ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertSame( 200, $response->get_status(), 'REST request failed: ' . wp_json_encode( $response->get_data() ) );
		$order = wc_get_order( $order->get_id() );

		$this->assertGreaterThan( $lookups, $this->lookups, 'The new address was looked up.' );
		$this->assert_untaxed_and_told( $order );
		$this->assertCount( 1, $this->notes( $order ), 'Only that note: ' . wp_json_encode( $this->notes( $order ) ) );
	}

	/**
	 * @testdox An order taxed since its note is noted again when a later outage leaves it with no tax.
	 */
	public function test_order_taxed_since_its_note_is_noted_again_in_a_later_outage() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();
		$order = $this->checkout_order();

		$this->start_new_request();
		$this->taxjar = 'answer';
		$order        = $this->press_recalculate( $order );
		$this->assertEqualsWithDelta( 6.0, (float) $order->get_total_tax(), 0.001, 'Taxed once the tax service answered.' );

		$this->start_new_request();
		$this->reset_rate_table();
		$this->forget_cached_answers();
		$this->taxjar = 503;
		$order        = $this->press_recalculate( $order );

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertCount( 2, $this->untaxed_notes( $order ), 'Noted for each outage. Notes: ' . wp_json_encode( $this->notes( $order ) ) );
		$this->assertSame( array( $order->get_id() ), $this->noticed_order_ids() );
	}

	/**
	 * @testdox An order that gets tax later is taken off the notice; the other orders stay.
	 */
	public function test_order_taxed_later_is_taken_off_the_notice() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();
		$taxed_later = $this->checkout_order();
		$still_zero  = $this->press_recalculate( $this->admin_order() );
		$this->assertSame( array( $taxed_later->get_id(), $still_zero->get_id() ), $this->noticed_order_ids(), 'Both are listed.' );

		$this->start_new_request();
		$this->taxjar = 'answer';
		$taxed_later  = $this->press_recalculate( $taxed_later );

		$this->assertEqualsWithDelta( 6.0, (float) $taxed_later->get_total_tax(), 0.001, 'Taxed once the tax service answered.' );
		$this->assertSame( array( $still_zero->get_id() ), $this->noticed_order_ids(), 'Only the order with no tax is listed.' );
		$this->assertSame( 1, ( new UntaxedOrdersNotice() )->get_stored()['count'], 'The notice counts one order.' );
	}

	/**
	 * @testdox An order that gets tax with only a copied mark leaves the order the mark was copied from on the notice.
	 */
	public function test_order_with_a_copied_mark_taxed_later_leaves_the_notice_alone() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();
		$noted = $this->checkout_order();
		$copy  = $this->admin_order();
		$copy->update_meta_data( '_wc_connect_untaxed_order_noted', (string) $noted->get_id() );
		$copy->save();

		$this->start_new_request();
		$this->taxjar = 'answer';
		$reads        = 0;
		$count        = static function ( $pre ) use ( &$reads ) {
			++$reads;
			return $pre;
		};
		add_filter( 'pre_option_' . UntaxedOrdersNotice::OPTION_NAME, $count );
		$copy = $this->press_recalculate( $copy );
		remove_filter( 'pre_option_' . UntaxedOrdersNotice::OPTION_NAME, $count );

		$this->assertEqualsWithDelta( 6.0, (float) $copy->get_total_tax(), 0.001 );
		$this->assertSame( '', (string) $copy->get_meta( '_wc_connect_untaxed_order_noted' ), 'The copied mark is gone.' );
		$this->assertSame( 0, $reads, 'The notice\'s list is not even read for a copied mark.' );
		$this->assertSame( array( $noted->get_id() ), $this->noticed_order_ids(), 'The order it was copied from is still listed.' );
	}

	/**
	 * @testdox The mark copied to another order, as Subscriptions copies meta to a renewal, does not count as noted.
	 */
	public function test_a_copied_mark_does_not_count_as_noted() {
		$this->taxjar = 503;
		$this->fill_cart_at_checkout();
		$noted = $this->checkout_order();
		$copy  = $this->admin_order();
		foreach ( $noted->get_meta_data() as $meta ) {
			$copy->update_meta_data( $meta->key, $meta->value );
		}
		$copy->save();
		delete_option( UntaxedOrdersNotice::OPTION_NAME );

		$this->start_new_request();
		$this->assert_untaxed_and_told( $this->press_recalculate( $copy ) );
	}

	/**
	 * @testdox A subscription that gets tax later loses its mark and is taken off the notice, as an order is.
	 */
	public function test_subscription_taxed_later_is_taken_off_the_notice() {
		$this->taxjar = 503;
		$noted        = $this->press_recalculate( $this->admin_order() );
		$this->assertSame( array( $noted->get_id() ), $this->noticed_order_ids(), 'Listed.' );

		// WooCommerce Subscriptions' WC_Subscription is a WC_Order whose object type is
		// "subscription", so its saves fire woocommerce_before_subscription_object_save.
		$subscription = new class( $noted->get_id() ) extends WC_Order {
			/**
			 * As WC_Subscription.
			 *
			 * @var string
			 */
			protected $object_type = 'subscription';
		};
		$subscription->set_cart_tax( 6 );
		$subscription->save();

		$this->assertSame( '', (string) wc_get_order( $noted->get_id() )->get_meta( '_wc_connect_untaxed_order_noted' ), 'The mark is gone.' );
		$this->assertSame( array(), $this->noticed_order_ids(), 'Not listed any more.' );
	}

	// -------------------------------------------------------------------------
	// Phone and manual order plugins (admin AJAX carts)
	// -------------------------------------------------------------------------

	/**
	 * Serve the request as admin-ajax.php does for a phone order plugin that opted its cart in.
	 */
	private function as_opted_in_phone_order() {
		set_current_screen( 'dashboard' );
		add_filter( 'wp_doing_ajax', '__return_true' );
		do_action( 'woocommerce_cart_loaded_from_session', WC()->cart );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->integration->record_admin_ajax_user();
		add_filter( 'woocommerce_services_calculate_admin_ajax_cart_tax', '__return_true' );
	}

	/**
	 * @testdox A phone order cart with no answer fires the lookup-failed action, and the order made from it is noted.
	 */
	public function test_phone_order_without_an_answer_fires_the_action_and_notes_the_order() {
		$this->as_opted_in_phone_order();
		$this->taxjar = 503;

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->create_product()->get_id(), 1 );
		WC()->customer->set_shipping_location( 'US', 'CO', '80202', 'Denver' );
		$this->failed_carts = array();
		WC()->cart->calculate_totals();

		$this->assertNotEmpty( $this->failed_carts, 'The action fired.' );
		$this->assertSame( WC()->cart, $this->failed_carts[0], 'The action got the cart.' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_total_tax(), 0.001, 'The cart has no tax when the action fires.' );

		// Phone Orders for WooCommerce totals the cart, then calls WC_Checkout::create_order().
		$this->assert_untaxed_and_told( $this->checkout_order() );
	}

	/**
	 * @testdox The lookup-failed action does not fire for a cart that got an answer or a refused address.
	 */
	public function test_lookup_failed_action_fires_only_without_an_answer() {
		$this->fill_cart_at_checkout();
		$this->assertSame( array(), $this->failed_carts, 'An answer.' );

		$this->taxjar = 'zip_not_in_state';
		WC()->customer->set_shipping_location( 'US', 'CO', '80301', 'Boulder' );
		WC()->cart->calculate_totals();
		$this->assertSame( array(), $this->failed_carts, 'A refused address.' );

		$this->taxjar = 'timeout';
		WC()->customer->set_shipping_location( 'US', 'CO', '80203', 'Denver' );
		WC()->cart->calculate_totals();
		$this->assertCount( 1, $this->failed_carts, 'No answer.' );
	}

	/**
	 * @testdox The lookup-failed action fires for each way the tax service fails to answer.
	 *
	 * @dataProvider provide_no_answer
	 *
	 * @param string|int $outage How the tax service fails.
	 */
	public function test_lookup_failed_action_fires_for_each_outage( $outage ) {
		$this->taxjar = $outage;
		$this->fill_cart_at_checkout();

		$this->assertCount( 1, $this->failed_carts );
		$this->assertSame( WC()->cart, $this->failed_carts[0] );
	}

	/**
	 * @testdox The lookup-failed action does not fire for an address the tax service refused with a 400 or a 404.
	 *
	 * @dataProvider provide_refused_address
	 *
	 * @param string $refusal How the tax service refuses the address.
	 */
	public function test_lookup_failed_action_does_not_fire_for_a_refused_address( $refusal ) {
		$this->taxjar = $refusal;
		$this->fill_cart_at_checkout();

		$this->assertSame( array(), $this->failed_carts );
	}

	// -------------------------------------------------------------------------
	// Recalculate on the order screen
	// -------------------------------------------------------------------------

	/**
	 * @testdox Recalculate on an unpaid order with no answer and no saved rates: no tax, a note and a place in the notice.
	 *
	 * @dataProvider provide_no_answer
	 *
	 * @param string|int $outage How the tax service fails.
	 */
	public function test_recalculate_without_an_answer_or_saved_rates_is_untaxed_and_told( $outage ) {
		$order        = $this->admin_order();
		$this->taxjar = $outage;

		$this->assert_untaxed_and_told( $this->press_recalculate( $order ) );
	}

	/**
	 * @testdox Recalculate with no answer and a saved rate: the saved rate taxes it, as before, and nothing is said.
	 */
	public function test_recalculate_without_an_answer_uses_saved_rates_quietly() {
		$this->save_colorado_rate();
		$order        = $this->admin_order();
		$this->taxjar = 'timeout';

		$this->assert_taxed_and_quiet( $this->press_recalculate( $order ) );
	}

	/**
	 * @testdox Recalculate with an answer: taxed, nothing is said.
	 */
	public function test_recalculate_with_an_answer_is_quiet() {
		$order = $this->admin_order();

		$this->assert_taxed_and_quiet( $this->press_recalculate( $order ) );
	}

	/**
	 * @testdox Recalculate that drops an unpaid order's tax because of no answer says so.
	 */
	public function test_recalculate_that_drops_tax_without_an_answer_is_told() {
		$order = $this->press_recalculate( $this->admin_order() );
		$this->assertEqualsWithDelta( 6.0, (float) $order->get_total_tax(), 0.001, 'Taxed first.' );

		// The saved rows TaxJar's answer wrote are for this address; without them no rate
		// applies. The cached answer is dropped too, as it is after an hour.
		$this->reset_rate_table();
		$this->forget_cached_answers();
		$this->taxjar = 503;

		$this->assert_untaxed_and_told( $this->press_recalculate( $order ) );
	}

	/**
	 * @testdox Saving an order's items without Recalculate adds no note, even when the lookup gets no answer and code recalculates the order after.
	 */
	public function test_saving_order_items_without_recalculate_adds_no_note() {
		$order        = $this->admin_order();
		$this->taxjar = 503;
		$_POST        = array(
			'country'  => 'US',
			'state'    => 'CO',
			'postcode' => '80202',
			'city'     => 'Denver',
		);

		// The order was placed in an earlier request, so recalculating it from code asks the
		// tax service nothing: its items and address did not change.
		foreach ( array( 'orders_created_in_request', 'order_items_created_in_request' ) as $name ) {
			$property = new ReflectionProperty( $this->integration, $name );
			$property->setAccessible( true );
			$property->setValue(
				$this->integration,
				array(
					'objects' => array(),
					'ids'     => array(),
				)
			);
		}

		wc_save_order_items( $order->get_id(), array() );
		// Code that recalculates the order afterwards in the same request is not Recalculate.
		$order = wc_get_order( $order->get_id() );
		$order->calculate_totals( true );
		$order = wc_get_order( $order->get_id() );

		$this->assertGreaterThan( 0, $this->lookups, 'The lookup ran.' );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
	}

	/**
	 * @testdox Recalculate on a VAT exempt order with no answer adds no note: WooCommerce took its tax off, not a missing rate.
	 */
	public function test_recalculate_of_a_vat_exempt_order_adds_no_note() {
		$order = $this->admin_order();
		$order->update_meta_data( 'is_vat_exempt', 'yes' );
		$order->save();
		$this->taxjar = 503;

		$order = $this->press_recalculate( $order );

		$this->assertGreaterThan( 0, $this->lookups, 'The lookup ran.' );
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	/**
	 * @testdox A paid order that has no tax keeps it on Recalculate with no answer, and gets no note: its tax is locked.
	 */
	public function test_recalculate_of_a_paid_order_without_tax_adds_no_note() {
		$order = $this->admin_order( 'processing' );
		$order->set_date_paid( time() );
		$order->save();
		$this->taxjar = 503;

		// The order was paid in an earlier request. One created in this request, as an
		// import that creates an order already paid, is not locked yet.
		foreach ( array( 'orders_created_in_request', 'order_items_created_in_request' ) as $name ) {
			$property = new ReflectionProperty( $this->integration, $name );
			$property->setAccessible( true );
			$property->setValue(
				$this->integration,
				array(
					'objects' => array(),
					'ids'     => array(),
				)
			);
		}

		$order = $this->press_recalculate( $order );

		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001 );
		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
	}

	// -------------------------------------------------------------------------
	// REST API
	// -------------------------------------------------------------------------

	/**
	 * @testdox A new REST order with no answer and no saved rates: no tax, one note that says why, and a place in the notice.
	 *
	 * @dataProvider provide_no_answer
	 *
	 * @param string|int $outage How the tax service fails.
	 */
	public function test_rest_order_without_an_answer_or_saved_rates_is_untaxed_and_told( $outage ) {
		$this->taxjar = $outage;

		$order = $this->rest_create();

		$this->assert_untaxed_and_told( $order );
		$this->assertCount( 1, $this->notes( $order ), 'Only that note: ' . wp_json_encode( $this->notes( $order ) ) );
	}

	/**
	 * @testdox A new REST order with no answer and a saved rate keeps its note from before, and is not listed.
	 */
	public function test_rest_order_without_an_answer_with_saved_rates_keeps_the_old_note() {
		$this->save_colorado_rate();
		$this->taxjar = 503;

		$order = $this->rest_create();

		$this->assert_taxed_and_quiet( $order );
		$notes = $this->notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'did not answer or did not accept the order\'s address', $notes[0] );
	}

	/**
	 * @testdox A new REST order for an address the tax service refused keeps its note from before, and is not listed.
	 *
	 * @dataProvider provide_refused_address
	 *
	 * @param string $refusal How the tax service refuses the address.
	 */
	public function test_rest_order_for_a_refused_address_keeps_the_old_note( $refusal ) {
		$this->taxjar = $refusal;

		$order = $this->rest_create();

		$this->assertSame( array(), $this->untaxed_notes( $order ) );
		$this->assertSame( array(), $this->noticed_order_ids() );
		$notes = $this->notes( $order );
		$this->assertCount( 1, $notes );
		$this->assertStringContainsString( 'did not answer or did not accept the order\'s address', $notes[0] );
	}

	// -------------------------------------------------------------------------
	// The admin notice
	// -------------------------------------------------------------------------

	/**
	 * Render the notice on a screen.
	 *
	 * @param string $screen Screen id.
	 * @return string
	 */
	private function render_notice( $screen ) {
		set_current_screen( $screen );
		ob_start();
		( new UntaxedOrdersNotice() )->render();

		return (string) ob_get_clean();
	}

	/**
	 * @testdox The notice lists the latest orders with links, on WooCommerce screens and the Dashboard.
	 */
	public function test_notice_shows_on_woocommerce_screens_and_the_dashboard() {
		$notice = new UntaxedOrdersNotice();
		$orders = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$orders[] = wc_create_order();
			$notice->record( end( $orders ) );
		}

		$html = $this->render_notice( 'woocommerce_page_wc-settings' );

		$this->assertStringContainsString( '7 orders got no sales tax, because the sales tax service did not answer and the store has no saved tax rates for their addresses. Each order has a note about it. Check whether tax is due on them.', $html );
		$this->assertStringContainsString( 'notice-dismiss', $html );
		$this->assertStringContainsString( '<span class="screen-reader-text">Dismiss this notice.</span></a>', $html, 'The dismiss link has a name for screen readers.' );
		$this->assertStringContainsString( '#' . $orders[6]->get_order_number(), $html, 'Newest is linked.' );
		$this->assertStringNotContainsString( '#' . $orders[1]->get_order_number() . '<', $html, 'Only the 5 latest are linked.' );
		$this->assertNotSame( '', $this->render_notice( 'dashboard' ) );
		$this->assertSame( '', $this->render_notice( 'edit-post' ), 'Not on other screens.' );
	}

	/**
	 * @testdox The notice for one order says so in the singular.
	 */
	public function test_notice_for_one_order() {
		$order = wc_create_order();
		( new UntaxedOrdersNotice() )->record( $order );

		$html = $this->render_notice( 'dashboard' );

		$this->assertStringContainsString( '1 order got no sales tax, because the sales tax service did not answer and the store has no saved tax rates for its address. The order has a note about it. Check whether tax is due on it.', $html );
		$this->assertStringContainsString( 'Latest: <a href="', $html );
		$this->assertStringContainsString( '#' . $order->get_order_number() . '</a>', $html );
	}

	/**
	 * @testdox The notice is shown only to users who can manage WooCommerce, and only when an order was listed.
	 */
	public function test_notice_needs_manage_woocommerce_and_an_order() {
		$this->assertSame( '', $this->render_notice( 'dashboard' ), 'Nothing listed.' );

		( new UntaxedOrdersNotice() )->record( wc_create_order() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertSame( '', $this->render_notice( 'dashboard' ) );
	}

	/**
	 * @testdox An order is listed once, the list keeps the latest 20, and the count goes on.
	 */
	public function test_notice_record_keeps_the_latest_orders_once() {
		$notice = new UntaxedOrdersNotice();
		$first  = wc_create_order();
		$notice->record( $first );
		$notice->record( wc_get_order( $first->get_id() ) );
		$this->assertSame( 1, $notice->get_stored()['count'] );

		for ( $i = 0; $i < 21; $i++ ) {
			$notice->record( wc_create_order() );
		}

		$stored = $notice->get_stored();
		$this->assertSame( 22, $stored['count'] );
		$this->assertCount( 20, $stored['order_ids'] );
		$this->assertNotContains( $first->get_id(), $stored['order_ids'] );
	}

	/**
	 * @testdox Forgetting a listed order takes it off and lowers the count; the last one removes the option.
	 */
	public function test_notice_forget_takes_a_listed_order_off() {
		$notice = new UntaxedOrdersNotice();
		$orders = array( wc_create_order(), wc_create_order(), wc_create_order() );
		foreach ( $orders as $order ) {
			$notice->record( $order );
		}

		$notice->forget( $orders[1]->get_id() );

		$this->assertSame(
			array(
				'count'     => 2,
				'order_ids' => array( $orders[0]->get_id(), $orders[2]->get_id() ),
			),
			$notice->get_stored()
		);

		$notice->forget( $orders[0]->get_id() );
		$notice->forget( $orders[2]->get_id() );

		$this->assertFalse( get_option( UntaxedOrdersNotice::OPTION_NAME ), 'Nothing left to show.' );
	}

	/**
	 * @testdox Forgetting an order that is not listed writes nothing; one past the kept list leaves the count.
	 */
	public function test_notice_forget_of_an_unlisted_order_writes_nothing() {
		global $wpdb;

		$notice = new UntaxedOrdersNotice();
		$first  = wc_create_order();
		$notice->record( $first );
		for ( $i = 0; $i < 21; $i++ ) {
			$notice->record( wc_create_order() );
		}
		$before = $notice->get_stored();
		$this->assertNotContains( $first->get_id(), $before['order_ids'], 'The first order is past the kept list.' );

		$writes = 0;
		$count  = static function ( $query ) use ( &$writes, $wpdb ) {
			if ( preg_match( '/^\s*(UPDATE|INSERT|DELETE)\b.*' . preg_quote( $wpdb->options, '/' ) . '/is', $query ) ) {
				++$writes;
			}
			return $query;
		};
		add_filter( 'query', $count );
		$notice->forget( $first->get_id() );
		$notice->forget( 999999 );
		remove_filter( 'query', $count );

		$this->assertSame( 0, $writes, 'No option write.' );
		$this->assertSame( $before, $notice->get_stored() );

		$notice->forget( $before['order_ids'][0] );
		$this->assertSame( 21, $notice->get_stored()['count'] );
		$this->assertCount( 19, $notice->get_stored()['order_ids'] );
		$this->assertSame( 'off', $wpdb->get_var( $wpdb->prepare( "SELECT autoload FROM {$wpdb->options} WHERE option_name = %s", UntaxedOrdersNotice::OPTION_NAME ) ), 'Still not autoloaded.' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * @testdox Dismissing clears the notice until another order is listed; a bad nonce or a user who can't manage WooCommerce does nothing.
	 */
	public function test_dismiss_clears_the_notice() {
		$notice = new UntaxedOrdersNotice();
		$notice->record( wc_create_order() );

		$dismiss = function () use ( $notice ) {
			add_filter(
				'wp_redirect',
				static function () {
					throw new Exception( 'redirected' );
				}
			);
			try {
				$notice->handle_dismiss();
			} catch ( Exception $e ) {
				$this->assertSame( 'redirected', $e->getMessage() );
			}
			remove_all_filters( 'wp_redirect' );
		};

		$_GET['_wpnonce'] = 'bad';
		$dismiss();
		$this->assertSame( 1, $notice->get_stored()['count'], 'A bad nonce does nothing.' );

		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$_GET['_wpnonce'] = wp_create_nonce( UntaxedOrdersNotice::DISMISS_ACTION );
		$dismiss();
		$this->assertSame( 1, $notice->get_stored()['count'], 'An editor cannot dismiss it.' );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$_GET['_wpnonce'] = wp_create_nonce( UntaxedOrdersNotice::DISMISS_ACTION );
		$dismiss();
		unset( $_GET['_wpnonce'] );
		$this->assertSame( 0, $notice->get_stored()['count'], 'Dismissed.' );

		$notice->record( wc_create_order() );
		$this->assertSame( 1, $notice->get_stored()['count'], 'A new order shows it again.' );
	}

	/**
	 * @testdox A stored value of the wrong shape reads as an empty list.
	 */
	public function test_notice_reads_a_broken_option_as_empty() {
		update_option( UntaxedOrdersNotice::OPTION_NAME, 'broken' );

		$this->assertSame(
			array(
				'count'     => 0,
				'order_ids' => array(),
			),
			( new UntaxedOrdersNotice() )->get_stored()
		);
	}
}
