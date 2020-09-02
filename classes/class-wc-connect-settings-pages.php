<?php

if ( ! class_exists( 'WC_Connect_Settings_Pages' ) ) {

	class WC_Connect_Settings_Pages {
		/**
		 * @array
		 */
		protected $fieldsets;

		/**
		 * @var WC_Connect_Continents
		 */
		protected $continents;


		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		public function __construct( WC_Connect_API_Client $api_client ) {
			$this->id    = 'connect';
			$this->label = _x( 'WooCommerce Services', 'The WooCommerce Services brandname', 'woocommerce-services' );
			$this->continents = new WC_Connect_Continents();
            $this->api_client = $api_client;

			add_filter( 'woocommerce_get_sections_shipping', array( $this, 'get_sections' ), 30 );
			add_action( 'woocommerce_settings_shipping', array( $this, 'output_settings_screen' ), 5 );
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

			add_filter( 'woocommerce_get_settings_shipping', '__return_empty_array' );
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
			if( $carriers_response = $this->api_client->get_carrier_accounts() ) {
				$extra_args[ 'carriers' ] = $carriers_response->carriers;
			}

			if ( isset( $_GET['from_order'] ) ) {
				$extra_args['order_id'] = $_GET['from_order'];
				$extra_args['order_href'] = get_edit_post_link( $_GET['from_order'] );
			}
			if ( isset( $_GET['carrier'] ) ) {
				$extra_args['carrier']    = $_GET['carrier'];
				$extra_args['continents'] = $this->continents->get();
				$translated_carrier_name  = null;
				switch ( strtolower( $extra_args['carrier'] ) ) {
					case 'ups':
						$translated_carrier_name = __( 'UPS', 'woocommerce-services' );
						break;
					default:
						break;
				}
				if ( $translated_carrier_name ) {
				?>
					<h2>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings' ) ); ?>"><?php esc_html_e( 'WooCommerce Services', 'woocommerce-services' ); ?></a> &gt;
						<span><?php echo esc_html( $translated_carrier_name ); ?></span>
					</h2>
				<?php
				}
			}

			do_action( 'enqueue_wc_connect_script', 'wc-connect-shipping-settings', $extra_args );
		}
	}

}
