<?php

if ( ! class_exists( 'WC_Connect_Settings_Pages' ) ) {

	class WC_Connect_Settings_Pages {

		/**
		 * @var WC_Connect_Payment_Methods_Store
		 */
		protected $payment_methods_store;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;

		/**
		 * @array
		 */
		protected $fieldsets;

		public function __construct( WC_Connect_Payment_Methods_Store $payment_methods_store,
									 WC_Connect_Service_Settings_Store $service_settings_store,
									 WC_Connect_Service_Schemas_Store $service_schemas_store ) {
			$this->id    = 'connect';
			$this->label = _x( 'WooCommerce Services', 'The WooCommerce Services brandname', 'woocommerce-services' );

			$this->payment_methods_store = $payment_methods_store;
			$this->service_settings_store = $service_settings_store;
			$this->service_schemas_store = $service_schemas_store;

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
			$shipping_tabs[ 'label-settings'] = __( 'Shipping Labels', 'woocommerce-services' );
			return $shipping_tabs;
		}

		/**
		 * Helper method to get if Jetpack is in development mode
		 * @return bool
		 */
		protected function is_jetpack_dev_mode() {
			if ( method_exists( 'Jetpack', 'is_development_mode' ) ) {
				return Jetpack::is_development_mode();
			}

			return false;
		}

		/**
		 * Helper method to get if Jetpack is connected (aka active)
		 * @return bool
		 */
		protected function is_jetpack_connected() {
			if ( method_exists( 'Jetpack', 'is_active' ) ) {
				return Jetpack::is_active();
			}

			return false;
		}

		/**
		 * Helper method to get the Jetpack master user, IF we are connected
		 * @return WP_User | false
		 */
		protected function get_master_user() {
			include_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( $this->is_jetpack_connected() && method_exists( 'Jetpack_Options', 'get_option' ) ) {
				$master_user_id = Jetpack_Options::get_option( 'master_user' );
				return get_userdata( $master_user_id );
			}

			return false;
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
					$master_user = $this->get_master_user();
					if ( $this->is_jetpack_dev_mode() || ( is_a( $master_user, 'WP_User' ) && $current_user->ID == $master_user->ID ) ) {
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

			if ( $this->is_jetpack_dev_mode() ) {
				if ( $this->is_jetpack_connected() ) {
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

			// Always get a fresh copy when loading this view
			$this->payment_methods_store->fetch_payment_methods_from_connect_server();

			do_action( 'enqueue_wc_connect_script', 'wc-connect-account-settings', array(
				'storeOptions' => $this->service_settings_store->get_store_options(),
				'formData'     => $this->service_settings_store->get_account_settings(),
				'formMeta'     => array(
					'payment_methods' => $this->payment_methods_store->get_payment_methods(),
				),
			) );
		}

		public function output_no_priv_account_screen() {
			// hiding the save button because nothing can be saved
			global $hide_save_button;
			$hide_save_button = true;

			wp_enqueue_style( 'wc_connect_admin' );

			$master_user = $this->get_master_user();
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

			do_action( 'enqueue_wc_connect_script', 'wc-connect-packages', array(
				'storeOptions' => $this->service_settings_store->get_store_options(),
				'formSchema'   => array(
					'custom' => $this->service_schemas_store->get_packages_schema(),
					'predefined' => $this->service_schemas_store->get_predefined_packages_schema(),
				),
				'formData'     => array(
					'custom' => $this->service_settings_store->get_packages(),
					'predefined' => $this->service_settings_store->get_predefined_packages(),
				),
			) );
		}

	}

}
