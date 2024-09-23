<?php

class WC_Connect_Jetpack_Deleted_Notice {
	/**
	 * Assets base URL.
	 *
	 * @var string
	 */
	private $base_url;

	public function __construct( $base_url ) {
		$this->base_url = $base_url;
	}

	public function register_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_script' ) );
	}

	public function maybe_enqueue_script() {
		$screen = get_current_screen();

		if ( ! $screen || 'plugins' !== $screen->base ) {
			return;
		}

		wp_enqueue_style( 'wp-components' ); // Needed to render the modal.
		wp_enqueue_script( 'wc_connect_jetpack_deleted_notice', $this->base_url . 'woocommerce-services-jetpack-deleted-notice-' . WC_Connect_Loader::get_wcs_version() . '.js' );
		wp_localize_script(
			'wc_connect_jetpack_deleted_notice',
			'wcServicesJetpackDeletedNotice',
			array(
				'nonce'        => wp_create_nonce( 'wcs_nux_notice' ),
				'url'          => admin_url( 'admin-post.php' ),
				'action'       => 'register_woocommerce_services_jetpack',
				'redirect_url' => admin_url( 'plugins.php' ),
			)
		);
	}
}
