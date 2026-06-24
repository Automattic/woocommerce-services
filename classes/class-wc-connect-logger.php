<?php

if ( ! class_exists( 'WC_Connect_Logger' ) ) {

	/**
	 * Handles logging and debug output for WooCommerce Services.
	 */
	class WC_Connect_Logger {

		/**
		 * The WooCommerce logger instance.
		 *
		 * @var WC_Logger
		 */
		private $logger;

		/**
		 * Whether logging to file is enabled.
		 *
		 * @var bool
		 */
		private $is_logging_enabled = false;

		/**
		 * Whether debug notices are enabled.
		 *
		 * @var bool
		 */
		private $is_debug_enabled = false;

		/**
		 * Optional feature name used to namespace the log file.
		 *
		 * @var string
		 */
		private $feature;

		/**
		 * Constructor.
		 *
		 * @param WC_Logger $logger  The WooCommerce logger instance.
		 * @param string    $feature Optional. Feature name used to namespace the log file.
		 */
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

		/**
		 * Enable logging to file.
		 *
		 * @return void
		 */
		public function enable_logging() {
			WC_Connect_Options::update_option( 'debug_logging_enabled', true );
			$this->is_logging_enabled = true;
			$this->log( 'Logging enabled' );
		}

		/**
		 * Disable logging to file.
		 *
		 * @return void
		 */
		public function disable_logging() {
			$this->log( 'Logging disabled' );
			WC_Connect_Options::update_option( 'debug_logging_enabled', false );
			$this->is_logging_enabled = false;
		}

		/**
		 * Enable debug notices.
		 *
		 * @return void
		 */
		public function enable_debug() {
			WC_Connect_Options::update_option( 'debug_display_enabled', true );
			$this->is_debug_enabled = true;
			$this->log( 'Debug enabled' );
		}

		/**
		 * Disable debug notices.
		 *
		 * @return void
		 */
		public function disable_debug() {
			$this->log( 'Debug disabled' );
			WC_Connect_Options::update_option( 'debug_display_enabled', false );
			$this->is_debug_enabled = false;
		}

		/**
		 * Check whether debug notices are enabled.
		 *
		 * @return bool True when debug notices are enabled.
		 */
		public function is_debug_enabled() {
			return $this->is_debug_enabled;
		}

		/**
		 * Check whether logging to file is enabled.
		 *
		 * @return bool True when logging to file is enabled.
		 */
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
			if ( $this->is_debug_enabled() && ! wc_has_notice( $message, $type ) ) {
				wc_add_notice( $message, $type );
			}
		}

		/**
		 * Logs messages even if debugging is disabled.
		 *
		 * @param string $message Message to log.
		 * @param string $context Optional context (e.g. a class or function name).
		 */
		public function error( $message, $context = '' ) {
			WC_Connect_Error_Notice::instance()->enable_notice( $message );
			$this->log( $message, $context, true );
		}

		/**
		 * Logs messages to file and error_log if WP_DEBUG.
		 *
		 * @param WP_Error|string $message Message to log.
		 * @param string          $context Optional context (e.g. a class or function name).
		 * @param bool            $force   Optional. Whether to log even when logging is disabled.
		 */
		public function log( $message, $context = '', $force = false ) {
			$log_message = $this->format_message( $message, $context );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore QITStandard.PHP.DebugCode.DebugFunctionFound -- Intended debug logging when WP_DEBUG is enabled.
				error_log( $log_message );
			}

			if ( ! $this->is_logging_enabled() && ! $force ) {
				return;
			}

			$log_file = 'wc-services';

			if ( ! empty( $this->feature ) ) {
				$log_file .= '-' . $this->feature;
			}

			$this->logger->add( $log_file, $log_message );
		}
	}

}//end if
