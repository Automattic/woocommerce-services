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
         * @param $services All settings for all services we want rates for
         * @param $package Package provided to WC_Shipping_Method::calculate_shipping()
         * @return object|WP_Error
         */
        public static function get_shipping_rates( $services, $package ) {

            // First, build the contents array
            // each item needs to specify quantity, weight, length, width and height
            $contents = array();
            foreach ( $package['contents'] as $item_id => $values ) {

                $product_id = absint( $values['data']->id );
                $product = wc_get_product( $product_id );

                if ( $values['quantity'] > 0 && $product->needs_shipping() ) {

                    if ( ! $product->has_weight() ) {
                        return new WP_Error(
                            'product_missing_weight',
                            sprintf( "Product ( ID: %d ) did not include a weight. Shipping rates cannot be calculated.", $product_id )
                        );
                    }

                    $height = 0;
                    $length = 0;
                    $weight = $product->get_weight();
                    $width = 0;

                    if ( $product->has_dimensions() ) {
                        $height = $product->get_height();
                        $length = $product->get_length();
                        $width  = $product->get_width();
                    }

                    $contents[] = array(
                        'height' => $height,
                        'item_id' => $values['data']->id,
                        'length' => $length,
                        'quantity' => $values['quantity'],
                        'weight' => $weight,
                        'width' => $width
                    );
                }
            }

            if ( empty( $contents ) ) {
                return new WP_Error( 'nothing_to_ship', 'No shipping rate could be calculated. No items in the package are shippable.' );
            }

            // Then, make the request
            $body = array(
                'contents' => $contents,
                'destination' => $package['destination'],
                'services' => $services
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

            // TODO - incorporate caching for repeated identical requests

            if ( ! class_exists( 'Jetpack_client' ) ) {
                return new WP_Error( 'jetpack_client_class_not_found', 'Unable to send request to WooCommerce Connect server. Jetpack client was not found.' );
            }

            if ( ! method_exists( 'Jetpack_client', 'remote_request' ) ) {
                return new WP_Error( 'jetpack_client_remote_request_not_found', 'Unable to send request to WooCommerce Connect server. Jetpack client does not implement remote_request.' );
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
                'base_city' => WC()->countries->get_base_city(),
                'base_country' => WC()->countries->get_base_country(),
                'base_postcode' => WC()->countries->get_base_postcode(),
                'base_state' => WC()->countries->get_base_state(),
                'currency' => get_woocommerce_currency(),
                'dimension_unit' => strtolower( get_option( 'woocommerce_dimension_unit' ) ),
                'jetpack_version' => JETPACK__VERSION,
                'wc_version' => WC()->version,
                'weight_unit' => strtolower( get_option('woocommerce_weight_unit' ) ),
                'wp_version' => get_bloginfo( 'version' )
            ) );

            $body = wp_json_encode( apply_filters( 'wc_connect_api_client_body', $body ) );
            if ( ! $body ) {
                return new WP_Error( 'unable_to_json_encode_body', 'Unable to encode body for request to WooCommerce Connect server.' );
            }

            add_filter( 'http_request_args', array( 'WC_Connect_API_Client', 'filter_http_request_args' ), 10, 2 );
            $result = Jetpack_client::remote_request( $args, $body );
            remove_filter( 'http_request_args', array( 'WC_Connect_API_Client', 'filter_http_request_args' ) );

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

            $lang = strtolower( str_replace( '_', '-', get_locale() ) );
            $request_args['headers']['Accept-Language'] = $lang;
            $request_args['headers']['Accept'] = 'application/vnd.woocommerce-connect.v1';

            return $request_args;
        }
    }

}
