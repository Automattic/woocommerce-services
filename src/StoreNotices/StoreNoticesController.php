<?php
/**
 * StoreNoticesController class.
 *
 * Controller class for store notices-related hooks.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreNotices;

use WC_Cart;
use WP_Error;

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
		add_filter( 'woocommerce_store_api_cart_errors', array( $this, 'add_store_api_cart_errors' ), 10, 2 );
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

	/**
	 * If there are error notices, we need to block the block checkout to prevent proceeding with checkout.
	 *
	 * @param WP_Error $cart_errors List of errors in the cart.
	 * @param WC_Cart  $cart Cart object.
	 * @return WP_Error
	 */
	public function add_store_api_cart_errors( $cart_errors, $cart ) {
		// Get notices from StoreNoticesNotifier.
		$notices = StoreNoticesNotifier::get_notices();

		// Check if there are any error notices.
		if ( ! empty( $notices['error'] ) ) {
			foreach ( $notices['error'] as $notice ) {
				// Add each error notice to the $cart_errors object to block checkout.
				$cart_errors->add( 'wcservices_validation', $notice['message'] );
			}
		}

		return $cart_errors;
	}
}
