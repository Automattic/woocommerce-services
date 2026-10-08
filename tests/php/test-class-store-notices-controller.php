<?php
/**
 * Tests for StoreNoticesController.
 *
 * @package Automattic/WCServices
 */

use Automattic\WCServices\StoreNotices\StoreNoticesController;
use Automattic\WCServices\StoreNotices\StoreNoticesNotifier;

/**
 * Class WP_Test_Store_Notices_Controller
 */
class WP_Test_Store_Notices_Controller extends WC_Unit_Test_Case {

	/**
	 * Controller under test.
	 *
	 * @var StoreNoticesController
	 */
	private $controller;

	/**
	 * Notifier shared with the controller.
	 *
	 * @var StoreNoticesNotifier
	 */
	private $notifier;

	/**
	 * Set up a classic checkout request with a WC session.
	 */
	public function set_up() {
		parent::set_up();

		if ( empty( WC()->session ) ) {
			WC()->initialize_session();
		}

		$this->notifier   = new StoreNoticesNotifier( false );
		$this->controller = new StoreNoticesController( $this->notifier );

		// Classic checkout POST, as during update_order_review or placing an order.
		$_POST = array( 'billing_postcode' => '902' );
		add_filter( 'woocommerce_is_checkout', '__return_true' );
	}

	/**
	 * Reset request, notice and hook state.
	 */
	public function tear_down() {
		global $wp_actions;

		unset( $wp_actions['woocommerce_checkout_update_order_review'] );
		$_POST = array();
		remove_filter( 'woocommerce_is_checkout', '__return_true' );
		StoreNoticesNotifier::clear_notices();
		wc_clear_notices();

		parent::tear_down();
	}

	/**
	 * WooCommerce marks update_order_review as failed when the notice queue is not empty, and the classic
	 * checkout then blurs every field, which re-triggers the update - an endless loop (WOOTAX-302).
	 */
	public function test_notices_stay_out_of_the_wc_notice_queue_during_update_order_review() {
		do_action( 'woocommerce_checkout_update_order_review', '' );
		$this->notifier->error( 'Invalid ZIP Code entered.', array(), 'taxjar' );

		$this->controller->maybe_display_notices();

		$this->assertSame( 0, wc_notice_count() );
		$this->assertNotEmpty( StoreNoticesNotifier::get_notices(), 'The notices must survive for the checkout fragment.' );
	}

	/**
	 * The notices reach the classic checkout through a fragment instead, and are cleared once rendered.
	 */
	public function test_update_order_review_fragment_renders_and_clears_notices() {
		$this->notifier->error( 'Invalid ZIP Code entered.', array(), 'taxjar' );

		$fragments = $this->controller->add_update_order_review_fragment( array( 'existing' => 'kept' ) );

		$this->assertSame( 'kept', $fragments['existing'] );
		$this->assertStringContainsString( 'Invalid ZIP Code entered.', $fragments[ StoreNoticesController::CLASSIC_CHECKOUT_NOTICES_SELECTOR ] );
		$this->assertStringContainsString( 'woocommerce-error', $fragments[ StoreNoticesController::CLASSIC_CHECKOUT_NOTICES_SELECTOR ] );
		$this->assertEmpty( StoreNoticesNotifier::get_notices() );
	}

	/**
	 * Without notices the fragment still replaces the container, so a corrected ZIP clears the message.
	 */
	public function test_update_order_review_fragment_is_an_empty_container_without_notices() {
		$fragments = $this->controller->add_update_order_review_fragment( array() );

		$this->assertSame(
			'<div class="wcservices-checkout-notices"></div>',
			$fragments[ StoreNoticesController::CLASSIC_CHECKOUT_NOTICES_SELECTOR ]
		);
	}

	/**
	 * Placing the order still goes through the WC notice queue, so an invalid ZIP keeps blocking it.
	 */
	public function test_notices_still_reach_the_wc_notice_queue_when_placing_the_order() {
		$this->notifier->error( 'Invalid ZIP Code entered.', array(), 'taxjar' );

		$this->controller->maybe_display_notices();

		$this->assertTrue( wc_has_notice( 'Invalid ZIP Code entered.', 'error' ) );
	}
}
