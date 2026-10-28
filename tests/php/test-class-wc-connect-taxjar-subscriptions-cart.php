<?php
/**
 * Tests for how a WooCommerce Subscriptions recurring cart is sent to TaxJar.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Subscriptions_Cart
 *
 * Subscriptions totals one recurring cart per subscription, inside the initial cart's
 * total. It sets its calculation type back to 'none' on woocommerce_after_calculate_totals
 * at priority 1, before the TaxJar hook at priority 20 sees the recurring cart. Reading
 * that as the initial cart unset the free trial Subscriptions gives a synced product, and
 * the sign-up order of a synced subscription then charged shipping.
 */
class WP_Test_WC_Connect_TaxJar_Subscriptions_Cart extends WC_Unit_Test_Case {

	/**
	 * Recurring cart key Subscriptions gives a product renewed every 2 months, synced to the 1st.
	 */
	const RECURRING_CART_KEY = '2026_11_01_every_2nd_month_synced';

	/**
	 * Integration under test.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $integration;

	/**
	 * $10 product in the cart.
	 *
	 * @var WC_Product
	 */
	private $product;

	/**
	 * The customer's shipping location before the test, put back afterwards.
	 *
	 * @var array
	 */
	private $shipping_location;

	/**
	 * Load required classes before running tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
		require_once __DIR__ . '/class-wc-subscriptions-synchroniser-test-double.php';
		require_once __DIR__ . '/class-wc-subscriptions-cart-test-double.php';
	}

	/**
	 * A $10 synced subscription in the cart, first payment moved to the sync date.
	 */
	public function set_up() {
		parent::set_up();

		$this->integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( '_log' ) )
			->getMock();

		$this->product = WC_Helper_Product::create_simple_product();
		$this->product->set_regular_price( 10 );
		$this->product->save();

		$this->shipping_location = array(
			WC()->customer->get_shipping_country(),
			WC()->customer->get_shipping_state(),
			WC()->customer->get_shipping_postcode(),
			WC()->customer->get_shipping_city(),
		);

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		WC_Subscriptions_Synchroniser::$deferred_first_payment = true;
		WC_Subscriptions_Cart::$calculation_type               = 'none';
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		remove_filter( 'woocommerce_is_checkout', '__return_true' );
		WC_Subscriptions_Synchroniser::reset();
		WC_Subscriptions_Cart::$calculation_type = 'none';
		WC_Subscriptions_Cart::$sign_up_fee      = 0.0;
		remove_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $this->integration, 'override_cart_item_tax_rates' ), 10 );
		delete_option( 'woocommerce_calc_taxes' );
		WC()->customer->set_shipping_location( ...$this->shipping_location );

		WC()->cart->empty_cart();
		WC_Helper_Product::delete_product( $this->product->get_id() );

		parent::tear_down();
	}

	/**
	 * The recurring cart as Subscriptions builds it: a clone of the cart, with its key set.
	 *
	 * @return WC_Cart
	 */
	private function recurring_cart() {
		$recurring_cart                     = clone WC()->cart;
		$recurring_cart->recurring_cart_key = self::RECURRING_CART_KEY;

		return $recurring_cart;
	}

	/**
	 * The one line get_line_items() returns for a cart.
	 *
	 * @param WC_Cart $cart Cart.
	 * @return array
	 */
	private function line_item( $cart ) {
		$method = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', 'get_line_items' );
		$method->setAccessible( true );
		$line_items = $method->invoke( $this->integration, $cart );

		$this->assertCount( 1, $line_items );

		return reset( $line_items );
	}

	/**
	 * Subscriptions keeps the free trial on while it totals the recurring carts, and
	 * decides whether the initial order ships from it afterwards.
	 */
	public function test_recurring_cart_keeps_the_free_trial_of_a_synced_product() {
		WC_Subscriptions_Synchroniser::$free_trial = true;

		$this->line_item( $this->recurring_cart() );

		$this->assertTrue( WC_Subscriptions_Synchroniser::$free_trial );
	}

	/**
	 * A recurring cart is the renewal, so its line goes to TaxJar at the renewal price.
	 */
	public function test_recurring_cart_line_is_sent_at_the_renewal_price() {
		WC_Subscriptions_Synchroniser::$free_trial = true;

		$this->assertSame( '10', $this->line_item( $this->recurring_cart() )['unit_price'] );
	}

	/**
	 * When the lookup fails, nothing totals the recurring cart again, so nothing puts a
	 * removed free trial back. That is so even for a customer in the store's own state,
	 * whose lookup did reach TaxJar.
	 */
	public function test_recurring_cart_keeps_the_free_trial_when_the_lookup_fails() {
		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock();
		$api_client->expects( $this->once() )
			->method( 'proxy_request' )
			->willReturn( new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' ) );

		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->setConstructorArgs(
				array(
					$api_client,
					$this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock(),
					'https://example.com',
					$this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock(),
				)
			)
			->onlyMethods( array( 'get_store_settings', '_log' ) )
			->getMock();
		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'CO',
				'postcode' => '80202',
				'city'     => 'Denver',
				'street'   => '1 Main St',
			)
		);

		WC()->customer->set_shipping_location( 'US', 'CO', '80202', 'Denver' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );
		WC_Subscriptions_Synchroniser::$free_trial = true;

		$integration->calculate_totals( $this->recurring_cart() );

		$this->assertTrue( WC_Subscriptions_Synchroniser::$free_trial );
	}

	/**
	 * A renewal under a price threshold leaves the sign-up order taxed.
	 *
	 * A $50 plan with a $100 sign-up fee, where clothing under $110 is exempt. Subscriptions
	 * totals the recurring cart first, and it shares its product objects with the main
	 * cart. The recurring cart's $50 line comes back exempt, and its tax status must not
	 * carry over to the $150 sign-up line.
	 */
	public function test_an_exempt_renewal_leaves_the_sign_up_order_taxed() {
		update_option( 'woocommerce_calc_taxes', 'yes' );

		$this->integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'smartcalcs_cache_request', 'get_store_settings', '_log' ) )
			->getMock();
		$this->integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'CO',
				'postcode' => '80202',
				'city'     => 'Denver',
				'street'   => '1 Main St',
			)
		);
		// 2.9% state tax, and 0% for a line sent as exempt or priced under $110.
		$this->integration->method( 'smartcalcs_cache_request' )->willReturnCallback(
			function ( $json ) {
				$body  = json_decode( $json, true );
				$lines = array();
				foreach ( $body['line_items'] ?? array() as $line_item ) {
					$rate    = '99999' === (string) $line_item['product_tax_code'] || (float) $line_item['unit_price'] < 110 ? 0.0 : 0.029;
					$lines[] = array(
						'id'                   => $line_item['id'],
						'combined_tax_rate'    => $rate,
						'state_sales_tax_rate' => $rate,
					);
				}

				return array(
					'body' => wp_json_encode(
						array(
							'tax' => array(
								'rate'            => 0.029,
								'freight_taxable' => false,
								'has_nexus'       => true,
								'jurisdictions'   => array(
									'county' => 'DENVER',
									'city'   => 'DENVER',
								),
								'breakdown'       => array( 'line_items' => $lines ),
							),
						)
					),
				);
			}
		);
		add_filter( 'woocommerce_cart_totals_get_item_tax_rates', array( $this->integration, 'override_cart_item_tax_rates' ), 10, 3 );

		$this->product->set_regular_price( 50 );
		$this->product->save();
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );
		WC()->customer->set_shipping_location( 'US', 'CO', '80202', 'Denver' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );
		WC_Subscriptions_Synchroniser::$deferred_first_payment = false;
		WC_Subscriptions_Cart::$sign_up_fee                    = 100.0;

		WC()->cart->calculate_totals();
		$recurring_cart = $this->recurring_cart();
		$recurring_cart->calculate_totals();
		$this->integration->calculate_totals( $recurring_cart );
		$this->integration->calculate_totals( WC()->cart );

		$this->assertEqualsWithDelta( 0.0, (float) $recurring_cart->get_cart_contents_tax(), 0.001, 'TaxJar answered the $50 renewal as exempt' );
		$this->assertNotSame( '99999', $this->line_item( WC()->cart )['product_tax_code'], 'The sign-up line is sent as taxable' );
		// $150 sign-up order at 2.9%. WooCommerce taxes the cart line at its $50 price.
		$this->assertEqualsWithDelta( 1.45, (float) WC()->cart->get_cart_contents_tax(), 0.001, 'Sign-up order tax' );
	}

	/**
	 * The initial cart is still sent at the first payment, $0 for a deferred one, and
	 * the free trial is off again afterwards, as it was before.
	 */
	public function test_initial_cart_line_is_sent_at_the_first_payment() {
		$this->assertEquals( 0, $this->line_item( WC()->cart )['unit_price'] );
		$this->assertFalse( WC_Subscriptions_Synchroniser::$free_trial );
	}
}
