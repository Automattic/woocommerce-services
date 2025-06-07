<?php
/**
 * General Automattic\WCServices utils.
 *
 * Provides utility functions useful for multiple parts of WCServices.
 *
 * @package Automattic\WCServices
 */

namespace Automattic\WCServices;

/**
 * Automattic\WCServices utils class.
 */
class Utils {
	/**
	 * Get WooCommerce Services plugin version.
	 *
	 * @return string
	 */
	public static function get_wcshipping_version() {
		if ( defined( 'WCSHIPPING_VERSION' ) ) {
			return WCSHIPPING_VERSION;
		}
		// Fallback to reading the version from the plugin file.
		$plugin_data = get_file_data( WCSHIPPING_PLUGIN_FILE, array( 'Version' => 'Version' ) );
		return $plugin_data['Version'];
	}

	/**
	 * Get the base URL for enqueuing assets.
	 *
	 * @return string
	 */
	public static function get_enqueue_base_url(): string {
		return trailingslashit( defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_DEV_SERVER_URL : WCSHIPPING_PLUGIN_DIST_URL );
	}

	/**
	 * Get the plugin directory path.
	 * This is a helper function to get the plugin directory path for either the main plugin or the WooCommerce plugin.
	 *
	 * @param bool $for_woocommerce Whether to get the path for the WooCommerce plugin.
	 * @return string The plugin directory path.
	 */
	public static function get_plugin_path( $for_woocommerce = false ) {
		return $for_woocommerce ? plugin_dir_path( WC_PLUGIN_FILE ) : plugin_dir_path( WCSHIPPING_PLUGIN_FILE );
	}

	/**
	 * Get the relative path to the plugin directory.
	 * This is a helper function to get the relative path to the plugin directory for either the main plugin or the WooCommerce plugin.
	 *
	 * @param bool $for_woocommerce Whether to get the path for the WooCommerce plugin.
	 * @return string The relative path to the plugin directory.
	 */
	public static function get_relative_plugin_path( $for_woocommerce = false ) {
		// Full path to the plugins directory using plugin_dir_path.
		$plugin_full_path = self::get_plugin_path( $for_woocommerce );

		// Normalize paths to avoid issues with different directory separators.
		$plugin_full_path = wp_normalize_path( $plugin_full_path );
		$root_path        = wp_normalize_path( ABSPATH );

		// Remove the root path part, leaving only the relative path in place.
		$relative_path = str_replace( $root_path, '', $plugin_full_path );

		return $relative_path;
	}

	/**
	 * Get constants that are useful for JavaScript.
	 *
	 * @return array
	 */
	public static function get_constants_for_js() {
		return array(
			'WCSHIPPING_PLUGIN_FILE'         => WCSHIPPING_PLUGIN_FILE,
			'WCSHIPPING_PLUGIN_DIR'          => self::get_plugin_path(),
			'WCSHIPPING_RELATIVE_PLUGIN_DIR' => self::get_relative_plugin_path(),
			'WC_PLUGIN_RELATIVE_DIR'         => self::get_relative_plugin_path( true ),
		);
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 *
	 * @return string The cache buster value to use for the given file.
	 */
	public static function get_file_version( string $file ): string {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return (string) filemtime( $file );
		}

		return self::get_wcshipping_version();
	}
}
