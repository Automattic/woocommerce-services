<?php
/**
 * Exception raised in place of the redirect that ends a successful notice dismissal.
 *
 * @package WC_Connect
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCS_Test_Error_Notice_Redirect' ) ) {

	/**
	 * WC_Connect_Error_Notice::render_notice() calls exit immediately after redirecting.
	 * Left alone that ends the PHPUnit process with status 0, so a dismissal that got
	 * through the guard would end the run green instead of failing it. The test hooks
	 * wp_redirect and throws this instead, which unwinds before the exit is reached.
	 */
	class WCS_Test_Error_Notice_Redirect extends Exception {}
}
