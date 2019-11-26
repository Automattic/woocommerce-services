<?php
/*
Plugin Name: wc-services-testing-helper
Plugin URI: http://
Description: Helps testing wc-services
Author: Extendables
Version: 1.0
Author URI: http://
*/

define( 'JETPACK_DEV_DEBUG', true );
define( 'WOOCOMMERCE_SERVICES_LOCAL_TEST_MODE', true );
define( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH', true );
define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'http://localhost:5000/' );

function wc_test_connect_jetpack_access_fake_token( $token ) {
        $token = new stdClass();
        $token->secret = "askjdflasjdlfkajsldfkjaslkdf.asdkfjlaskjdflajskd";
        $token->external_user_id = 0;
        return $token;
}

add_filter( 'wc_connect_jetpack_access_token', 'wc_test_connect_jetpack_access_fake_token' );
