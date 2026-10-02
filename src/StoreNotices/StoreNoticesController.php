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
	 * Selector of the classic checkout notices container, replaced by the update_order_review fragment.
	 *
	 * @var string
	 */
	const CLASSIC_CHECKOUT_NOTICES_SELECTOR = 'div.wcservices-checkout-notices';

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

		add_action( 'woocommerce_after_calculate_totals', array( $this, 'maybe_display_notices' ), 30 );
		add_filter( 'woocommerce_store_api_cart_errors', array( $this, 'add_store_api_cart_errors' ), 10, 2 );
		add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'print_classic_checkout_notices_container' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'add_update_order_review_fragment' ) );
	}

	/**
	 * Maybe display address validation notices.
	 */
	public function maybe_display_notices() {
		if ( ! self::is_classic_checkout() && ! self::is_classic_cart() ) {
			return;
		}

		// WooCommerce marks update_order_review as failed when the notice queue is not empty, and the classic
		// checkout then blurs every field, which triggers another update: an endless loop. The notices are sent
		// as a fragment instead (see add_update_order_review_fragment()). Placing the order still queues them.
		if ( did_action( 'woocommerce_checkout_update_order_review' ) ) {
			return;
		}

		$this->notifier->print_notices();
		$this->notifier::clear_notices();
	}

	/**
	 * Print the classic checkout container that the update_order_review fragment fills with the notices.
	 */
	public function print_classic_checkout_notices_container() {
		echo '<div class="wcservices-checkout-notices"></div>';
	}

	/**
	 * Send the notices to the classic checkout as an update_order_review fragment.
	 *
	 * The container is replaced even when there are no notices, so a corrected address clears the message.
	 *
	 * @param array $fragments Checkout fragments.
	 *
	 * @return array
	 */
	public function add_update_order_review_fragment( $fragments ) {
		if ( ! is_array( $fragments ) ) {
			return $fragments;
		}

		$fragments[ self::CLASSIC_CHECKOUT_NOTICES_SELECTOR ] = '<div class="wcservices-checkout-notices">' . $this->get_notices_html() . '</div>';
		$this->notifier::clear_notices();

		return $fragments;
	}

	/**
	 * Render the notices with the WooCommerce notice templates.
	 *
	 * @return string
	 */
	private function get_notices_html(): string {
		$html = '';

		foreach ( StoreNoticesNotifier::get_notices() as $type => $notices ) {
			// The types wc_print_notices() renders by default, so the fragment shows what the notice queue would.
			if ( ! in_array( $type, array( 'error', 'success', 'notice' ), true ) ) {
				continue;
			}

			foreach ( $notices as $notice ) {
				$html .= wc_print_notice( $this->notifier->maybe_get_formatted_message( $notice['message'], $notice['data'] ), $type, array(), true );
			}
		}

		return $html;
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
