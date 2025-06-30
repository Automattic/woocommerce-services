<?php
/**
 * WooCommerceBlocks Integration class.
 *
 * @package Automattic\WCServices
 */

namespace Automattic\WCServices\Integrations;

use Automattic\WCServices\StoreApi\StoreApiExtendSchema;
use Automattic\WCServices\Utils;
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerceBlocks Integration class.
 */
class WooCommerceBlocksIntegration implements IntegrationInterface {

	/**
	 * The base URL to use for loading assets.
	 *
	 * @var string
	 */
	private string $base_url;

	public function __construct( string $wc_connect_base_url ) {
		$this->base_url = $wc_connect_base_url;
	}

	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return StoreApiExtendSchema::IDENTIFIER;
	}

	/**
	 * When called invokes any initialization/setup for the integratidon.
	 */
	public function initialize() {
		$this->register_scripts();
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles(): array {
		$script_handles = array();

		$script_handles[] = 'woocommerce-services-store-notices';

		return $script_handles;
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles(): array {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data(): array {
		return array();
	}

	/**
	 * Registers the scripts and styles for the integration.
	 */
	public function register_scripts() {
		foreach ( $this->get_script_handles() as $handle ) {
			$this->register_script( $handle );
		}
	}

	/**
	 * Register a script for the integration.
	 *
	 * @param string $handle Script handle.
	 */
	protected function register_script( string $handle ) {
		$plugin_version      = Utils::get_wcservices_version();
		$script_name         = "$handle-$plugin_version.js";
		$script_path         = $this->base_url . $script_name;
		$script_url          = Utils::get_enqueue_base_url() . $script_name;
		$script_asset_path   = $this->base_url . $handle . '.asset.php';
		$script_asset        = file_exists( $script_asset_path )
			? require $script_asset_path : array();  // nosemgrep: audit.php.lang.security.file.inclusion-arg --- This is a safe file inclusion.
		$script_dependencies = $script_asset['dependencies'] ?? array();

		wp_register_script(
			$handle,
			$script_url,
			$script_dependencies,
			null,
			true
		);
	}
}
