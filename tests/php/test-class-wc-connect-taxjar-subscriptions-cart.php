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
	 * Load required classes before running tests.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
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

		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $this->product->get_id(), 1 );

		WC_Subscriptions_Synchroniser::$deferred_first_payment = true;
		WC_Subscriptions_Cart::$calculation_type               = 'none';
	}

	/**
	 * Tear down after each test.
	 */
	public function tear_down() {
		WC_Subscriptions_Synchroniser::reset();
		WC_Subscriptions_Cart::$calculation_type = 'none';

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
	 * The initial cart is still sent at the first payment, $0 for a deferred one, and
	 * the free trial is off again afterwards, as it was before.
	 */
	public function test_initial_cart_line_is_sent_at_the_first_payment() {
		$this->assertEquals( 0, $this->line_item( WC()->cart )['unit_price'] );
		$this->assertFalse( WC_Subscriptions_Synchroniser::$free_trial );
	}
}
