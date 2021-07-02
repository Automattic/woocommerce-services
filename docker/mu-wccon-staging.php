<?php

if ( defined( 'WOOCOMMERCE_COM_STAGING' ) && WOOCOMMERCE_COM_STAGING ) {
	// overwrites the woocommerce.com helper API base URL
	add_filter( 'woocommerce_helper_api_base', function () {
		return 'https://staging.woocommerce.com/wp-json/helper/1.0';
	} );

	add_filter( 'pre_http_send_through_proxy', function ( $default, $destination_url ) {
		// requests made to the staging server need to go through the proxy
		if ( strpos( $destination_url, 'staging.woocommerce.com' ) !== false ) {
			return true;
		}

		// requests made to the connect server do not need to go through the proxy
		if ( strpos( $destination_url, 'host.docker.internal' ) !== false ) {
			return false;
		}

		return $default;
	}, 10, 2 );

	add_action( 'http_api_curl', function ( $handle, $request, $destination_url ) {
		// requests made to the connect server do not need the CURL options
		if ( strpos( $destination_url, 'host.docker.internal' ) !== false ) {
			return;
		}

		curl_setopt( $handle, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5 );
	}, 10, 3 );

	// proxies the request through autoproxxy
	define( 'WP_PROXY_HOST', 'host.docker.internal' );
	define( 'WP_PROXY_PORT', '8080' );
}
