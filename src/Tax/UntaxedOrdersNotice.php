<?php
/**
 * Tells store managers about orders that got no tax because the tax service did not answer.
 *
 * When a tax lookup fails and the store has no saved tax rates for the address, the order
 * is placed (or recalculated) with no tax, and the order gets a note. This keeps a short
 * list of those orders and shows a notice on WooCommerce admin screens and the Dashboard
 * until a store manager dismisses it. A later order like that shows it again.
 *
 * @internal Not part of the public API; may change without notice.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\Tax;

use WC_Order;

defined( 'ABSPATH' ) || exit;

/**
 * Admin notice for orders that got no tax because the tax service did not answer.
 */
final class UntaxedOrdersNotice {

	/**
	 * Option that holds the orders, site-scoped.
	 */
	const OPTION_NAME = 'wc_connect_taxes_untaxed_orders';

	/**
	 * Admin-post action that dismisses the notice.
	 */
	const DISMISS_ACTION = 'wc_connect_dismiss_untaxed_orders';

	/**
	 * How many order ids are kept. The count goes on past it.
	 */
	const KEPT_ORDERS = 20;

	/**
	 * How many orders the notice links to.
	 */
	const LINKED_ORDERS = 5;

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( 'admin_notices', array( $this, 'render' ) );
		add_action( 'admin_post_' . self::DISMISS_ACTION, array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Add an order to the notice.
	 *
	 * @param WC_Order $order The order that got no tax.
	 */
	public function record( $order ) {
		if ( ! $order instanceof WC_Order || ! $order->get_id() ) {
			return;
		}

		$stored = $this->get_stored();
		$id     = (int) $order->get_id();

		if ( in_array( $id, $stored['order_ids'], true ) ) {
			return;
		}

		$stored['order_ids'][] = $id;
		$stored['order_ids']   = array_slice( $stored['order_ids'], -self::KEPT_ORDERS );
		++$stored['count'];

		update_option( self::OPTION_NAME, $stored, false );
	}

	/**
	 * Take an order off the notice, as it now has tax.
	 *
	 * The option is written only when the order is listed. An order past the kept list
	 * cannot be told apart, so the count stays.
	 *
	 * @param int $order_id The order's id.
	 */
	public function forget( $order_id ) {
		$stored = $this->get_stored();
		$key    = array_search( (int) $order_id, $stored['order_ids'], true );

		if ( false === $key ) {
			return;
		}

		unset( $stored['order_ids'][ $key ] );
		$stored['order_ids'] = array_values( $stored['order_ids'] );
		$stored['count']     = max( $stored['count'] - 1, count( $stored['order_ids'] ) );

		if ( 0 === $stored['count'] ) {
			delete_option( self::OPTION_NAME );
			return;
		}

		update_option( self::OPTION_NAME, $stored, false );
	}

	/**
	 * The orders the notice is about, as stored.
	 *
	 * @return array{count: int, order_ids: int[]} Order ids oldest first, at most KEPT_ORDERS of them.
	 */
	public function get_stored() {
		$stored = get_option( self::OPTION_NAME, array() );
		$stored = is_array( $stored ) ? $stored : array();

		$ids = isset( $stored['order_ids'] ) && is_array( $stored['order_ids'] ) ? array_values( array_filter( array_map( 'intval', $stored['order_ids'] ) ) ) : array();

		return array(
			'count'     => max( isset( $stored['count'] ) ? (int) $stored['count'] : 0, count( $ids ) ),
			'order_ids' => $ids,
		);
	}

	/**
	 * Print the notice, if there is one and this is a screen it is shown on.
	 */
	public function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ( ! $this->is_woocommerce_screen() && ! $this->is_dashboard() ) ) {
			return;
		}

		$stored = $this->get_stored();
		if ( 0 === $stored['count'] ) {
			return;
		}

		$links = array();
		foreach ( array_reverse( $stored['order_ids'] ) as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				continue;
			}

			$links[] = sprintf( '<a href="%s">#%s</a>', esc_url( $order->get_edit_order_url() ), esc_html( $order->get_order_number() ) );
			if ( count( $links ) >= self::LINKED_ORDERS ) {
				break;
			}
		}

		$message = esc_html(
			sprintf(
				/* translators: %d: number of orders. */
				_n(
					'%d order got no sales tax, because the sales tax service did not answer and the store has no saved tax rates for its address. The order has a note about it. Check whether tax is due on it.',
					'%d orders got no sales tax, because the sales tax service did not answer and the store has no saved tax rates for their addresses. Each order has a note about it. Check whether tax is due on them.',
					$stored['count'],
					'woocommerce-services'
				),
				$stored['count']
			)
		);

		$dismiss_url = wp_nonce_url( add_query_arg( 'action', self::DISMISS_ACTION, admin_url( 'admin-post.php' ) ), self::DISMISS_ACTION );
		?>
		<div class="notice notice-warning wc-connect-untaxed-orders-notice" style="position: relative; padding-right: 48px;">
			<a href="<?php echo esc_url( $dismiss_url ); ?>" style="text-decoration: none;" class="notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce-services' ); ?>"></a>
			<p><?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?></p>
			<?php if ( $links ) : ?>
				<p>
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %s: comma-separated links to orders, such as "#123, #124". */
							__( 'Latest: %s', 'woocommerce-services' ),
							implode( ', ', $links )
						),
						array( 'a' => array( 'href' => array() ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Dismiss the notice, then return to the page the store manager came from.
	 */
	public function handle_dismiss() {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( wp_verify_nonce( $nonce, self::DISMISS_ACTION ) && current_user_can( 'manage_woocommerce' ) ) {
			delete_option( self::OPTION_NAME );
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}

	/**
	 * Is the current admin screen the WordPress Dashboard?
	 *
	 * @return bool
	 */
	private function is_dashboard() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return null !== $screen && 'dashboard' === $screen->id;
	}

	/**
	 * Is the current admin screen a WooCommerce one?
	 *
	 * @return bool
	 */
	private function is_woocommerce_screen() {
		if ( ! function_exists( 'get_current_screen' ) || ! function_exists( 'wc_get_screen_ids' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return null !== $screen && in_array( $screen->id, wc_get_screen_ids(), true );
	}
}
