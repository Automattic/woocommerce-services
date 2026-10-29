<?php
/**
 * Tests for carts totalled in an admin AJAX request, as phone and manual order plugins do.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Admin_Ajax_Cart
 *
 * WordPress reports is_admin() for admin-ajax.php, so a cart these plugins build there
 * was never sent to TaxJar, and an address with no saved rate rows got $0 tax. A plugin
 * opts its cart in with the woocommerce_services_calculate_admin_ajax_cart_tax filter.
 */
class WP_Test_WC_Connect_TaxJar_Admin_Ajax_Cart extends WC_Unit_Test_Case {

	const OPT_IN_FILTER = 'woocommerce_services_calculate_admin_ajax_cart_tax';

	/**
	 * Integration under test.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $integration;

	/**
	 * Number of TaxJar requests the integration made.
	 *
	 * @var int
	 */
	private $taxjar_requests = 0;

	/**
	 * $100 product in the cart.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * Product TaxJar answers as exempt, or 0 for none.
	 *
	 * @var int
	 */
	private $exempt_product_id = 0;

	/**
	 * REST route the request had before the test, restored after it.
	 *
	 * @var string|null
	 */
	private $rest_route;

	/**
	 * Load required classes before running tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-functions.php';
	}

	/**
	 * A $100 product in the cart, shipped to Gwinn, MI, from a Michigan store whose
	 * TaxJar answer is 6%. No rate rows exist, as for an address never checked out.
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		$this->reset_tax_rate_tables();

		$this->rest_route  = $GLOBALS['wp']->query_vars['rest_route'] ?? null;
		$this->integration = $this->counting_integration();
		add_action( 'woocommerce_after_calculate_totals', array( $this->integration, 'maybe_calculate_totals' ), 20 );
		add_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $this->integration, 'override_cart_item_tax_rates' ), 10, 3 );

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( 100 );
		$this->product->save();

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->customer->set_billing_location( 'US', 'MI', '49841', 'Gwinn' );
		WC()->customer->set_shipping_location( 'US', 'MI', '49841', 'Gwinn' );
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		remove_action( 'woocommerce_after_calculate_totals', array( $this->integration, 'maybe_calculate_totals' ), 20 );
		remove_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $this->integration, 'override_cart_item_tax_rates' ), 10 );
		remove_all_filters( self::OPT_IN_FILTER );
		remove_all_filters( 'wp_doing_ajax' );
		remove_all_filters( 'woocommerce_is_cart' );
		remove_all_filters( 'woocommerce_is_checkout' );
		if ( null === $this->rest_route ) {
			unset( $GLOBALS['wp']->query_vars['rest_route'] );
		} else {
			$GLOBALS['wp']->query_vars['rest_route'] = $this->rest_route;
		}

		WC()->cart->empty_cart();
		WC_Helper_Product::delete_product( $this->product->get_id() );
		delete_option( 'woocommerce_calc_taxes' );

		parent::tear_down();
	}

	/**
	 * An integration whose TaxJar request is stubbed and counted. It answers 6% for
	 * every line but the exempt product's, from a store in Marquette, MI.
	 *
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function counting_integration() {
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'smartcalcs_cache_request', 'get_store_settings', '_log' ) )
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

		$integration->method( 'smartcalcs_cache_request' )->willReturnCallback(
			function ( $json ) {
				++$this->taxjar_requests;

				$components = array(
					'combined_tax_rate'    => 0.06,
					'city_tax_rate'        => 0.0,
					'county_tax_rate'      => 0.0,
					'special_tax_rate'     => 0.0,
					'state_sales_tax_rate' => 0.06,
				);

				$body  = json_decode( $json, true );
				$lines = array();
				foreach ( $body['line_items'] ?? array() as $line_item ) {
					$exempt  = 0 !== $this->exempt_product_id && 0 === strpos( (string) $line_item['id'], $this->exempt_product_id . '-' );
					$lines[] = array_merge(
						array( 'id' => $line_item['id'] ),
						$exempt ? array_fill_keys( array_keys( $components ), 0.0 ) : $components
					);
				}

				return array(
					'body' => wp_json_encode(
						array(
							'tax' => array(
								'rate'            => 0.06,
								'freight_taxable' => false,
								'has_nexus'       => true,
								'jurisdictions'   => array(
									'county' => 'MARQUETTE',
									'city'   => strtoupper( (string) ( $body['to_city'] ?? '' ) ),
								),
								'breakdown'       => array( 'line_items' => $lines ),
							),
						)
					),
				);
			}
		);

		return $integration;
	}

	/**
	 * Delete every tax rate row, so no saved row can tax the address.
	 *
	 * `DELETE` rather than `TRUNCATE`: `TRUNCATE` commits the test's transaction.
	 */
	private function reset_tax_rate_tables() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery

		WC_Cache_Helper::invalidate_cache_group( 'taxes' );
		if ( class_exists( Automattic\WooCommerce\Internal\Tax\TaxRateDataStore::class ) ) {
			wc_get_container()->replace( Automattic\WooCommerce\Internal\Tax\TaxRateDataStore::class, new Automattic\WooCommerce\Internal\Tax\TaxRateDataStore() );
		}
	}

	/**
	 * Serve the request as admin-ajax.php does for a phone order plugin: an admin
	 * screen, an AJAX request, a cart loaded from the session, and the given user,
	 * recorded as admin_init records them before any wp_ajax_ handler runs.
	 *
	 * @param string|null $role Role of the logged-in user, or null for a logged-out visitor.
	 */
	private function as_admin_ajax( $role = 'shop_manager' ) {
		set_current_screen( 'dashboard' );
		add_filter( 'wp_doing_ajax', '__return_true' );
		do_action( 'woocommerce_cart_loaded_from_session', WC()->cart );

		wp_set_current_user( $this->user_id( $role ) );
		$this->integration->record_admin_ajax_user();
	}

	/**
	 * A new user with the given role.
	 *
	 * @param string|null $role Role of the user, or null for a logged-out visitor.
	 * @return int
	 */
	private function user_id( $role ) {
		return null === $role ? 0 : self::factory()->user->create( array( 'role' => $role ) );
	}

	/**
	 * Opt the cart in with the given filter value.
	 *
	 * @param mixed $value Value the filter returns.
	 */
	private function opt_in( $value = true ) {
		add_filter(
			self::OPT_IN_FILTER,
			function () use ( $value ) {
				return $value;
			}
		);
	}

	/**
	 * Tax on the cart.
	 *
	 * @return float
	 */
	private function cart_tax() {
		return (float) WC()->cart->get_total_tax();
	}

	/**
	 * Without the opt-in, an admin AJAX cart is not sent to TaxJar, as before.
	 */
	public function test_admin_ajax_cart_is_not_sent_to_taxjar_by_default() {
		$this->as_admin_ajax();

		WC()->cart->calculate_totals();

		$this->assertSame( 0, $this->taxjar_requests );
		$this->assertEqualsWithDelta( 0.0, $this->cart_tax(), 0.001 );
	}

	/**
	 * An opted-in admin AJAX cart is sent to TaxJar once per total and taxed from its answer.
	 */
	public function test_opted_in_admin_ajax_cart_is_sent_to_taxjar_once() {
		$this->as_admin_ajax();
		$this->opt_in();

		WC()->cart->calculate_totals();

		$this->assertSame( 1, $this->taxjar_requests );
		// $100 at 6%.
		$this->assertEqualsWithDelta( 6.0, $this->cart_tax(), 0.001 );
	}

	/**
	 * The filter receives the cart being totalled, which need not be WC()->cart.
	 */
	public function test_opt_in_filter_receives_the_cart() {
		$this->as_admin_ajax();
		$cart = new WC_Cart();
		$cart->add_to_cart( $this->product->get_id(), 1 );
		$received = null;
		add_filter(
			self::OPT_IN_FILTER,
			function ( $calculate, $cart ) use ( &$received ) {
				$received = $cart;

				return $calculate;
			},
			10,
			2
		);

		$cart->calculate_totals();

		$this->assertNotSame( WC()->cart, $cart );
		$this->assertSame( $cart, $received );
	}

	/**
	 * An opted-in cart that is not WC()->cart keeps its own answer for the order built
	 * from it, though WC()->cart was answered in the same request.
	 */
	public function test_order_from_an_opted_in_cart_that_is_not_the_main_cart_is_taxed_by_its_answer() {
		$this->as_admin_ajax();
		$this->opt_in();
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $this->integration, 'override_order_item_taxes' ), 10, 2 );

		$exempt = WC_Helper_Product::create_simple_product();
		$exempt->set_regular_price( 100 );
		$exempt->save();
		$this->exempt_product_id = $exempt->get_id();

		try {
			// Adding to any cart also totals WC()->cart, which TaxJar answers at 6%. The
			// taxed line comes last, so the shared rate rows hold its 6%.
			$cart = new WC_Cart();
			$cart->add_to_cart( $exempt->get_id(), 1 );
			$cart->add_to_cart( $this->product->get_id(), 1 );
			$cart->calculate_totals();

			$order = wc_create_order();
			$order->set_billing_address(
				array(
					'country'  => 'US',
					'state'    => 'MI',
					'postcode' => '49841',
					'city'     => 'Gwinn',
				)
			);
			$order->set_shipping_address(
				array(
					'country'  => 'US',
					'state'    => 'MI',
					'postcode' => '49841',
					'city'     => 'Gwinn',
				)
			);
			$order->add_product( $exempt, 1 );
			$order->add_product( $this->product, 1 );
			$order->calculate_totals();

			$taxed = array();
			foreach ( $order->get_items() as $item ) {
				$taxed[ $item->get_product_id() ] = (float) $item->get_total_tax();
			}

			// $100 at 6%.
			$this->assertEqualsWithDelta( 6.0, (float) $cart->get_cart_contents_tax(), 0.001, 'Cart tax' );
			$this->assertEqualsWithDelta( 0.0, $taxed[ $exempt->get_id() ], 0.001, 'TaxJar answered this line as exempt' );
			$this->assertEqualsWithDelta( 6.0, $taxed[ $this->product->get_id() ], 0.001 );
		} finally {
			remove_action( 'woocommerce_order_item_after_calculate_taxes', array( $this->integration, 'override_order_item_taxes' ), 10 );
			WC_Helper_Product::delete_product( $exempt->get_id() );
		}
	}

	/**
	 * A plugin that defines WOOCOMMERCE_CART makes is_cart() true. The cart is still taxed.
	 */
	public function test_opted_in_admin_ajax_cart_is_taxed_when_is_cart_is_true() {
		$this->as_admin_ajax();
		$this->opt_in();
		add_filter( 'woocommerce_is_cart', '__return_true' );

		WC()->cart->calculate_totals();

		$this->assertSame( 1, $this->taxjar_requests );
		$this->assertEqualsWithDelta( 6.0, $this->cart_tax(), 0.001 );
	}

	/**
	 * An address with no saved rate rows gets TaxJar's tax, not $0.
	 */
	public function test_opted_in_admin_ajax_cart_taxes_an_address_with_no_rate_rows() {
		$gwinn = array(
			'country'  => 'US',
			'state'    => 'MI',
			'postcode' => '49841',
			'city'     => 'Gwinn',
		);
		$this->assertSame( array(), WC_Tax::find_rates( $gwinn ), 'No rate row may tax the address before the lookup.' );
		$this->as_admin_ajax();
		$this->opt_in();

		WC()->cart->calculate_totals();

		$this->assertEqualsWithDelta( 6.0, $this->cart_tax(), 0.001 );
	}

	/**
	 * Values that are not exactly true do not opt in.
	 *
	 * @return array
	 */
	public function provide_values_that_do_not_opt_in() {
		return array(
			'string "1"'   => array( '1' ),
			'integer 1'    => array( 1 ),
			'string "yes"' => array( 'yes' ),
			'array'        => array( array( true ) ),
			'null'         => array( null ),
		);
	}

	/**
	 * @dataProvider provide_values_that_do_not_opt_in
	 *
	 * @param mixed $value Value the filter returns.
	 */
	public function test_filter_must_return_true_to_opt_in( $value ) {
		$this->as_admin_ajax();
		$this->opt_in( $value );

		WC()->cart->calculate_totals();

		$this->assertSame( 0, $this->taxjar_requests );
	}

	/**
	 * Roles that cannot edit orders, and logged-out visitors on wp_ajax_nopriv_ actions,
	 * cannot reach TaxJar through the opt-in.
	 *
	 * @return array
	 */
	public function provide_users_who_cannot_edit_orders() {
		return array(
			'logged out' => array( null ),
			'customer'   => array( 'customer' ),
		);
	}

	/**
	 * @dataProvider provide_users_who_cannot_edit_orders
	 *
	 * @param string|null $role Role of the logged-in user, or null for a logged-out visitor.
	 */
	public function test_opt_in_requires_a_user_who_can_edit_orders( $role ) {
		$this->as_admin_ajax( $role );
		$this->opt_in();

		WC()->cart->calculate_totals();

		$this->assertSame( 0, $this->taxjar_requests );
	}

	/**
	 * Phone Orders' "Switch customer" logs in as the customer before it totals the cart.
	 * The shop manager who sent the request is still the one checked.
	 */
	public function test_opt_in_checks_the_user_who_sent_the_request_not_the_switched_customer() {
		$this->as_admin_ajax( 'shop_manager' );
		$this->opt_in();
		wp_set_current_user( $this->user_id( 'customer' ) );

		WC()->cart->calculate_totals();

		$this->assertSame( 1, $this->taxjar_requests );
		$this->assertEqualsWithDelta( 6.0, $this->cart_tax(), 0.001 );
	}

	/**
	 * A customer's request a handler switches to a shop manager is still refused.
	 */
	public function test_opt_in_refuses_a_customer_switched_to_a_shop_manager() {
		$this->as_admin_ajax( 'customer' );
		$this->opt_in();
		wp_set_current_user( $this->user_id( 'shop_manager' ) );

		WC()->cart->calculate_totals();

		$this->assertSame( 0, $this->taxjar_requests );
	}

	/**
	 * The opt-in is for admin-ajax.php. A cart loaded on a dashboard page stays skipped.
	 */
	public function test_opt_in_does_not_reach_admin_page_loads() {
		$this->as_admin_ajax();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		$this->opt_in();

		WC()->cart->calculate_totals();

		$this->assertSame( 0, $this->taxjar_requests );
	}

	/**
	 * The opt-in is for admin-ajax.php. A front-end AJAX cart, like the mini-cart, stays skipped.
	 */
	public function test_opt_in_does_not_reach_front_end_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'woocommerce_is_cart', '__return_true' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->opt_in();

		WC()->cart->calculate_totals();

		$this->assertSame( 0, $this->taxjar_requests );
	}

	/**
	 * Classic and block checkout, each with the opt-in filter off and on.
	 *
	 * @return array
	 */
	public function provide_checkouts() {
		return array(
			'classic checkout, filter off' => array( 'classic', false ),
			'classic checkout, filter on'  => array( 'classic', true ),
			'block checkout, filter off'   => array( 'block', false ),
			'block checkout, filter on'    => array( 'block', true ),
		);
	}

	/**
	 * Checkout is taxed the same with the filter on: one lookup and the same tax. The
	 * shopper is a shop manager, so only the admin AJAX checks keep the opt-in out.
	 *
	 * @dataProvider provide_checkouts
	 *
	 * @param string $checkout 'classic' for the checkout page, 'block' for the Store API.
	 * @param bool   $filter   Whether the opt-in filter returns true.
	 */
	public function test_checkout_is_taxed_the_same_with_the_filter_on( $checkout, $filter ) {
		if ( 'classic' === $checkout ) {
			add_filter( 'woocommerce_is_checkout', '__return_true' );
		} else {
			$GLOBALS['wp']->query_vars['rest_route'] = '/wc/store/v1/checkout';
		}
		wp_set_current_user( $this->user_id( 'shop_manager' ) );
		if ( $filter ) {
			$this->opt_in();
		}

		WC()->cart->calculate_totals();

		$this->assertSame( 1, $this->taxjar_requests );
		$this->assertEqualsWithDelta( 6.0, $this->cart_tax(), 0.001 );
	}
}
