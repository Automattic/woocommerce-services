<?php

if ( ! class_exists( 'WC_Connect_Settings_Pages' ) ) {

	class WC_Connect_Settings_Pages {
		/**
		 * @array
		 */
		protected $fieldsets;

		public function __construct() {
			$this->id    = 'connect';
			$this->label = _x( 'WooCommerce Services', 'The WooCommerce Services brandname', 'woocommerce-services' );

			add_filter( 'woocommerce_get_sections_shipping', array( $this, 'get_sections' ), 30 );
			add_action( 'woocommerce_settings_shipping', array( $this, 'output_settings_screen' ) );
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections( $shipping_tabs ) {
			if ( ! is_array( $shipping_tabs ) ) {
				$shipping_tabs = array();
			}

			$shipping_tabs[ 'package-settings' ] = __( 'Packages', 'woocommerce-services' );
			$shipping_tabs[ 'label-settings'] = __( 'Shipping labels', 'woocommerce-services' );
			return $shipping_tabs;
		}

		/**
		 * Output the settings.
		 */
		public function output_settings_screen() {
			global $current_section;
			global $current_user;

			switch( $current_section ) {
				case 'package-settings':
					$this->output_packages_screen();
					break;
				case 'label-settings':
					$master_user = WC_Connect_Jetpack::get_master_user();
					if ( WC_Connect_Jetpack::is_development_mode() || ( is_a( $master_user, 'WP_User' ) && $current_user->ID === $master_user->ID ) ) {
						$this->output_account_screen();
					} else {
						$this->output_no_priv_account_screen();
					}
					break;
			}
		}

		/**
		 * Localizes the bootstrap, enqueues the script and styles for the settings page
		 */
		public function output_account_screen() {
			// hiding the save button because the react container has its own
			global $hide_save_button;
			$hide_save_button = true;

			if ( WC_Connect_Jetpack::is_development_mode() ) {
				if ( WC_Connect_Jetpack::is_active() ) {
					$message = __( 'Note: Jetpack is connected, but development mode is also enabled on this site. Please disable development mode.', 'woocommerce-services' );
				} else {
					$message = __( 'Note: Jetpack development mode is enabled on this site. This site will not be able to obtain payment methods from WooCommerce Services production servers.', 'woocommerce-services' );
				}
				?>
					<div class="wc-connect-admin-dev-notice">
						<p>
							<?php echo esc_html( $message ); ?>
						</p>
					</div>
				<?php
			}

			$extra_args = array();
			if ( isset( $_GET['from_order'] ) ) {
				$extra_args['order_id'] = $_GET['from_order'];
				$extra_args['order_href'] = get_edit_post_link( $_GET['from_order'] );
			}

			do_action( 'enqueue_wc_connect_script', 'wc-connect-account-settings', $extra_args );
		}

		public function output_no_priv_account_screen() {
			// hiding the save button because nothing can be saved
			global $hide_save_button;
			$hide_save_button = true;

			wp_enqueue_style( 'wc_connect_admin' );

			$master_user = WC_Connect_Jetpack::get_master_user();
			if ( is_a( $master_user, 'WP_User' ) ) {
				$message = sprintf(
					__( 'Only the primary Jetpack user can manage shipping label payment methods. Please login as %1$s (%2$s) to manage payment methods.', 'woocommerce-services' ),
					$master_user->display_name,
					$master_user->user_login
				);
			} else {
				$message = __( 'You must first connect your Jetpack before you can manage your shipping label payment method.', 'woocommerce-services' );
			}

			?>
				<div class="wcc-root">
					<div class="wc-connect-no-priv-settings">
						<h3 class="settings-group-header form-section-heading">
							<?php echo esc_html( __( 'Payment Method', 'woocommerce-services' ) ); ?>
						</h3>
						<?php echo esc_html( $message ) ?>
					</div>
				</div>
			<?php
		}

		public function output_packages_screen() {
			// hiding the save button because the react container has its own
			global $hide_save_button;
			$hide_save_button = true;

			do_action( 'enqueue_wc_connect_script', 'wc-connect-packages' );
		}

	}

}
