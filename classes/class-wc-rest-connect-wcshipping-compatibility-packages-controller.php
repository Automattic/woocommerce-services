<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_WCShipping_Compatibility_Packages_Controller' ) ) {
	return;
}

require_once __DIR__ . '/class-wc-rest-connect-packages-controller.php';
require_once __DIR__ . '/class-wc-rest-connect-wcshipping-compatibility-packages-controller.php';

/**
 * REST controller using WCS&T's settings store instead of WCShipping's.
 */
class WC_REST_Connect_WCShipping_Compatibility_Packages_Controller extends WC_REST_Connect_Packages_Controller {
	protected $rest_base = 'connect/wcservices/packages';
}
