<?php
/**
 * Exception raised in place of the redirect that ends a verified NUX banner action.
 *
 * @package WC_Connect
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCS_Test_Nux_Redirect' ) ) {

	/**
	 * WC_Connect_Nux's banner handlers call exit immediately after redirecting. Left
	 * alone that ends the PHPUnit process with status 0, so an action that got through
	 * the nonce check would end the run green instead of failing it. The tests hook
	 * wp_redirect and throw this instead, which unwinds before the exit is reached.
	 */
	class WCS_Test_Nux_Redirect extends Exception {}
}
