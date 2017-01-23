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

		private static $option_key = 'wc_connect_error_notice';

		public function enable_notice() {
			update_option( self::$option_key, true );
		}

		public function disable_notice() {
			update_option( self::$option_key, false );
		}

		public function render_notice() {
			$error_notice = filter_input( INPUT_GET, 'wc-connect-error-notice' );
			if ( 'disable' === $error_notice ) {
				update_option( self::$option_key, false );
				$url = home_url( remove_query_arg( 'wc-connect-error-notice' ) );
				wp_safe_redirect( $url );
				exit;
			}

			if ( $this->notice_enabled() ) {
				$this->show_notice();
			}
		}

		private function notice_enabled() {
			return get_option( self::$option_key, false );
		}

		private function show_notice() {
			$link_status = admin_url( 'admin.php?page=wc-status&tab=connect' );
			$link_dismiss = home_url( add_query_arg( array( 'wc-connect-error-notice' => 'disable' ) ) );

			$message = sprintf(
				__( 'An error occurred in Connect for WooCommerce. Details are logged <a href="%s">here</a>.', 'connectforwoocommerce' ),
				$link_status, $link_dismiss
			);
?>
			<div class='notice notice-error' style="position: relative;">
				<a href="<?php echo esc_url( $link_dismiss ); ?>" style="text-decoration: none;" class="notice-dismiss" title="<?php esc_attr_e( 'Dismiss this notice', 'connectforwoocommerce' ); ?>"></a>
				<p><?php echo $message; ?></p>
			</div>
<?php
			echo "";
		}
	}

}
