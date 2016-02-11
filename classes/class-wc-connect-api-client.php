<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
    define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'https://wooconnect.woocommerce.com/' );
}

if ( ! class_exists( 'WC_Connect_API_Client' ) ) {

    class WC_Connect_API_Client {

        /**
         * @var static The reference the *Singleton* instance of this class
         */
        private static $instance;

        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return static The *Singleton* instance.
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
         * Private un-serialize method to prevent un-serializing of the *Singleton*
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
         * Requests the available services for this site from the WooCommerce Connect Server
         *
         * @return array|WP_Error
         */
        public function get_services() {
            return do_request( 'GET', '/services' );
        }


        /**
         * Validates the settings for a given service with the WooCommerce Connect Server
         *
         * @param $service_slug
         * @param $service_settings
         *
         * @return bool|WP_Error
         */
        public function validate_service_settings( $service_slug, $service_settings ) {
            if ( 1 === preg_match( "/[^a-z_-]*/gi", $service_slug ) ) {
                return new WP_Error( 'error', __( 'Invalid WooCommerce Connect service slug provided' ) );
            }

            return do_request( 'POST', "/services/{$service_slug}/settings", $service_settings );
        }


        /**
         * Gets the shipping rates from the WooCommerce Connect Server
         *
         * @param $settings All settings for all services we want rates for
         * @param $package Package provided to WC_Shipping_Method::calculate_shipping()
         * @return object|WP_Error
         */
        public function get_shipping_rates( $settings, $package ) {
            $body = array(
                'fields' => $settings,
                'destination' => $package->destination,
                'contents' => array(
                    // TODO - extract and format package contents
                )
            );

            return do_request( 'GET', '/shipping/rates', $body );
        }


        /**
         * Tests the connection to the WooCommerce Connect Server
         *
         * @return true|WP_Error
         */
        public function test_connection() {
            return do_request( 'GET', '/connection-test' );
        }


        /**
         * Sends a request to the WooCommerce Connect Server via Jetpack
         *
         * @param $method
         * @param $path
         * @param $body
         * @return mixed|WP_Error
         */
        public function do_request( $method, $path, $body = array() ) {

            if ( ! class_exists( 'Jetpack_client' ) ) {
                return new WP_Error( 'error', __( 'Unable to send request to WooCommerce Connect server. Jetpack client was not found.', '' ) );
            }

            if ( ! is_array( $body ) ) {
                return new WP_Error( 'error', __( 'Unable to send request to WooCommerce Connect server. Body must be an array.', '' ) );
            }

            $url = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL ) . $path;

            $args = array(
                'url' => $url,
                'method' => $method
            );

            // Add interesting fields to the body of each request
            if ( ! array_key_exists( 'settings', $body ) ) {
                $body['settings'] = array();
            }
            $body['settings'] = wp_parse_args( $body['settings'], array(
                'currency' => get_woocommerce_currency(),
                'weight' => strtolower( get_option('woocommerce_weight_unit') ),
                'distance' => strtolower( get_option( 'woocommerce_dimension_unit' ) ),
                'wp_version' => get_bloginfo( 'version' ),
                'wc_version' => WC()->version,
                'jetpack_version' => JETPACK__VERSION
            );

            add_filter( 'http_request_args', array( $this, 'filter_http_request_args' ), 10, 2 );
            Jetpack_client::remote_request( $args, $body );
            remove_filter( 'http_request_args', array( $this, 'filter_http_request_args' ) );

        }


        /**
         * Adds language to the header
         *
         * @param $request_args array
         * @param $url string
         * @return array
         */
        public function filter_http_request_args( $request_args, $url ) {
            // TODO add Accept-Language: language
            // TODO add Accept: application/vnd.woocommerce-connect.v1
            return $request_args;
        }
    }

}
