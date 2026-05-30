<?php
/**
 * Live implementation of the WooCommerce Connect API client.
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
	define( 'WOOCOMMERCE_CONNECT_SERVER_URL', 'https://api.woocommerce.com/' );
}

if ( ! class_exists( 'WC_Connect_API_Client_Live' ) ) {
	require_once plugin_basename( 'class-wc-connect-api-client.php' );

	/**
	 * Sends live HTTP requests to the WooCommerce Connect server.
	 */
	class WC_Connect_API_Client_Live extends WC_Connect_API_Client {

		/**
		 * Send a request to the WooCommerce Connect server.
		 *
		 * @param string $method The HTTP method to use.
		 * @param string $path   The request path relative to the server URL.
		 * @param array  $body   Optional. The request body.
		 * @return mixed|WP_Error The decoded response body, the raw response, or a WP_Error on failure.
		 */
		protected function request( $method, $path, $body = array() ) {

			// TODO - incorporate caching for repeated identical requests.
			if ( ! is_array( $body ) ) {
				return new WP_Error(
					'request_body_should_be_array',
					__( 'Unable to send request to WooCommerce Tax server. Body must be an array.', 'woocommerce-services' )
				);
			}

			$url = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL );
			/**
			 * Filters the WooCommerce Connect server URL.
			 *
			 * @since 3.6.3
			 *
			 * @param string $url The WooCommerce Connect server URL.
			 */
			$url = apply_filters( 'wc_connect_server_url', $url );
			$url = trailingslashit( $url ) . ltrim( $path, '/' );

			// Add useful system information to requests that contain bodies.
			if ( in_array( $method, array( 'POST', 'PUT' ) ) ) {
				$body = $this->request_body( $body );
				/**
				 * Filters the request body sent to the WooCommerce Connect server.
				 *
				 * @since 3.6.3
				 *
				 * @param array $body The request body.
				 */
				$body = wp_json_encode( apply_filters( 'wc_connect_api_client_body', $body ) );

				if ( ! $body ) {
					return new WP_Error(
						'unable_to_json_encode_body',
						__( 'Unable to encode body for request to WooCommerce Tax server.', 'woocommerce-services' )
					);
				}
			}

			$headers = $this->request_headers();
			if ( is_wp_error( $headers ) ) {
				return $headers;
			}

			$http_timeout = 60;
			// 1 minute
			if ( function_exists( 'wc_set_time_limit' ) ) {
				wc_set_time_limit( $http_timeout + 10 );
			}
			$args = array(
				'headers'     => $headers,
				'method'      => $method,
				'body'        => $body,
				'redirection' => 0,
				'compress'    => true,
				'timeout'     => $http_timeout,
			);
			/**
			 * Filters the arguments passed to wp_remote_request().
			 *
			 * @since 3.6.3
			 *
			 * @param array $args The request arguments.
			 */
			$args = apply_filters( 'wc_connect_request_args', $args );

			$response      = wp_remote_request( $url, $args );
			$response_code = wp_remote_retrieve_response_code( $response );

			// If the received response is not JSON, return the raw response.
			$content_type = wp_remote_retrieve_header( $response, 'content-type' );
			if ( false === strpos( $content_type, 'application/json' ) ) {
				if ( 200 != $response_code ) {
					return new WP_Error(
						'wcc_server_error',
						sprintf(
							/* translators: %d: HTTP response code */
							__( 'Error: The WooCommerce Tax server returned HTTP code: %d', 'woocommerce-services' ),
							$response_code
						),
						array(
							'response_status_code' => $response_code,
						)
					);
				}
				return $response;
			}

			$response_body = wp_remote_retrieve_body( $response );
			if ( ! empty( $response_body ) ) {
				$response_body = json_decode( $response_body );
			}

			if ( 200 != $response_code ) {
				if ( empty( $response_body ) ) {
					return new WP_Error(
						'wcc_server_empty_response',
						sprintf(
							/* translators: %d: HTTP response code */
							__( 'Error: The WooCommerce Tax server returned ( %d ) and an empty response body.', 'woocommerce-services' ),
							$response_code
						),
						array(
							'response_status_code' => $response_code,
						)
					);
				}

				$error   = property_exists( $response_body, 'error' ) ? $response_body->error : '';
				$message = property_exists( $response_body, 'message' ) ? $response_body->message : '';
				$data    = property_exists( $response_body, 'data' ) ? (array) $response_body->data : array();

				$data['response_status_code'] = $response_code;

				return new WP_Error(
					'wcc_server_error_response',
					sprintf(
						/* translators: %1$s: error code, %2$s: error message, %3$d: HTTP response code */
						__( 'Error: The WooCommerce Tax server returned: %1$s %2$s ( %3$d )', 'woocommerce-services' ),
						$error,
						$message,
						$response_code
					),
					$data
				);
			}//end if

			return $response_body;
		}
	}
}//end if
