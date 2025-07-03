<?php
/**
 * StoreNoticesController class.
 *
 * Controller class for store notices-related hooks.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Class StoreNoticesController
 */
class StoreNoticesController {

	/**
	 * Notifier instance.
	 *
	 * @var StoreNoticesNotifier
	 */
	private StoreNoticesNotifier $notifier;

	/**
	 * StoreNoticesController constructor.
	 *
	 * @param StoreNoticesNotifier $notifier The WC_Connect_Logger instance.
	 */
	public function __construct( StoreNoticesNotifier $notifier ) {
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
			|| ! function_exists( 'has_block' )
		) {
			return false;
		}
		return is_cart() && ! has_block( 'woocommerce/cart' );
	}

	/**
	 * Check if the page contains the classic checkout.
	 *
	 * @return bool
	 */
	private static function is_classic_checkout(): bool {
		if (
			! function_exists( 'is_checkout' )
			|| ! function_exists( 'has_block' )
		) {
			return false;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing --- No need to verify nonce here.
		return ! empty( $_POST ) && is_checkout() && ! has_block( 'woocommerce/checkout' );
	}
}
