<?php
/**
 * Tests for a cart totalled more than once in one request.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Same_Request_Totals
 *
 * The Store API batch route runs several cart changes in one request, and each one
 * totals the cart. The integration keeps the last answer TaxJar gave, so a total
 * that gets no answer must not reuse the answer an earlier total in the same request
 * got for another address. A separate request starts with none.
 */
class WP_Test_WC_Connect_TaxJar_Same_Request_Totals extends WC_Unit_Test_Case {

	/**
	 * Whether TaxJar answers. Read by the mock on every lookup.
	 *
	 * @var bool
	 */
	private $taxjar_answers = true;

	/**
	 * Number of lookups sent to TaxJar.
	 *
	 * @var int
	 */
	private $lookups = 0;

	/**
	 * Destination ZIP code of the last lookup sent to TaxJar.
	 *
	 * @var string|null
	 */
	private $last_to_zip = null;

	/**
	 * The integration under test, hooked as init() hooks it.
	 *
	 * @var WC_Connect_TaxJar_Integration|null
	 */
	private $integration;

	/**
	 * The integration's woocommerce_before_calculate_totals callback, hooked as init() hooks it.
	 *
	 * @var Closure|null
	 */
	private $forget_answers_before_main_cart_total;

	/**
	 * $100 product in the cart.
	 *
	 * @var WC_Product|null
	 */
	private $product;

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
	}

	/**
	 * A store in Austin, TX, with flat rate shipping, local pickup, taxes on and an empty
	 * rate table.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		WC_Helper_Shipping::create_simple_flat_rate( 10 );
		add_filter( 'woocommerce_package_rates', array( $this, 'add_local_pickup' ) );
		WC()->customer->set_is_vat_exempt( false );

		// Look like a Store API request, so calculate_totals() runs.
		$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/v1/batch';

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( 100 );
		$this->product->save();

		$this->integration                           = $this->new_integration();
		$this->forget_answers_before_main_cart_total = ( new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'forget_answers_before_main_cart_total' ) )->getClosure( $this->integration );
		add_action( 'woocommerce_before_calculate_totals', $this->forget_answers_before_main_cart_total, PHP_INT_MIN );
		add_action( 'woocommerce_after_calculate_totals', array( $this->integration, 'maybe_calculate_totals' ), 20 );
		add_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $this->integration, 'override_cart_item_tax_rates' ), 10, 3 );
		add_filter( 'woocommerce_customer_taxable_address', array( $this->integration, 'append_base_address_to_customer_taxable_address' ), 10, 1 );
		add_filter( 'woocommerce_matched_rates', array( $this->integration, 'allow_street_address_for_matched_rates' ), 10, 2 );
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this->integration, 'override_order_item_taxes' ), 10, 2 );
	}

	/**
	 * Undo set_up().
	 */
	public function tear_down() {
		remove_action( 'woocommerce_before_calculate_totals', $this->forget_answers_before_main_cart_total, PHP_INT_MIN );
		remove_action( 'woocommerce_after_calculate_totals', array( $this->integration, 'maybe_calculate_totals' ), 20 );
		remove_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $this->integration, 'override_cart_item_tax_rates' ), 10 );
		remove_filter( 'woocommerce_customer_taxable_address', array( $this->integration, 'append_base_address_to_customer_taxable_address' ), 10 );
		remove_filter( 'woocommerce_matched_rates', array( $this->integration, 'allow_street_address_for_matched_rates' ), 10 );
		remove_action( 'woocommerce_order_item_after_calculate_taxes', array( $this->integration, 'override_order_item_taxes' ), 10 );
		remove_filter( 'woocommerce_package_rates', array( $this, 'add_local_pickup' ) );
		remove_all_actions( 'woocommerce_order_after_calculate_totals' );
		unset( $GLOBALS['wp']->query_vars['rest_route'] );

		WC()->cart->empty_cart();
		WC()->session->set( 'chosen_shipping_methods', array() );
		WC_Helper_Product::delete_product( $this->product->get_id() );
		delete_option( 'woocommerce_calc_taxes' );
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		parent::tear_down();
	}

	/**
	 * Offer local pickup next to flat rate.
	 *
	 * @param WC_Shipping_Rate[] $rates Rates for the package.
	 * @return WC_Shipping_Rate[]
	 */
	public function add_local_pickup( $rates ) {
		$rates['local_pickup:1'] = new WC_Shipping_Rate( 'local_pickup:1', 'Local pickup', 0, array(), 'local_pickup', 1 );

		return $rates;
	}

	/**
	 * An integration for the Austin store. TaxJar answers 8.25% (state 6.25%, city 1%,
	 * special 1%) with shipping taxable, wherever in Texas it is asked about, or fails
	 * while $taxjar_answers is false.
	 *
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function new_integration() {
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'smartcalcs_cache_request', 'get_store_settings', '_log' ) )
			->getMock();

		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'TX',
				'postcode' => '78701',
				'city'     => 'Austin',
				'street'   => '100 Congress Ave',
			)
		);

		$integration->method( 'smartcalcs_cache_request' )->willReturnCallback(
			function ( $json ) {
				++$this->lookups;
				$this->last_to_zip = (string) ( json_decode( $json, true )['to_zip'] ?? '' );
				if ( ! $this->taxjar_answers ) {
					return false;
				}

				$components = array(
					'city_tax_rate'        => 0.01,
					'county_tax_rate'      => 0.0,
					'special_tax_rate'     => 0.01,
					'state_sales_tax_rate' => 0.0625,
				);
				$body       = json_decode( $json, true );
				$lines      = array();
				foreach ( $body['line_items'] ?? array() as $line_item ) {
					$amount  = $line_item['unit_price'] * $line_item['quantity'] - $line_item['discount'];
					$lines[] = array_merge(
						array(
							'id'                => $line_item['id'],
							'tax_collectable'   => round( $amount * array_sum( $components ), 2 ),
							'combined_tax_rate' => array_sum( $components ),
						),
						$components
					);
				}

				return array(
					'body' => wp_json_encode(
						array(
							'tax' => array(
								'rate'            => array_sum( $components ),
								'freight_taxable' => true,
								'has_nexus'       => true,
								'jurisdictions'   => array(
									'county' => 'TRAVIS',
									'city'   => strtoupper( (string) ( $body['to_city'] ?? '' ) ),
								),
								'breakdown'       => array(
									'line_items' => $lines,
									'shipping'   => array_merge( array( 'combined_tax_rate' => array_sum( $components ) ), $components ),
								),
							),
						)
					),
				);
			}
		);

		return $integration;
	}

	/**
	 * Point the customer's billing and shipping address at a place.
	 *
	 * @param string $state    State.
	 * @param string $postcode Postcode.
	 * @param string $city     City.
	 */
	private function ship_to( $state, $postcode, $city ) {
		WC()->customer->set_billing_location( 'US', $state, $postcode, $city );
		WC()->customer->set_shipping_location( 'US', $state, $postcode, $city );
		WC()->customer->set_billing_address_1( '1 Test St' );
		WC()->customer->set_shipping_address_1( '1 Test St' );
	}

	/**
	 * Choose a shipping method and total the cart, as a select-shipping-rate request does.
	 *
	 * @param string $rate_id Rate id.
	 */
	private function choose_shipping( $rate_id ) {
		WC()->session->set( 'chosen_shipping_methods', array( $rate_id ) );
		WC()->cart->calculate_totals();
	}

	/**
	 * Create the order from the cart, as classic checkout does.
	 *
	 * @return WC_Order
	 */
	private function checkout_order() {
		$customer = WC()->customer;
		$order_id = WC()->checkout()->create_order(
			array(
				'billing_email'     => 'a@b.com',
				'payment_method'    => 'bacs',
				'billing_country'   => $customer->get_billing_country(),
				'billing_state'     => $customer->get_billing_state(),
				'billing_postcode'  => $customer->get_billing_postcode(),
				'billing_city'      => $customer->get_billing_city(),
				'shipping_country'  => $customer->get_shipping_country(),
				'shipping_state'    => $customer->get_shipping_state(),
				'shipping_postcode' => $customer->get_shipping_postcode(),
				'shipping_city'     => $customer->get_shipping_city(),
			)
		);
		$this->assertIsInt( $order_id, 'The order was not created.' );

		return wc_get_order( $order_id );
	}

	/**
	 * The steps on WOOTAX-429, in one request: a New York customer picks local pickup
	 * (taxed at the Austin store, 8.25% on $100), then flat rate. Out of state, no tax
	 * applies, so the cart is $100 and $10 shipping with no tax, as in a separate request.
	 * The order made from it has no tax either.
	 */
	public function test_out_of_state_shipping_after_local_pickup_in_the_same_request_is_not_taxed() {
		$this->ship_to( 'NY', '10118', 'New York' );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		$this->choose_shipping( 'local_pickup:1' );
		$this->assertEqualsWithDelta( 8.25, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Local pickup is taxed at the store.' );

		$this->choose_shipping( 'flat_rate' );
		$this->assertEqualsWithDelta( 10.0, (float) WC()->cart->get_shipping_total(), 0.001, 'Flat rate is chosen.' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
		$this->assertEqualsWithDelta( 110.0, (float) WC()->cart->get_total( 'edit' ), 0.001, 'Cart total' );

		$order = $this->checkout_order();
		$this->assertEqualsWithDelta( 0.0, (float) $order->get_total_tax(), 0.001, 'Order tax' );
		$this->assertSame( array(), $order->get_taxes(), 'Order tax lines' );
	}

	/**
	 * A lookup that fails after one that was answered, in one request: the customer moves
	 * from Austin to Round Rock and TaxJar is down. The cart is taxed from the saved
	 * rates, as in a separate request, and the table has none for Round Rock. It does not
	 * keep Austin's answer.
	 */
	public function test_failed_lookup_after_an_answered_one_in_the_same_request_uses_the_saved_rates() {
		$this->ship_to( 'TX', '78701', 'Austin' );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$this->choose_shipping( 'flat_rate' );
		$this->assertEqualsWithDelta( 8.25, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Austin is answered.' );

		$this->taxjar_answers = false;
		$this->ship_to( 'TX', '78664', 'Round Rock' );
		WC()->cart->calculate_totals();

		$this->assertSame( '78664', $this->last_to_zip, 'TaxJar was asked about Round Rock.' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Cart shipping tax' );
	}

	/**
	 * A cart Subscriptions totals inside the main cart's total, whose own lookup fails,
	 * leaves the main cart's answer for the checkout order, and costs no other lookup.
	 */
	public function test_failed_lookup_for_a_nested_cart_keeps_the_main_cart_answer() {
		$this->ship_to( 'TX', '78701', 'Austin' );
		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		// Subscriptions totals its recurring carts from this hook, at priority 1, also
		// when the integration totals the main cart again with the answer applied.
		$main_totals = 0;
		$nested      = function ( $cart ) use ( &$main_totals ) {
			if ( WC()->cart !== $cart || 2 !== ++$main_totals ) {
				return;
			}
			$recurring_cart                     = clone $cart;
			$recurring_cart->recurring_cart_key = '2026_11_18_monthly';
			$this->taxjar_answers               = false;
			$this->integration->calculate_totals( $recurring_cart );
			$this->taxjar_answers = true;
		};
		add_action( 'woocommerce_after_calculate_totals', $nested, 1 );
		$lookups_before = $this->lookups;
		try {
			$this->choose_shipping( 'flat_rate' );
		} finally {
			remove_action( 'woocommerce_after_calculate_totals', $nested, 1 );
		}
		$this->assertSame( 2, $this->lookups - $lookups_before, 'One lookup for the main cart and one for the recurring cart, and no other.' );

		$item          = new stdClass();
		$item->product = $this->product;
		$item->key     = $cart_item_key;
		$rates         = $this->integration->override_cart_item_tax_rates( array( 'table' => array() ), $item, WC()->cart );

		$this->assertArrayNotHasKey( 'table', $rates, 'The main cart lost its answer.' );
		$this->assertEqualsWithDelta( 8.25, array_sum( array_column( $rates, 'rate' ) ), 0.001, 'Main cart rates' );
	}

	/**
	 * A lookup with no answer, and no answer from earlier in the request, leaves
	 * WooCommerce's total as it is: the cart isn't totalled a second time.
	 */
	public function test_lookup_with_no_answer_and_none_before_does_not_total_the_cart_again() {
		$this->ship_to( 'NY', '10118', 'New York' );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->session->set( 'chosen_shipping_methods', array( 'flat_rate' ) );

		$totals_before = did_action( 'woocommerce_before_calculate_totals' );
		WC()->cart->calculate_totals();

		$this->assertSame( 1, did_action( 'woocommerce_before_calculate_totals' ) - $totals_before, 'Cart totals' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
	}

	/**
	 * When a failed lookup drops an earlier answer and the cart is totalled again,
	 * Subscriptions totals its recurring carts again. A recurring cart's answer is its
	 * own: the main cart is left with no answer, and is taxed from the rate table.
	 */
	public function test_recurring_cart_totalled_after_a_dropped_answer_does_not_give_the_main_cart_its_answer() {
		$this->ship_to( 'TX', '78701', 'Austin' );
		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$this->choose_shipping( 'flat_rate' );

		$this->taxjar_answers = false;
		$this->ship_to( 'TX', '78664', 'Round Rock' );
		$nested = function ( $cart ) {
			if ( WC()->cart !== $cart || $this->taxjar_answers ) {
				return;
			}
			$recurring_cart                     = clone $cart;
			$recurring_cart->recurring_cart_key = '2026_11_18_monthly';
			$this->taxjar_answers               = true;
			try {
				$this->integration->calculate_totals( $recurring_cart );
			} finally {
				$this->taxjar_answers = false;
			}
		};
		add_action( 'woocommerce_after_calculate_totals', $nested, 1 );
		try {
			WC()->cart->calculate_totals();
		} finally {
			remove_action( 'woocommerce_after_calculate_totals', $nested, 1 );
		}

		$item          = new stdClass();
		$item->product = $this->product;
		$item->key     = $cart_item_key;
		$rates         = $this->integration->override_cart_item_tax_rates( array( 'table' => array() ), $item, WC()->cart );

		$this->assertSame( array( 'table' => array() ), $rates, 'The main cart got the recurring cart\'s answer.' );
	}

	/**
	 * Another cart totalled on its own, like a copy of the cart a plugin totals, whose
	 * lookup fails, leaves the main cart's answer for the checkout order.
	 */
	public function test_failed_lookup_for_a_cart_totalled_on_its_own_keeps_the_main_cart_answer() {
		$this->ship_to( 'TX', '78701', 'Austin' );
		$cart_item_key = WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$this->choose_shipping( 'flat_rate' );
		$this->assertEqualsWithDelta( 8.25, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Austin is answered.' );

		$other_cart           = clone WC()->cart;
		$this->taxjar_answers = false;
		$other_cart->calculate_totals();

		$item          = new stdClass();
		$item->product = $this->product;
		$item->key     = $cart_item_key;
		$rates         = $this->integration->override_cart_item_tax_rates( array( 'table' => array() ), $item, WC()->cart );

		$this->assertArrayNotHasKey( 'table', $rates, 'The main cart lost its answer.' );
		$this->assertEqualsWithDelta( 8.25, array_sum( array_column( $rates, 'rate' ) ), 0.001, 'Main cart rates' );
	}

	/**
	 * Subscriptions totals its recurring carts from the woocommerce_calculated_total filter,
	 * at priority 1000. That runs inside WooCommerce's own total of the main cart, before
	 * the integration's hook. A customer answered in Austin moves to New York in the same
	 * request. Out of state, no tax applies: the main cart is $100 and $10 shipping with no
	 * tax, and so is the recurring cart.
	 */
	public function test_out_of_state_address_after_an_answered_one_with_a_recurring_cart_totalled_inside_the_main_total() {
		$recurring_cart = null;
		$in_recurring   = false;
		// Like WC_Subscriptions_Cart::calculate_subscription_totals(), which skips the recurring
		// cart's own total.
		$subscriptions = function ( $total ) use ( &$recurring_cart, &$in_recurring ) {
			if ( $in_recurring ) {
				return $total;
			}
			$in_recurring = true;
			try {
				$recurring_cart                     = clone WC()->cart;
				$recurring_cart->recurring_cart_key = '2026_11_19_monthly';
				$recurring_cart->calculate_totals();
			} finally {
				$in_recurring = false;
			}

			return $total;
		};
		add_filter( 'woocommerce_calculated_total', $subscriptions, 1000 );
		try {
			$this->ship_to( 'TX', '78701', 'Austin' );
			WC()->cart->add_to_cart( $this->product->get_id(), 1 );
			$this->choose_shipping( 'flat_rate' );
			$this->assertEqualsWithDelta( 8.25, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Austin is answered.' );

			$this->ship_to( 'NY', '10118', 'New York' );
			WC()->cart->calculate_totals();
		} finally {
			remove_filter( 'woocommerce_calculated_total', $subscriptions, 1000 );
		}

		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Main cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_shipping_tax(), 0.001, 'Main cart shipping tax' );
		$this->assertEqualsWithDelta( 110.0, (float) WC()->cart->get_total( 'edit' ), 0.001, 'Main cart total' );
		$this->assertInstanceOf( WC_Cart::class, $recurring_cart, 'The recurring cart was totalled.' );
		$this->assertEqualsWithDelta( 0.0, (float) $recurring_cart->get_cart_contents_tax(), 0.001, 'Recurring cart item tax' );
		$this->assertEqualsWithDelta( 0.0, (float) $recurring_cart->get_shipping_tax(), 0.001, 'Recurring cart shipping tax' );
	}

	/**
	 * init() hooks the drop before WooCommerce totals the main cart.
	 *
	 * A failed lookup prices the cart again when a shipping answer is held (see
	 * forget_shipping_answer()), which also takes the earlier answer off the lines. So
	 * the drop shows in the number of totals: with nothing held, there is no second one.
	 */
	public function test_init_hooks_the_drop_before_the_main_cart_total() {
		remove_action( 'woocommerce_before_calculate_totals', $this->forget_answers_before_main_cart_total, PHP_INT_MIN );
		require_once __DIR__ . '/../../classes/class-wc-connect-custom-surcharge.php';
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'yes' );
		$this->integration->api_client = $this->createMock( 'WC_Connect_API_Client' );
		$this->integration->init();

		$this->ship_to( 'NY', '10118', 'New York' );
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		$this->choose_shipping( 'local_pickup:1' );
		$totals_before = did_action( 'woocommerce_before_calculate_totals' );
		$this->choose_shipping( 'flat_rate' );

		$this->assertEqualsWithDelta( 0.0, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Cart item tax' );
		$this->assertSame( 1, did_action( 'woocommerce_before_calculate_totals' ) - $totals_before, 'The cart was totalled once.' );
	}
}
