<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'WC_Connect_Debug_Tools' ) ) {

    class WC_Connect_Debug_Tools {

        function __construct( WC_Connect_API_Client $api_client ) {
            $this->api_client = $api_client;

            add_filter( 'woocommerce_debug_tools', array( $this, 'woocommerce_debug_tools' ) );
        }

        function woocommerce_debug_tools( $tools ) {
            $tools['test_wcc_connection'] = array(
                'name'    => __( 'Test your Connect for WooCommerce connection', 'connectforwoocommerce' ),
                'button'  => __( 'Test Connection', 'connectforwoocommerce' ),
                'desc'    => __( 'This will test your Connect for WooCommerce connection to ensure everything is working correctly', 'connectforwoocommerce' ),
                'callback' => array( $this, 'test_connection' ),
            );
            return $tools;
        }

        function test_connection() {
            $test_request = $this->api_client->auth_test();
            if ( $test_request && ! is_wp_error( $test_request ) && $test_request->authorized ) {
                echo '<div class="updated inline"><p>' . __( 'Your site is succesfully communicating to the Connect for WooCommerce API.', 'connectforwoocommerce' ) . '</p></div>';
            } else {
                echo '<div class="error inline"><p>' . __( 'ERROR: Your site has a problem connecting to the Connect for WooCommerce API. Please make sure your Jetpack connection is working.', 'connectforwoocommerce' ) . '</p></div>';
            }
        }

    }
}
