<?php
/**
 * Tests for the hint next to the order screen's Recalculate button on a paid order.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Locked_Order_Tax_Hint
 *
 * The rule: with automated taxes on, an order whose tax is locked (it has a payment date)
 * keeps its charged tax when Recalculate is pressed, so the order items box says so next
 * to the button. WooCommerce shows Recalculate only while the order can be edited (pending,
 * on hold), so the hint shows only then. Nothing is shown for an order that isn't locked,
 * for a subscription (never locked), or when automated taxes are off.
 */
class WP_Test_WC_Connect_TaxJar_Locked_Order_Tax_Hint extends WC_Unit_Test_Case {

	/**
	 * The action WooCommerce fires in the order items box, just before Recalculate.
	 */
	const BUTTONS_ACTION = 'woocommerce_order_item_add_action_buttons';

	/**
	 * Text the hint must contain.
	 */
	const HINT = 'The tax on a paid order stays at the amount charged.';

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
	}

	/**
	 * A store that collects tax in Colorado.
	 */
	public function set_up() {
		parent::set_up();

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_default_country', 'US:CO' );
	}

	/**
	 * Put the options back.
	 */
	public function tear_down() {
		delete_option( WC_Connect_TaxJar_Integration::OPTION_NAME );
		delete_option( 'woocommerce_calc_taxes' );

		parent::tear_down();
	}

	/**
	 * Build the integration and register its hooks the way a store does.
	 *
	 * @param bool $automated_taxes Whether automated taxes are on.
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function make_integration( $automated_taxes ) {
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, $automated_taxes ? 'yes' : 'no' );

		$api_client = $this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock();
		$logger     = $this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock();
		$tracks     = $this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock();
		$notifier   = new Automattic\WCServices\StoreNotices\StoreNoticesNotifier( false );

		$integration = new WC_Connect_TaxJar_Integration( $api_client, $logger, 'https://example.com', $tracks, $notifier );
		$integration->init();

		return $integration;
	}

	/**
	 * Create an order with a status, paid or not.
	 *
	 * @param string $status Order status.
	 * @param bool   $paid   Whether the order has a payment date.
	 * @return WC_Order
	 */
	private function create_order( $status, $paid ) {
		$order = wc_create_order();
		$order->set_status( $status );
		if ( $paid ) {
			$order->set_date_paid( time() - HOUR_IN_SECONDS );
		}
		$order->save();

		return wc_get_order( $order->get_id() );
	}

	/**
	 * Load an order as a subscription from now on, as WooCommerce Subscriptions does.
	 *
	 * @param int $order_id Order id.
	 * @return WC_Order
	 */
	private function load_as_subscription( $order_id ) {
		require_once __DIR__ . '/class-wc-subscription-test-double.php';
		require_once __DIR__ . '/wcs-functions-test-double.php';
		add_filter(
			'woocommerce_order_class',
			static function ( $classname, $order_type = null, $id = null ) use ( $order_id ) {
				return (int) $id === (int) $order_id ? 'WC_Subscription' : $classname;
			},
			// After WooCommerce Admin's own filter, which gives its Order override class.
			PHP_INT_MAX,
			3
		);
		// An order already in WooCommerce's order cache is returned without the filter.
		if ( class_exists( Automattic\WooCommerce\Caches\OrderCache::class ) ) {
			wc_get_container()->get( Automattic\WooCommerce\Caches\OrderCache::class )->remove( $order_id );
		}
		$subscription = wc_get_order( $order_id );
		$this->assertInstanceOf( 'WC_Subscription', $subscription );

		return $subscription;
	}

	/**
	 * What the order items box prints at the action before Recalculate.
	 *
	 * @param mixed $order What WooCommerce passes.
	 * @return string
	 */
	private function render_buttons_action( $order ) {
		ob_start();
		do_action( self::BUTTONS_ACTION, $order );
		return (string) ob_get_clean();
	}

	/**
	 * @testdox A paid order moved back to on hold shows the hint next to Recalculate.
	 */
	public function test_paid_order_on_hold_shows_hint() {
		$this->make_integration( true );
		$order = $this->create_order( 'on-hold', true );
		$this->assertTrue( $order->is_editable(), 'Precondition: WooCommerce shows Recalculate for this order.' );

		$html = $this->render_buttons_action( $order );

		$this->assertStringContainsString( self::HINT, $html );
		$this->assertStringContainsString( 'the order notes say what changed and what to do next.', $html );
		$this->assertStringContainsString( '<span class="description wc-connect-locked-order-tax-hint">', $html );
	}

	/**
	 * @testdox A paid order that is pending payment again shows the hint.
	 */
	public function test_paid_order_pending_shows_hint() {
		$this->make_integration( true );
		$order = $this->create_order( 'pending', true );

		$this->assertStringContainsString( self::HINT, $this->render_buttons_action( $order ) );
	}

	/**
	 * @testdox An unpaid order on hold shows no hint: Recalculate updates its tax.
	 */
	public function test_unpaid_order_on_hold_shows_no_hint() {
		$this->make_integration( true );
		$order = $this->create_order( 'on-hold', false );

		$this->assertSame( '', $this->render_buttons_action( $order ) );
	}

	/**
	 * @testdox A paid order that is processing shows no hint: WooCommerce shows no Recalculate button for it.
	 */
	public function test_processing_order_shows_no_hint() {
		$this->make_integration( true );
		$order = $this->create_order( 'processing', true );
		$this->assertFalse( $order->is_editable(), 'Precondition: WooCommerce hides Recalculate for this order.' );

		$this->assertSame( '', $this->render_buttons_action( $order ) );
	}

	/**
	 * @testdox A subscription with a payment date shows no hint: its tax follows its edits.
	 */
	public function test_subscription_shows_no_hint() {
		$this->make_integration( true );
		$order        = $this->create_order( 'on-hold', true );
		$subscription = $this->load_as_subscription( $order->get_id() );
		$this->assertNotNull( $subscription->get_date_paid( 'edit' ), 'Precondition: the subscription has a payment date.' );

		$this->assertSame( '', $this->render_buttons_action( $subscription ) );
	}

	/**
	 * @testdox With automated taxes off, a paid order on hold shows no hint.
	 */
	public function test_automated_taxes_off_shows_no_hint() {
		$integration = $this->make_integration( false );
		$order       = $this->create_order( 'on-hold', true );

		$this->assertFalse( has_action( self::BUTTONS_ACTION, array( $integration, 'show_locked_order_tax_hint' ) ) );
		$this->assertSame( '', $this->render_buttons_action( $order ) );
	}

	/**
	 * @testdox Automated taxes turned off after the hooks were added: a paid order on hold shows no hint.
	 */
	public function test_automated_taxes_turned_off_later_shows_no_hint() {
		$integration = $this->make_integration( true );
		$order       = $this->create_order( 'on-hold', true );
		update_option( WC_Connect_TaxJar_Integration::OPTION_NAME, 'no' );

		ob_start();
		$integration->show_locked_order_tax_hint( $order );
		$this->assertSame( '', (string) ob_get_clean() );
	}

	/**
	 * @testdox Something other than an order passed to the action prints nothing and does not fail.
	 */
	public function test_non_order_prints_nothing() {
		$this->make_integration( true );

		$this->assertSame( '', $this->render_buttons_action( null ) );
		$this->assertSame( '', $this->render_buttons_action( 123 ) );
	}
}
