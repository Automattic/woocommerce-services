<?php

use Automattic\WCServices\Integrations\WooCommerceBlocksIntegration;

/**
 * Test double that points asset file lookups at a temp directory,
 * so tests never touch the plugin's real dist/ directory.
 */
class WCS_Test_Blocks_Integration extends WooCommerceBlocksIntegration {

	/**
	 * Directory the asset file lookup should use.
	 *
	 * @var string
	 */
	public $asset_dir;

	/**
	 * Get the filesystem path of a script's asset file.
	 *
	 * @param string $handle Script handle.
	 *
	 * @return string
	 */
	protected function get_script_asset_path( string $handle ): string {
		return $this->asset_dir . $handle . '.asset.php';
	}
}
