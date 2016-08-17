<?php

if ( ! class_exists( 'WC_Connect_Settings_View' ) ) {

	class WC_Connect_Settings_View {

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @array
		 */
		protected $fieldsets;

		public function __construct( WC_Connect_Service_Settings_Store $service_settings_store,
			WC_Connect_Logger $logger ) {

			$this->service_settings_store = $service_settings_store;
			$this->logger = $logger;

			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'tabs' ), 30 ); // 30 positions connect as the last tab
			add_action( 'woocommerce_settings_connect', array( $this, 'page' ) );
		}

		/**
		 * Filters the tabs to add connect
		 *
		 * @param array $tabs
		 * @return array
		 */
		public function tabs( $tabs ) {

			if ( ! is_array( $tabs ) ) {
				$tabs = array();
			}
			$tabs[ 'connect' ] = _x( 'WooCommerce Connect', 'The WooCommerce Connect brandname', 'woocommerce' );
			return $tabs;

		}

		/**
		 * Localizes the bootstrap, enqueues the script and styles for the settings page
		 */
		public function page() {
			$root_view = 'wc-connect-admin-settings';
			$admin_array = array(
				'storeOptions' => $this->service_settings_store->get_store_options(),
				// TODO
//				'formSchema'   => $this->get_form_schema(),
//				'formLayout'   => $this->get_form_layout(),
//				'formData'     => $this->get_form_data(),
				'callbackURL'  => get_rest_url( null, "/wc/v1/connect/settings" ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'rootView'     => $root_view,
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			// hiding the save button because the react container has its own
			global $hide_save_button;
			$hide_save_button = true;

			?>
				<h2>
					<?php _e( 'Settings', 'woocommerce' ); ?>
				</h2>
				<div class="wc-connect-admin-container" id="<?php echo esc_attr( $root_view ) ?>"></div>
			<?php
		}

	}

}
