<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Jetpack_Installer' ) ) {
	class WC_Connect_Jetpack_Installer {

		public function __construct() {
			$this->add_error_notice();
			$this->try_install();
		}

		/**
		 * Verify the intent to install Jetpack, and kick off installation.
		 */
		public function try_install() {
			if ( ! isset( $_GET[ 'jetpack-install-action' ] ) ) {
				return;
			}
			check_admin_referer( 'wc-services-jetpack-install' );

			$result = false;

			switch ( $_GET[ 'jetpack-install-action' ] ) {
				case 'install':
					if ( current_user_can( 'install_plugins' ) ) {
						$result = $this->install();
						if ( $result ) {
							$result = $this->activate();
						}
					}
					break;

				case 'activate':
					if ( current_user_can( 'activate_plugins' ) ) {
						$result = $this->activate();
					}
					break;
			}

			wp_safe_redirect( $result ? $this->get_connect_url() : add_query_arg( 'wc-services-jetpack-install-error', true, wp_get_referer() ) );
			exit;
		}

		/**
		 * Set up installation error admin notice.
		 */
		public function add_error_notice() {
			if ( ! empty( $_GET[ 'wc-services-jetpack-install-error' ] ) ) {
				add_action( 'admin_notices', array( $this, 'error_notice' ) );
			}
		}

		/**
		 * Notify the user that the installation of Jetpack failed.
		 */
		public function error_notice() {
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php _e( 'There was an error installing Jetpack. Please try installing it manually.', 'jetpack' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Download and install the Jetpack plugin.
		 *
		 * @return bool result of installation
		 */
		private function install() {
			include_once( ABSPATH . '/wp-admin/includes/admin.php' );
			include_once( ABSPATH . '/wp-admin/includes/plugin-install.php' );
			include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			include_once( ABSPATH . '/wp-admin/includes/class-wp-upgrader.php' );
			include_once( ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php' );

			$api = plugins_api( 'plugin_information', array( 'slug' => 'jetpack' ) );

			if ( is_wp_error( $api ) ) {
				return false;
			}

			$upgrader = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
			$result   = $upgrader->install( $api->download_link );

			return true === $result;
		}

		/**
		 * Activate the Jetpack plugin.
		 *
		 * @return bool result of activation
		 */
		private function activate() {
			$result = activate_plugin( 'jetpack/jetpack.php' );

			// activate_plugin() returns null on success
			return is_null( $result );
		}

		/**
		 * Get the URL to redirect to when Jetpack needs to be connected to WordPress.com
		 *
		 * @return string URL
		 */
		public function get_connect_url() {
			$redirect_to = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
			return Jetpack::init()->build_connect_url( false, $redirect_to, 'woocommerce-services' );
		}
	}
}
