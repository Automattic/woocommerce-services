<?php

if ( ! class_exists( 'WC_Connect_Logger' ) ) {

	define( 'WOOCOMMERCE_CONNECT_DEBUG_LOGGING_ENABLED_OPTION', 'wcc_debug_logging_enabled' );

	class WC_Connect_Logger {

		/**
		 * @var WC_Logger
		 */
		private $logger;

		private $is_logging_enabled = false;

		public function __construct( WC_Logger $logger ) {

			$this->logger = $logger;

			$this->is_logging_enabled = ( '1' === get_option( WOOCOMMERCE_CONNECT_DEBUG_LOGGING_ENABLED_OPTION, '0' ) );

		}

		/**
		 * Format a message with optional context for logging.
		 *
		 * @param string|WP_Error $message Either a string message, or WP_Error object.
		 * @param string          $context Optional. Context for the logged message.
		 * @return string The formatted log message.
		 */
		protected function format_message( $message, $context = '' ) {

			$formatted_message = $message;

			if ( is_wp_error( $message ) ) {
				$formatted_message = $message->get_error_code() . ' ' . $message->get_error_message();
			}

			if ( ! empty( $context ) ) {
				$formatted_message .= ' (' . $context . ')';
			}

			return $formatted_message;

		}

		public function enable_logging() {
			update_option( WOOCOMMERCE_CONNECT_DEBUG_LOGGING_ENABLED_OPTION, true );
			$this->is_logging_enabled = true;
			$this->log( "Logging enabled" );
		}

		public function disable_logging() {
			$this->log( "Logging disabled" );
			update_option( WOOCOMMERCE_CONNECT_DEBUG_LOGGING_ENABLED_OPTION, false );
			$this->is_logging_enabled = false;
		}

		public function is_logging_enabled() {
			return $this->is_logging_enabled;
		}

		/**
		 * Logs messages
		 *
		 * @param string $message Message to log
		 * @param string $context Optional context (e.g. a class or function name)
		 */
		public function log( $message, $context = '' ) {

			if ( $this->is_logging_enabled() ) {

				$log_message = $this->format_message( $message, $context );
				$this->logger->add( 'wc-connect', $log_message );
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( $log_message );
				}

			}

		}

	}

}
