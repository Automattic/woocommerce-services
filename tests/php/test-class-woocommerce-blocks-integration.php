<?php

use Automattic\WCServices\Integrations\WooCommerceBlocksIntegration;
use Automattic\WCServices\Utils;

/**
 * Unit tests for WooCommerceBlocksIntegration.
 */
class WP_Test_WooCommerce_Blocks_Integration extends WC_Unit_Test_Case {

	const HANDLE = 'woocommerce-services-store-notices';

	/**
	 * Path of the asset file created by a test, if any.
	 *
	 * @var string|null
	 */
	private $asset_file;

	/**
	 * Whether the dist directory was created by a test.
	 *
	 * @var bool
	 */
	private $created_dist_dir = false;

	/**
	 * Deregister the script and remove any test artifacts.
	 */
	public function tear_down() {
		wp_deregister_script( self::HANDLE );

		if ( $this->asset_file && file_exists( $this->asset_file ) ) {
			wp_delete_file( $this->asset_file );
			$this->asset_file = null;
		}
		if ( $this->created_dist_dir ) {
			rmdir( Utils::get_plugin_path() . 'dist' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir --- Test cleanup of a directory the test created.
			$this->created_dist_dir = false;
		}

		parent::tear_down();
	}

	/**
	 * Build an integration instance like the plugin loader does.
	 *
	 * @return WooCommerceBlocksIntegration
	 */
	private function new_integration() {
		return new WooCommerceBlocksIntegration( 'https://example.com/wp-content/plugins/woocommerce-services/dist/' );
	}

	/**
	 * Without an asset file, the script must register with the default dependencies.
	 */
	public function test_store_notices_script_registers_with_default_dependencies() {
		$this->new_integration()->initialize();

		$registered = wp_scripts()->registered;
		$this->assertArrayHasKey( self::HANDLE, $registered );

		$deps = $registered[ self::HANDLE ]->deps;
		$this->assertContains( 'wp-plugins', $deps );
		$this->assertContains( 'wp-element', $deps );
		$this->assertContains( 'wp-data', $deps );
		$this->assertContains( 'wc-blocks-checkout', $deps );
	}

	/**
	 * With an asset file present in dist/, its dependencies must win over the defaults.
	 */
	public function test_store_notices_script_uses_asset_file_dependencies_when_present() {
		$dist_dir = Utils::get_plugin_path() . 'dist/';

		if ( ! is_dir( $dist_dir ) ) {
			wp_mkdir_p( $dist_dir );
			$this->created_dist_dir = true;
		}

		$this->asset_file = $dist_dir . self::HANDLE . '.asset.php';
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents --- Test fixture written to a location the test cleans up.
			$this->asset_file,
			'<?php return array( "dependencies" => array( "wp-element" ), "version" => "test" );'
		);

		$this->new_integration()->initialize();

		$registered = wp_scripts()->registered;
		$this->assertArrayHasKey( self::HANDLE, $registered );
		$this->assertSame( array( 'wp-element' ), $registered[ self::HANDLE ]->deps );
	}
}
