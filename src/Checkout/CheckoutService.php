<?php
/**
 * CheckoutService class.
 *
 * Service class for checkout-related functionality.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\Checkout;

use Automattic\WCServices\Connect\WC_Connect_Options;
use Automattic\WCServices\Connect\WC_Connect_Service_Settings_Store;
use Automattic\WCServices\LabelPurchase\AddressNormalizationService;
use Automattic\WCServices\Logger;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Utilities\CartController;
use WC_Cart;

defined( 'ABSPATH' ) || exit;

/**
 * Class CheckoutService
 */
class CheckoutService {

	/**
	 * Is this a checkout page?
	 *
	 * @return bool
	 */
	public static function is_checkout_page(): bool {
		return is_checkout() || has_block( 'woocommerce/checkout' );
	}

	/**
	 * Is this a classic checkout request?
	 *
	 * @return bool
	 */
	public static function is_classic_checkout(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		return ! empty( $_POST ) && self::is_checkout_page();
	}

	/**
	 * Get the cart instance.
	 *
	 * @return false|WC_Cart
	 */
	public static function get_cart_instance() {
		try {
			$cart = ( new CartController() )->get_cart_instance();
		} catch ( RouteException $e ) {
			return false;
		}

		return $cart;
	}
}
