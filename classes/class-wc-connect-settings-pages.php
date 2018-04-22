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

			$shipping_tabs[ 'woocommerce-services-settings' ] = __( 'WooCommerce Services', 'woocommerce-services' );
			return $shipping_tabs;
		}

		/**
		 * Output the settings.
		 */
		public function output_settings_screen() {
			global $current_section;

			if ( 'woocommerce-services-settings' !== $current_section ) {
				return;
			}

			$this->output_shipping_settings_screen();
		}

		/**
		 * Localizes the bootstrap, enqueues the script and styles for the settings page
		 */
		public function output_shipping_settings_screen() {
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

			do_action( 'enqueue_wc_connect_script', 'wc-connect-shipping-settings', $extra_args );
		}
	}

}
