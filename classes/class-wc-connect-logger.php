<?php

if ( ! class_exists( 'WC_Connect_Logger' ) ) {

	class WC_Connect_Logger {

		/**
		 * @var WC_Logger
		 */
		private $logger;

		private $is_logging_enabled = false;
		private $is_debug_enabled   = false;

		private $feature;

		public function __construct( WC_Logger $logger, $feature = '' ) {

			$this->logger  = $logger;
			$this->feature = strtolower( $feature );

			$this->is_logging_enabled = WC_Connect_Options::get_option( 'debug_logging_enabled', false );
			$this->is_debug_enabled   = WC_Connect_Options::get_option( 'debug_display_enabled', false );

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
			WC_Connect_Options::update_option( 'debug_logging_enabled', true );
			$this->is_logging_enabled = true;
			$this->log( "Logging enabled" );
		}

		public function disable_logging() {
			$this->log( "Logging disabled" );
			WC_Connect_Options::update_option( 'debug_logging_enabled', false );
			$this->is_logging_enabled = false;
		}

		public function enable_debug() {
			WC_Connect_Options::update_option( 'debug_display_enabled', true );
			$this->is_debug_enabled = true;
			$this->log( 'Debug enabled' );
		}

		public function disable_debug() {
			$this->log( 'Debug disabled' );
			WC_Connect_Options::update_option( 'debug_display_enabled', false );
			$this->is_debug_enabled = false;
		}

		public function is_debug_enabled() {
			return $this->is_debug_enabled;
		}

		public function is_logging_enabled() {
			return $this->is_logging_enabled;
		}

		/**
		 * Log debug by printing it as notice when debugging is enabled.
		 *
		 * @param string $message Debug message.
		 * @param string $type    Notice type.
		 */
		public function debug( $message, $type = 'notice' ) {
			if ( $this->is_debug_enabled()  ) {
				wc_add_notice( $message, $type );
			}
		}

		/**
		 * Logs messages even if debugging is disabled
		 *
		 * @param string $message Message to log
		 * @param string $context Optional context (e.g. a class or function name)
		 */
		public function error( $message, $context = '' ) {
			WC_Connect_Error_Notice::instance()->enable_notice();
			$this->log( $message, $context );
		}

		/**
		 * Logs messages to file and error_log if WP_DEBUG
		 *
		 * @param string $message Message to log
		 * @param string $context Optional context (e.g. a class or function name)
		 */
		public function log( $message, $context = '' ) {
			$log_message = $this->format_message( $message, $context );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $log_message );
			}

			if ( ! $this->is_logging_enabled() ) {
				return;
			}

			$log_file = 'wc-services';

			if ( ! empty( $this->feature ) ) {
				$log_file .= '-' . $this->feature;
			}

			$this->logger->add( $log_file, $log_message );


		}

	}

}
