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
			update_option( $this->$option_key, true );
		}

		public function disable_notice() {
			update_option( $this->$option_key, false );
		}

		public function render_notice() {
			if ( is_admin() && $this->notice_enabled() ) {
				$this->show_notice();
			}
		}

		private function notice_enabled() {
			return get_option( self::$option_key, false );
		}

		private function show_notice() {

		}
	}

}
