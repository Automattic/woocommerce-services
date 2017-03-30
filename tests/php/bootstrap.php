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
if ( false !== getenv( 'WC_DEVELOP_DIR' ) ) {
	$wc_root = getenv( 'WC_DEVELOP_DIR' );
} else if ( file_exists( '/tmp/woocommerce/tests/bootstrap.php' ) ) {
	$wc_root = '/tmp/woocommerce/tests';
} else {
	exit( 'Could not determine test root directory. Aborting.' );
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ? getenv( 'WP_TESTS_DIR' ) : '/tmp/wordpress-tests-lib';

// load test function so tests_add_filter() is available
require_once( $wp_tests_dir . '/includes/functions.php' );

// Activates this plugin in WordPress so it can be tested.
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../../woocommerce-services.php';
}
tests_add_filter( 'plugins_loaded', '_manually_load_plugin' );

if ( ! defined( 'WC_UNIT_TESTING' ) ) {
	define( 'WC_UNIT_TESTING', true );
}

require $wc_root . '/bootstrap.php';
