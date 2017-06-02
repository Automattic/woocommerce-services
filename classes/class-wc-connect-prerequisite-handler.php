<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Prerequisite_Handler' ) ) {

	class WC_Connect_Prerequisite_Handler {
		public function init() {
			if ( ! class_exists( 'WooCommerce' ) ) {
				if ( 0 === validate_plugin( 'woocommerce/woocommerce.php' ) ) {
					add_action( 'admin_notices', array( $this, 'show_woocommerce_activate_notice' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'show_woocommerce_install_notice' ) );
				}
				return;
			}

			if ( ! class_exists( 'Jetpack_Data' ) ) {
				if ( 0 === validate_plugin( 'jetpack/jetpack.php' ) ) {
					add_action( 'admin_notices', array( $this, 'show_jetpack_activate_notice' ) );
				} else {
					add_action( 'admin_notices', array( $this, 'show_jetpack_install_notice' ) );
				}
				return;
			}

			$user_token = Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			$is_connected = $user_token && is_object( $user_token ) && isset( $user_token->external_user_id );
			if ( ! $is_connected ) {
				add_action( 'admin_notices', array( $this, 'show_jetpack_connect_notice' ) );
				return;
			}

			if ( ! WC_Connect_Options::get_option( 'tos_accepted', false ) ) {
				add_action( 'admin_notices', array( $this, 'admin_tos_notice' ) );
				return;
			}
		}

		public function show_jetpack_install_notice() {
			$notice_text = __( 'To get started you need to install Jetpack.', 'woocommerce-services' );
			$button_label = __( 'Install Jetpack', 'woocommerce-services' );
			$button_url = '#';
			$button_class = 'button-primary wcc-install-jetpack';
			wp_enqueue_script( 'wc_connect_banner' );
			$this->render_prerequisite_notice( $notice_text, $button_url, $button_class, $button_label );
		}

		public function show_jetpack_activate_notice() {
			$button_class = 'button-primary';
			$notice_text = __( 'To get started you need to activate Jetpack.', 'woocommerce-services' );
			$button_label = __( 'Activate Jetpack', 'woocommerce-services' );
			$button_url = wp_nonce_url( 'plugins.php?action=activate&plugin=jetpack/jetpack.php', 'activate-plugin_jetpack/jetpack.php' );
			$this->render_prerequisite_notice( $notice_text, $button_url, $button_class, $button_label );
		}

		public function show_woocommerce_install_notice() {
			$notice_text = __( 'To get started you need to install WooCommerce.', 'woocommerce-services' );
			$button_label = __( 'Install WooCommerce', 'woocommerce-services' );
			$button_url = '#';
			$button_class = 'button-primary wcc-install-woocommerce';
			wp_enqueue_script( 'wc_connect_banner' );
			$this->render_prerequisite_notice( $notice_text, $button_url, $button_class, $button_label );
		}

		public function show_woocommerce_activate_notice() {
			$button_class = 'button-primary';
			$notice_text = __( 'To get started you need to activate WooCommerce.', 'woocommerce-services' );
			$button_label = __( 'Activate WooCommerce', 'woocommerce-services' );
			$button_url = wp_nonce_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php', 'activate-plugin_woocommerce/woocommerce.php' );
			$this->render_prerequisite_notice( $notice_text, $button_url, $button_class, $button_label );
		}

		public function show_jetpack_connect_notice() {
			$button_class = 'button-primary';
			$screen = get_current_screen();
			if ( 'jetpack' === $screen->parent_base || 'plugins' === $screen->base ) {
				return; // Jetpack Dashboard and the plugins list screen already have big "Connect to WordPress.com" buttons
			}

			$notice_text = __( 'To get started, please connect Jetpack to your WordPress.com account.', 'woocommerce-services' );
			$button_label = __( 'Connect to WordPress.com', 'woocommerce-services' );
			$redirect_to = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
			$button_url = Jetpack::init()->build_connect_url( false, $redirect_to, 'woocommerce-services' );
			$this->render_prerequisite_notice( $notice_text, $button_url, $button_class, $button_label );
		}

		private function render_prerequisite_notice( $notice_text, $button_url, $button_class, $button_label ) {
			?>
			<div class="notice wcc-admin-notice">
				<h2><?php _e( 'Welcome to WooCommerce Services', 'woocommerce-services' ) ?></h2>
				<p>
					<b><?php echo $notice_text ?></b>
				</p>
				<p>
					<a href="<?php echo esc_url( $button_url ) ?>"
					   data-error-message="<?php esc_attr_e( 'There was an error installing Jetpack. Please try installing it manually.', 'woocommerce-services' ) ?>"
					   class="<?php echo esc_attr( $button_class ) ?>">
						<?php echo $button_label ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

}
