<?php

/**
 * Show admin notices when errors occur
 */

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Error_Notice' ) ) {

	class WC_Connect_Error_Notice {

		/**
		 * Nonce action guarding the "dismiss this notice" link.
		 *
		 * @var string
		 */
		const DISMISS_NONCE_ACTION = 'wc_connect_dismiss_error_notice';

		/**
		 * Query argument carrying the dismissal nonce.
		 *
		 * A dedicated name (rather than `_wpnonce`) so the dismiss link cannot overwrite a
		 * nonce the surrounding admin screen already put on the current URL.
		 *
		 * @var string
		 */
		const DISMISS_NONCE_NAME = '_wc_connect_notice_nonce';

		private static $inst = null;

		public static function instance() {
			if ( null === self::$inst ) {
				self::$inst = new WC_Connect_Error_Notice();
			}

			return self::$inst;
		}

		public function enable_notice( $error = true ) {
			WC_Connect_Options::update_option( 'error_notice', $error );
		}

		public function disable_notice() {
			WC_Connect_Options::update_option( 'error_notice', false );
		}

		public function render_notice() {
			$error_notice = filter_input( INPUT_GET, 'wc-connect-error-notice', FILTER_SANITIZE_ENCODED );
			// Only act on a dismissal that carries a valid nonce and comes from a user who may
			// manage the store. Anything else is ignored, so the notice simply stays visible.
			if ( 'disable' === $error_notice && $this->is_dismissal_authorized() ) {
				WC_Connect_Options::update_option( 'error_notice', false );
				$url = remove_query_arg( array( 'wc-connect-error-notice', self::DISMISS_NONCE_NAME ) );
				wp_safe_redirect( $url );
				exit;
			}

			if ( $this->notice_enabled() ) {
				$this->show_notice();
			}
		}

		/**
		 * Whether the current request is allowed to dismiss the error notice.
		 *
		 * Fails closed: a missing, stale or forged nonce, or a user without the
		 * `manage_woocommerce` capability, leaves the stored option untouched.
		 *
		 * @return bool
		 */
		private function is_dismissal_authorized() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return false;
			}

			$nonce = isset( $_GET[ self::DISMISS_NONCE_NAME ] )
				? sanitize_text_field( wp_unslash( $_GET[ self::DISMISS_NONCE_NAME ] ) )
				: '';

			return (bool) wp_verify_nonce( $nonce, self::DISMISS_NONCE_ACTION );
		}

		private function notice_enabled() {
			 return WC_Connect_Options::get_option( 'error_notice', false );
		}

		private function show_notice() {
			$link_status  = admin_url( 'admin.php?page=wc-status&tab=connect' );
			$link_dismiss = wp_nonce_url(
				add_query_arg( array( 'wc-connect-error-notice' => 'disable' ) ),
				self::DISMISS_NONCE_ACTION,
				self::DISMISS_NONCE_NAME
			);
			$error        = $this->notice_enabled();

			if ( ! is_wp_error( $error ) ) {
				return;
			}

			$message = false;

			if (
				'product_missing_weight' === $error->get_error_code() ||
				'product_missing_dimension' === $error->get_error_code()
			) {
				$error_data = $error->get_error_data();
				$id         = $error_data['product_id'];
				$product    = wc_get_product( $id );

				if (
					! $product ||
					( $product->has_weight() &&
						$product->get_length() &&
						$product->get_height() &&
						$product->get_width()
					)
				) {
					$this->disable_notice();
					return;
				}

				$product_name = $product->get_name();
				$product_id   = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				$message      = sprintf(
					__( '<strong>"%2$s" is missing weight, length, width, or height.</strong><br />Shipping rates cannot be calculated. <a href="%1$s">Enter dimensions and weight for %2$s</a> so your customers can purchase this item.', 'woocommerce-services' ),
					get_edit_post_link( $product_id ),
					$product_name
				);
			}

			if ( ! $message ) {
				return;
			}

			$allowed_html = array(
				'a'      => array( 'href' => array() ),
				'strong' => array(),
				'br'     => array(),
			);
			?>
			<div class='notice notice-error' style="position: relative;">
				<a href="<?php echo esc_url( $link_dismiss ); ?>" style="text-decoration: none;" class="notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce-services' ); ?>"></a>
				<p><?php echo wp_kses( $message, $allowed_html ); ?></p>
			</div>
			<?php
			echo '';
		}
	}
}
