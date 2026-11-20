<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * Edit 'active_plugins' setting below to point to your main plugin file.
 *
 * @package wordpress-plugin-tests
 */

/**
 * Support for:
 * 1. `WC_DEVELOP_DIR` environment variable.
 * 2. WooCommerce checked out under the system temp dir (honors `TMPDIR` so the
 *    fallback matches the namespaced path tests/bin/install-wc-tests.sh writes
 *    to — see tests/bin/test-config.sh).
 */
$wc_tmp_root = rtrim( sys_get_temp_dir(), '/\\' ) . '/woocommerce/plugins/woocommerce';
if ( false !== getenv( 'WC_DEVELOP_DIR' ) ) {
	$wc_root = getenv( 'WC_DEVELOP_DIR' );
} elseif ( file_exists( $wc_tmp_root . '/tests/legacy/bootstrap.php' ) ) {
	$wc_root = $wc_tmp_root;
} else {
	exit( 'Could not determine test root directory. Aborting. Have you run bin/install-wc-tests.sh?' );
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $wp_tests_dir ) {
	$wp_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $wp_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Use this plugin's own wp-tests-config.php, written by tests/bin/run-tests.sh
// with this plugin's DB_HOST. The copy in the shared WP test library is
// rewritten by other plugins' setups, so a run that read it could connect to,
// and wipe, another plugin's test database.
if ( ! defined( 'WP_TESTS_CONFIG_FILE_PATH' ) ) {
	$plugin_wp_tests_config = getenv( 'WP_TESTS_CONFIG_FILE_PATH' );
	if ( $plugin_wp_tests_config && ! is_readable( $plugin_wp_tests_config ) ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI bootstrap error before WP loads; WP_Filesystem unavailable.
		fwrite( STDERR, "WP_TESTS_CONFIG_FILE_PATH points to {$plugin_wp_tests_config}, which cannot be read. Run composer test." . PHP_EOL );
		exit( 1 );
	}
	if ( ! $plugin_wp_tests_config ) {
		// vendor/bin/phpunit run by hand: use the file the last `composer test` wrote.
		$plugin_wp_tests_config = dirname( __DIR__ ) . '/.wp-tests-config.php';
	}
	if ( is_readable( $plugin_wp_tests_config ) ) {
		define( 'WP_TESTS_CONFIG_FILE_PATH', $plugin_wp_tests_config );
	}
	unset( $plugin_wp_tests_config );
}

// load test function so tests_add_filter() is available.
require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Activates this plugin in WordPress so it can be tested.
 *
 * @return void
 */
function _manually_load_plugin() {
	require __DIR__ . '/../../woocommerce-services.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

if ( ! defined( 'WC_UNIT_TESTING' ) ) {
	define( 'WC_UNIT_TESTING', true );
}

require $wc_root . '/tests/legacy/bootstrap.php';
