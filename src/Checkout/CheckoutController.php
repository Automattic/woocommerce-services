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
		if ( ! self::is_classic_checkout() && ! self::is_classic_cart() ) {
			return;
		}

		$this->notifier->print_notices();
		$this->notifier::clear_notices();
	}

	/**
	 * Check if the page contains the classic cart.
	 *
	 * @return bool
	 */
	private static function is_classic_cart(): bool {
		if (
			! function_exists( 'is_cart' )
			|| ! function_exists( 'wc_post_content_has_shortcode' )
			|| ! function_exists( 'has_block' )
		) {
			return false;
		}
		return is_cart() && ( wc_post_content_has_shortcode( 'woocommerce_cart' ) || has_block( 'woocommerce/classic-shortcode' ) );
	}

	/**
	 * Check if the page contains the classic checkout.
	 *
	 * @return bool
	 */
	private static function is_classic_checkout(): bool {
		if (
			! function_exists( 'is_checkout' )
			|| ! function_exists( 'wc_post_content_has_shortcode' )
			|| ! function_exists( 'has_block' )
		) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing --- No need to verify nonce here.
		return ! empty( $_POST ) && is_checkout() && ( wc_post_content_has_shortcode( 'woocommerce_checkout' ) || has_block( 'woocommerce/classic-shortcode' ) );
	}
}
