<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
    define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'https://api.woocommerce.com/' );
}

if ( ! class_exists( 'WC_Connect_API_Client' ) ) {

    class WC_Connect_API_Client {

        /**
         * Requests the available services for this site from the WooCommerce Connect Server
         *
         * @return array|WP_Error
         */
        public static function get_services() {
            return self::request( 'GET', '/services' );
        }


        /**
         * Validates the settings for a given service with the WooCommerce Connect Server
         *
         * @param $service_slug
         * @param $service_settings
         *
         * @return bool|WP_Error
         */
        public static function validate_service_settings( $service_slug, $service_settings ) {
            if ( 1 === preg_match( "/[^a-z_-]*/gi", $service_slug ) ) {
                return new WP_Error( 'invalid_service_slug', 'Invalid WooCommerce Connect service slug provided' );
            }

            return self::request( 'POST', "/services/{$service_slug}/settings", $service_settings );
        }


        /**
         * Gets the shipping rates from the WooCommerce Connect Server
         *
         * @param $settings All settings for all services we want rates for
         * @param $package Package provided to WC_Shipping_Method::calculate_shipping()
         * @return object|WP_Error
         */
        public static function get_shipping_rates( $settings, $package ) {
            $body = array(
                'fields' => $settings,
                'destination' => $package->destination,
                'contents' => array(
                    // TODO - extract and format package contents
                )
            );

            return self::request( 'GET', '/shipping/rates', $body );
        }


        /**
         * Tests the connection to the WooCommerce Connect Server
         *
         * @return true|WP_Error
         */
        public static function auth_test() {
            return self::request( 'GET', '/auth-test' );
        }


        /**
         * Sends a request to the WooCommerce Connect Server via Jetpack
         *
         * @param $method
         * @param $path
         * @param $body
         * @return mixed|WP_Error
         */
        protected static function request( $method, $path, $body = array() ) {

            if ( ! class_exists( 'Jetpack_client' ) ) {
                return new WP_Error( 'jetpack_client_not_found', 'Unable to send request to WooCommerce Connect server. Jetpack client was not found.' );
            }

            if ( ! is_array( $body ) ) {
                return new WP_Error( 'request_body_should_be_array', 'Unable to send request to WooCommerce Connect server. Body must be an array.' );
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
            ) );

            $body = apply_filters( 'wc_connect_api_client_body', $body );

            add_filter( 'http_request_args', array( 'WC_Connect_API_Client', 'filter_http_request_args' ), 10, 2 );
            $body = wp_json_encode( $body );
            if ( $body ) {
                $result = Jetpack_client::remote_request( $args, $body );
            }
            remove_filter( 'http_request_args', array( 'WC_Connect_API_Client', 'filter_http_request_args' ) );

            if ( ! $body ) {
                return new WP_Error( 'unable_to_json_encode_body', 'Unable to encode body for request to WooCommerce Connect server.' );
            }

            return $result;
        }


        /**
         * Adds language to the header
         *
         * @param $request_args array
         * @param $url string
         * @return array
         */
        public static function filter_http_request_args( $request_args, $url ) {

            if ( ! array_key_exists( 'headers', $request_args ) ) {
                $request_args['headers'] = array();
            }

            $request_args['headers']['Accept-Language'] = substr( get_locale(), 0, 2 );
            $request_args['headers']['Accept'] = 'application/vnd.woocommerce-connect.v1';

            return $request_args;
        }
    }

}
