<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_WCShipping_Compatibility_Packages_Controller' ) ) {
	return;
}

class WC_REST_Connect_WCShipping_Compatibility_Packages_Controller extends WC_REST_Connect_Packages_Controller {
	protected $rest_base = 'connect/wcshipping-compatibility-packages';
}
