<?php

if ( ! class_exists( 'WC_Connect_Logger' ) ) {

    class WC_Connect_Logger {

        /**
         * @var WC_Logger
         */
        private static $logger = null;

        /**
         * Retrieve the logger, initialize as needed
         */
        protected static function get_logger() {

            if ( is_null( self::$logger ) ) {
                self::$logger = new WC_Logger();
            }

            return self::$logger;
        }

        /**
         * Format a message with optional context for logging.
         *
         * @param string|WP_Error $message Either a string message, or WP_Error object.
         * @param string $context Optional. Context for the logged message.
         * @return string The formatted log message.
         */
        protected static function format_message( $message, $context = '' ) {

            $formatted_message = '';

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
        public static function log( $message, $context = '' ) {
            // TODO add a debug control somewhere to turn logging on and off

            $log_message = self::format_message( $message, $context );

            self::get_logger()->add( 'wc-connect', $log_message );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( $log_message );
            }

        }

    }

}