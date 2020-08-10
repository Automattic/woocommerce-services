<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * Edit 'active_plugins' setting below to point to your main plugin file.
 *
 * @package wordpress-plugin-tests
 */

// Support for:
// 1. `WC_DEVELOP_DIR` environment variable
// 2. Tests checked out to /tmp
if( false !== getenv( 'WC_DEVELOP_DIR' ) ) {
	$wc_root = getenv( 'WC_DEVELOP_DIR' );
} else if ( file_exists( '/tmp/woocommerce/tests/bootstrap.php' ) ) {
	$wc_root = '/tmp/woocommerce/tests';
} else {
	exit( 'Could not determine test root directory. Aborting.' );
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wp_tests_dir ) {
	$wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $wp_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// load test function so tests_add_filter() is available
require_once( $wp_tests_dir . '/includes/functions.php' );

// Activates this plugin in WordPress so it can be tested.
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../woocommerce-services.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

if ( ! defined( 'WC_UNIT_TESTING' ) ) {
	define( 'WC_UNIT_TESTING', true );
}

require $wc_root . '/bootstrap.php';
