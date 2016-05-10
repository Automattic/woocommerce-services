<?php

if ( ! class_exists( 'WC_Connect_Help_Provider' ) ) {

	class WC_Connect_Help_Provider {

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $service_settings_store ) {

			$this->service_settings_store = $service_settings_store;

			add_filter( 'woocommerce_admin_status_tabs', array( $this, 'status_tabs' ) );
			add_action( 'woocommerce_admin_status_content_connect', array( $this, 'page' ) );

		}

		/**
		 * Filters the WooCommerce System Status Tabs to add connect
		 *
		 * @param array $tabs
		 * @return array
		 */
		public function status_tabs( $tabs ) {

			if ( ! is_array( $tabs ) ) {
				$tabs = array();
			}
			$tabs[ 'connect' ] = _x( 'WooCommerce Connect', 'The WooCommerce Connect brandname', 'woocommerce' );
			return $tabs;

		}

		/**
		 * Returns the form schema object for the entire help page
		 *
		 * @return object
		 */
		protected function get_form_schema() {

			$form_schema = new stdClass();
			$form_schema->properties = new stdClass();
			return $form_schema;

		}

		/**
		 * Returns the form layout array for the entire help page
		 *
		 * @return array
		 */
		protected function get_form_layout() {

			return array(
				(object) array(
					'title' => _x( 'Health', 'This section displays the overall health of WooCommerce Connect and the things it depends on', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array() // TODO
				),
				(object) array(
					'title' => __( 'Services', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array() // TODO
				),
				(object) array(
					'title' => __( 'Debug', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array() // TODO
				),
				(object) array(
					'title' => __( 'Support', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array() // TODO
				)
			);

		}

		/**
		 * Returns the data bootstrap for the help page
		 *
		 * @return array
		 */
		protected function get_form_data() {

			return array(); // TODO

		}

		/**
		 * Localizes the bootstrap, enqueues the script and styles for the help page
		 */
		public function page() {

			$form_schema = new stdClass();
			$form_schema->properties = new stdClass();

			$admin_array = array(
				'storeOptions' => $this->service_settings_store->get_shared_settings(),
				'formSchema'   => $this->get_form_schema(),
				'formLayout'   => $this->get_form_layout(),
				'formData'     => $this->get_form_data(),
				'callbackURL'  => '', // TODO
				'nonce'        => '', // TODO
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			?>
				<h2>
					<?php _e( 'WooCommerce Connect Status', 'woocommerce' ); ?>
				</h2>
				<div id="wc-connect-admin-container"></div>
			<?php
		}

	}

}
