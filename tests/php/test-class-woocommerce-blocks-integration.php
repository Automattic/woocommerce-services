<?php

use Automattic\WCServices\Integrations\WooCommerceBlocksIntegration;
use Automattic\WCServices\Utils;

/**
 * Unit tests for WooCommerceBlocksIntegration.
 */
class WP_Test_WooCommerce_Blocks_Integration extends WC_Unit_Test_Case {

	const HANDLE = 'woocommerce-services-store-notices';

	const DEFAULT_DEPS = array( 'wp-plugins', 'wp-element', 'wp-data', 'wc-blocks-checkout' );

	/**
	 * Temp directory used for asset files, removed on tear down.
	 *
	 * @var string
	 */
	private $asset_dir;

	/**
	 * Create a fresh temp asset directory and a clean script registry.
	 */
	public function set_up() {
		parent::set_up();

		// Anything already holding the handle would make wp_register_script() a silent no-op.
		wp_deregister_script( self::HANDLE );

		$this->asset_dir = trailingslashit( get_temp_dir() ) . 'wcs-test-assets-' . uniqid() . '/';
		wp_mkdir_p( $this->asset_dir );
	}

	/**
	 * Deregister the script and remove the temp asset directory.
	 */
	public function tear_down() {
		wp_deregister_script( self::HANDLE );

		$asset_file = $this->asset_dir . self::HANDLE . '.asset.php';
		if ( file_exists( $asset_file ) ) {
			wp_delete_file( $asset_file );
		}
		if ( is_dir( $this->asset_dir ) ) {
			rmdir( $this->asset_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir --- Test cleanup of a temp directory the test created.
		}

		parent::tear_down();
	}

	/**
	 * Build an integration instance whose asset lookup uses the temp directory.
	 *
	 * @return WooCommerceBlocksIntegration
	 */
	private function new_integration() {
		return new WooCommerceBlocksIntegration( $this->asset_dir );
	}

	/**
	 * Write an asset file into the temp directory.
	 *
	 * @param string $contents Raw PHP contents of the asset file.
	 */
	private function write_asset_file( string $contents ) {
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents --- Test fixture written to a temp directory the test cleans up.
			$this->asset_dir . self::HANDLE . '.asset.php',
			$contents
		);
	}

	/**
	 * Get the registered script object for the store notices handle.
	 *
	 * @return _WP_Dependency
	 */
	private function get_registered_script() {
		$registered = wp_scripts()->registered;
		$this->assertArrayHasKey( self::HANDLE, $registered );

		return $registered[ self::HANDLE ];
	}

	/**
	 * With no directory injected, the asset lookup must point at the plugin's real dist
	 * directory. This pins the production wiring: resolving the asset file against the
	 * asset *URL* instead is the bug this class had, and it silently disables the lookup.
	 */
	public function test_asset_lookup_defaults_to_the_plugin_dist_directory() {
		$integration = new WooCommerceBlocksIntegration();

		$property = new ReflectionProperty( WooCommerceBlocksIntegration::class, 'dist_dir' );
		$property->setAccessible( true );
		$dist_dir = $property->getValue( $integration );

		$this->assertSame( WCSERVICES_PLUGIN_DIST_DIR, $dist_dir );
		$this->assertStringNotContainsString( '://', $dist_dir, 'The asset lookup must use a filesystem path, not a URL.' );
	}

	/**
	 * Without an asset file, the script must register with the default dependencies.
	 */
	public function test_store_notices_script_registers_with_default_dependencies() {
		$this->new_integration()->initialize();

		$this->assertSame( self::DEFAULT_DEPS, $this->get_registered_script()->deps );
	}

	/**
	 * The script must keep pointing at the versioned bundle in the enqueue base URL.
	 */
	public function test_store_notices_script_registers_the_versioned_bundle_url() {
		$this->new_integration()->initialize();

		$script = $this->get_registered_script();

		$this->assertSame(
			Utils::get_enqueue_base_url() . self::HANDLE . '-' . Utils::get_wcservices_version() . '.js',
			$script->src
		);
		$this->assertNull( $script->ver, 'The versioned filename carries cache busting, so no ver query arg is expected.' );
	}

	/**
	 * With an asset file present, its dependencies must win over the defaults.
	 */
	public function test_store_notices_script_uses_asset_file_dependencies_when_present() {
		$this->write_asset_file( '<?php return array( "dependencies" => array( "wp-element" ), "version" => "test" );' );

		$this->new_integration()->initialize();

		$this->assertSame( array( 'wp-element' ), $this->get_registered_script()->deps );
	}

	/**
	 * An asset file that does not return a dependency list must not strip the defaults.
	 */
	public function test_store_notices_script_falls_back_to_defaults_for_a_malformed_asset_file() {
		$this->write_asset_file( '<?php return "nonsense";' );

		$this->new_integration()->initialize();

		$this->assertSame( self::DEFAULT_DEPS, $this->get_registered_script()->deps );
	}
}
