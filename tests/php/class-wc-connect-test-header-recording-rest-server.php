<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST server that records headers instead of sending them. The base controller
 * sends a no-cache header on every dispatch, which PHPUnit would report as
 * "headers already sent".
 */
class WC_Connect_Test_Header_Recording_REST_Server extends WP_Test_Spy_REST_Server {

	/** @var array $sent_headers */
	public $sent_headers = array();

	/**
	 * Record a header instead of sending it.
	 *
	 * @param string $key   Header name.
	 * @param string $value Header value.
	 */
	public function send_header( $key, $value ) {
		$this->sent_headers[ $key ] = $value;
	}
}
