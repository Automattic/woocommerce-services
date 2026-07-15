<?php

require_once __DIR__ . '/class-wcs-test-blocks-integration.php';

/**
 * Unit tests for WooCommerceBlocksIntegration.
 */
class WP_Test_WooCommerce_Blocks_Integration extends WC_Unit_Test_Case {

	const HANDLE = 'woocommerce-services-store-notices';

	/**
	 * Temp directory used for asset files, removed on tear down.
	 *
	 * @var string
	 */
	private $asset_dir;

	/**
	 * Create a fresh temp asset directory.
	 */
	public function set_up() {
		parent::set_up();

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
	 * @return WCS_Test_Blocks_Integration
	 */
	private function new_integration() {
		$integration            = new WCS_Test_Blocks_Integration( 'https://example.com/wp-content/plugins/woocommerce-services/dist/' );
		$integration->asset_dir = $this->asset_dir;

		return $integration;
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
	 * With an asset file present, its dependencies must win over the defaults.
	 */
	public function test_store_notices_script_uses_asset_file_dependencies_when_present() {
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents --- Test fixture written to a temp directory the test cleans up.
			$this->asset_dir . self::HANDLE . '.asset.php',
			'<?php return array( "dependencies" => array( "wp-element" ), "version" => "test" );'
		);

		$this->new_integration()->initialize();

		$registered = wp_scripts()->registered;
		$this->assertArrayHasKey( self::HANDLE, $registered );
		$this->assertSame( array( 'wp-element' ), $registered[ self::HANDLE ]->deps );
	}
}
