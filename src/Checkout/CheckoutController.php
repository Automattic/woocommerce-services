<?php
/**
 * CheckoutController class.
 *
 * Controller class for checkout-related hooks.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\Checkout;

defined( 'ABSPATH' ) || exit;

/**
 * Class CheckoutController
 */
class CheckoutController {

	/**
	 * Notifier instance.
	 *
	 * @var CheckoutNotifier
	 */
	private CheckoutNotifier $notifier;

	/**
	 * CheckoutController constructor.
	 *
	 * @param CheckoutNotifier $notifier The WC_Connect_Logger instance.
	 */
	public function __construct( CheckoutNotifier $notifier ) {
		$this->notifier = $notifier;

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_display_notices' ) );
	}

	/**
	 * Maybe display address validation notices.
	 */
	public function maybe_display_notices() {
		if ( ! CheckoutService::is_classic_checkout() ) {
			return;
		}

		$this->notifier->print_notices();
		$this->notifier::clear_notices();
	}
}
