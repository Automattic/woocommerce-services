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
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @array
		 */
		protected $fieldsets;

		public function __construct( WC_Connect_Payment_Methods_Store $payment_methods_store,
			WC_Connect_Service_Settings_Store $service_settings_store,
			WC_Connect_Service_Schemas_Store $service_schemas_store,
			WC_Connect_Logger $logger ) {

			$this->id    = 'connect';
			$this->label = _x( 'Connect for WooCommerce', 'The Connect for WooCommerce brandname', 'connectforwoocommerce' );

			$this->payment_methods_store = $payment_methods_store;
			$this->service_settings_store = $service_settings_store;
			$this->service_schemas_store = $service_schemas_store;
			$this->logger = $logger;

			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'tabs' ), 30 ); // 30 positions connect as the last tab
			add_filter( 'woocommerce_get_sections_connect', array( $this, 'get_sections' ) );
			add_action( 'woocommerce_sections_connect', array( $this, 'output_section_tabs') );
			add_action( 'woocommerce_settings_connect', array( $this, 'output_settings_screen' ) );
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
			$tabs[ 'connect' ] = _x( 'Connect for WooCommerce', 'The Connect for WooCommerce brandname', 'connectforwoocommerce' );
			return $tabs;
		}

		/**
		 * Get sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			return array(
				'' => __( 'Account Settings', 'connectforwoocommerce' ),
				'packages' => __( 'Packaging Manager', 'connectforwoocommerce' ),
			);
		}

		/**
		 * Outputs the section tabs. Based on WC_Settings_Page::output_sections.
		 * We can't derive readily from WC_Settings_Page because we use
		 * dependency injection and WC expects to simply include each settings page
		 * instance
		 */

		public function output_section_tabs() {
			global $current_section;
			$sections = $this->get_sections();

			echo '<ul class="subsubsub">';
			$array_keys = array_keys( $sections );
			foreach ( $sections as $id => $label ) {
				echo '<li><a href="' . admin_url( 'admin.php?page=wc-settings&tab=connect&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
			}
			echo '</ul><br class="clear" /><br/>';
		}

		/**
		 * Things used to help render the view, but which cannot be changed in the view
		 */
		public function get_form_meta() {
			return array(
				'payment_methods' => $this->payment_methods_store->get_payment_methods()
			);
		}

		/**
		 * Mutate-able settings bootstrapped to the view -- should be
		 * identical to what is returned by the WC_REST_Connect_Settings_Controller
		 * GET /connect/settings endpoint (see callbackURL below)
		 */
		public function get_form_data() {
			return $this->service_settings_store->get_account_settings();
		}

		/**
		 * Output the settings.
		 */
		public function output_settings_screen() {
			global $current_section;

			if ( '' === $current_section ) {
				$this->output_account_screen();
			} else if ( 'packages' == $current_section ) {
				$this->output_packages_screen();
			}
		}

		/**
		 * Localizes the bootstrap, enqueues the script and styles for the settings page
		 */
		public function output_account_screen() {
			// Always get a fresh copy when loading this view
			$this->payment_methods_store->fetch_payment_methods_from_connect_server();

			// Fire up the view
			$root_view = 'wc-connect-account-settings';
			$admin_array = array(
				'storeOptions' => $this->service_settings_store->get_store_options(),
				'formData'     => $this->get_form_data(),
				'formMeta'     => $this->get_form_meta(),
				'callbackURL'  => get_rest_url( null, "/wc/v1/connect/account/settings" ),
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
				<div class="wc-connect-admin-container" id="<?php echo esc_attr( $root_view ) ?>"></div>
			<?php


			$is_authenticated = get_option( 'wc_connect_test_wpcom_auth', false );
			echo '<h3>WPCOM AUTH</h3>';
			if ( false === $is_authenticated ) {
				define( 'CLIENT_ID', 50573 );
				define( 'CLIENT_SECRET', 'XXXXXXXXXXXX' );
				define( 'REDIRECT_URL', 'http://localhost:5000/auth/redirect' );
				define( 'REQUEST_TOKEN_URL', 'https://public-api.wordpress.com/oauth2/token' );
				define( 'AUTHENTICATE_URL', 'https://public-api.wordpress.com/oauth2/authenticate' );
				session_start();
				$wpcc_state = md5( mt_rand() );
				$_SESSION[ 'wpcc_state' ] = $wpcc_state;

				$url_to = AUTHENTICATE_URL . '?' . http_build_query( array(
						'response_type' => 'code',
						'client_id'     => CLIENT_ID,
						'state'         => $wpcc_state,
						'redirect_uri'  => REDIRECT_URL
					) );
				echo '<p>Not Authenticated</p>';
				echo '<p><a href="' . $url_to . '"><img src="//s0.wp.com/i/wpcc-button.png" width="231" /></a></p>';
			} else {
				echo '<p>Authenticated</p>';
				echo '<p><a target="_blank" href="http://bing.com" class="button form-button is-primary">Clear</a></p>';
			}
		}

		public function get_packages_form_data() {
			return array(
				'custom' => $this->service_settings_store->get_packages(),
				'predefined' => $this->service_settings_store->get_predefined_packages()
			);
		}

		public function get_packages_form_schema() {
			return array(
				'custom' => $this->service_schemas_store->get_packages_schema(),
				'predefined' => $this->service_schemas_store->get_predefined_packages_schema()
			);
		}

		public function output_packages_screen() {
			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab' => 'connect'
				),
				admin_url( 'admin.php' )
			) );
			$store_options = $this->service_settings_store->get_store_options();
			$root_view = 'wc-connect-packages';

			$admin_array = array(
				'storeOptions' => $store_options,
				'formSchema'   => $this->get_packages_form_schema(),
				'formData'     => $this->get_packages_form_data(),
				'callbackURL'  => get_rest_url( null, '/wc/v1/connect/packages' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'submitMethod' => 'POST',
				'rootView'     => $root_view,
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			// hiding the save button because the react container has its own
			global $hide_save_button;
			$hide_save_button = true;

			?>
			<div class="wc-connect-admin-container" id="<?php echo esc_attr( $root_view ) ?>">
				<span class="form-troubles" style="opacity: 0">
					<?php printf( __( 'Settings not loading? Visit the <a href="%s">status page</a> for troubleshooting steps.', 'connectforwoocommerce' ), $debug_page_uri ); ?>
				</span>
			</div>
			<?php
		}

	}

}
