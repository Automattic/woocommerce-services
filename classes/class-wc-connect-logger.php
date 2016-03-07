<?php

if ( ! class_exists( 'WC_Connect_Logger' ) ) {

	class WC_Connect_Logger {

		/**
		 * @var WC_Logger
		 */
		private $logger;

		public function __construct( WC_Logger $logger ) {

			$this->logger = $logger;

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

		/**
		 * Logs messages
		 *
		 * @param string $message Message to log
		 * @param string $context Optional context (e.g. a class or function name)
		 */
		public function log( $message, $context = '' ) {
			// TODO add a debug control somewhere to turn logging on and off

			$log_message = $this->format_message( $message, $context );

			$this->logger->add( 'wc-connect', $log_message );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $log_message );
			}

		}

	}

}