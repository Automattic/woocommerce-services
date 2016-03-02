<?php

if ( ! class_exists( 'WC_Connect_Logger' ) ) {

    class WC_Connect_Logger {
        /**
         * @var WC_Logger
         */
        protected $logger = null;

        /**
         * @var Singleton The reference the *Singleton* instance of this class
         */
        private static $instance;

        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return Singleton The *Singleton* instance.
         */
        public static function getInstance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         * @return void
         */
        private function __clone() {
        }

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         *
         * @return void
         */
        private function __wakeup() {
        }

        /**
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        protected function __construct() {
        }

        /**
         * Initialize the logger as needed
         *
         */
        protected function init() {
            if ( empty( $this->logger ) ) {
                $this->logger = new WC_Logger();
            }
        }

        /**
         * Logs messages
         *
         * @param string $message Message to log
         * @param string $context Optional context (e.g. a class or function name)
         */
        public function log( $message, $context = '' ) {
            // TODO add a debug control somewhere to turn logging on and off

            $this->init();

            if ( is_wp_error( $message ) ) {
                $message = $message->get_error_code() . ' ' . $message->get_error_message();
            }

            if ( ! empty( $context ) ) {
                $message .= ' ' . $context;
            }

            $this->logger->add( 'wc-connect', $message );

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( $message );
            }
        }

        /**
         * Clears the log
         */
        public function clear() {
            $this->init();
            $this->logger->clear( 'wc-connect' );
        }

    }

}