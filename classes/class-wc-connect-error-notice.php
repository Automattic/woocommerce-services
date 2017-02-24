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

		private static $inst = null;

		public static function instance() {
			if ( null === self::$inst ) {
				self::$inst = new WC_Connect_Error_Notice();
			}

			return self::$inst;
		}

		public function enable_notice() {
			WC_Connect_Options::update_option( 'error_notice', true );
		}

		public function disable_notice() {
			WC_Connect_Options::update_option( 'error_notice', false );
		}

		public function render_notice() {
			$error_notice = filter_input( INPUT_GET, 'wc-connect-error-notice' );
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

		private function notice_enabled() {
			return WC_Connect_Options::get_option( 'error_notice', false );
		}

		private function show_notice() {
			$link_status = admin_url( 'admin.php?page=wc-status&tab=connect' );
			$link_dismiss = add_query_arg( array( 'wc-connect-error-notice' => 'disable' ) );

			$message = sprintf(
				__( 'An error occurred in WooCommerce Services. Details are logged <a href="%s">here</a>.', 'woocommerce-services' ),
				$link_status, $link_dismiss
			);
?>
			<div class='notice notice-error' style="position: relative;">
				<a href="<?php echo esc_url( $link_dismiss ); ?>" style="text-decoration: none;" class="notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'woocommerce-services' ); ?>"></a>
				<p><?php echo $message; ?></p>
			</div>
<?php
			echo "";
		}
	}

}
