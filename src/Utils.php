<?php
/**
 * General Automattic\WCServices utils.
 *
 * Provides utility functions useful for multiple parts of WCServices.
 *
 * @package Automattic\WCServices
 */

namespace Automattic\WCServices;

use WC_Connect_Loader;

/**
 * Automattic\WCServices utils class.
 */
class Utils {
	/**
	 * Get WooCommerce Services plugin version.
	 *
	 * @return string
	 */
	public static function get_wcservices_version() {
		return WC_Connect_Loader::get_wcs_version();
	}

	/**
	 * Get the base URL for enqueuing assets.
	 *
	 * @return string
	 */
	public static function get_enqueue_base_url(): string {
		return trailingslashit( defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_DEV_SERVER_URL : WCSERVICES_PLUGIN_DIST_URL );
	}

	/**
	 * Get the plugin directory path.
	 * This is a helper function to get the plugin directory path for either the main plugin or the WooCommerce plugin.
	 *
	 * @param bool $for_woocommerce Whether to get the path for the WooCommerce plugin.
	 * @return string The plugin directory path.
	 */
	public static function get_plugin_path( $for_woocommerce = false ) {
		return $for_woocommerce ? plugin_dir_path( WC_PLUGIN_FILE ) : plugin_dir_path( WCSERVICES_PLUGIN_FILE );
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

		return self::get_wcservices_version();
	}

	/**
	 * Get sanitized request data.
	 *
	 * @param string $key     The key to get the data for.
	 * @param string $default The default value to return if the key is not set.
	 * @return string The sanitized data as a string.
	 */
	public static function get_sanitized_request_data( string $key, string $default = '' ): string {
		return sanitize_text_field( wp_unslash( $_REQUEST[ $key ] ?? $default ) );
	}
}
