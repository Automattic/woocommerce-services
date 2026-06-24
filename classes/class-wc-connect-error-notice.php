<?php
/**
 * Show admin notices when errors occur.
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Error_Notice' ) ) {

	/**
	 * Manages the admin error notice shown when shipping errors occur.
	 */
	class WC_Connect_Error_Notice {


		/**
		 * The singleton instance of this class.
		 *
		 * @var WC_Connect_Error_Notice|null
		 */
		private static $inst = null;

		/**
		 * Get the singleton instance of this class.
		 *
		 * @return WC_Connect_Error_Notice The singleton instance.
		 */
		public static function instance() {
			if ( null === self::$inst ) {
				self::$inst = new WC_Connect_Error_Notice();
			}

			return self::$inst;
		}

		/**
		 * Enable the error notice.
		 *
		 * @param mixed $error Optional. The error to store, or true to enable a generic notice.
		 * @return void
		 */
		public function enable_notice( $error = true ) {
			WC_Connect_Options::update_option( 'error_notice', $error );
		}

		/**
		 * Disable the error notice.
		 *
		 * @return void
		 */
		public function disable_notice() {
			WC_Connect_Options::update_option( 'error_notice', false );
		}

		/**
		 * Render the error notice, handling dismissal requests.
		 *
		 * @return void
		 */
		public function render_notice() {
			$error_notice = filter_input( INPUT_GET, 'wc-connect-error-notice', FILTER_SANITIZE_ENCODED );
			if ( 'disable' === $error_notice ) {
				WC_Connect_Options::update_option( 'error_notice', false );
				$url = remove_query_arg( 'wc-connect-error-notice' );
				wp_safe_redirect( $url );
				exit;
			}

			if ( $this->notice_enabled() ) {
				$this->show_notice();
			}
		}

		/**
		 * Get the currently stored error notice value.
		 *
		 * @return mixed The stored error notice value, or false if none.
		 */
		private function notice_enabled() {
			return WC_Connect_Options::get_option( 'error_notice', false );
		}

		/**
		 * Output the error notice markup for the current error.
		 *
		 * @return void
		 */
		private function show_notice() {
			$link_status  = admin_url( 'admin.php?page=wc-status&tab=connect' );
			$link_dismiss = add_query_arg( array( 'wc-connect-error-notice' => 'disable' ) );
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
					/* translators: %1$s: URL to edit the product, %2$s: product name */
					__( '<strong>"%2$s" is missing weight, length, width, or height.</strong><br />Shipping rates cannot be calculated. <a href="%1$s">Enter dimensions and weight for %2$s</a> so your customers can purchase this item.', 'woocommerce-services' ),
					get_edit_post_link( $product_id ),
					$product_name
				);
			}//end if

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
}//end if
