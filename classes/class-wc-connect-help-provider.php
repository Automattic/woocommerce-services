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

		protected function get_indicator_schema() {

			return (object) array(
				"properties" => (object) array(
					"id" => (object) array(
						"type" => "string"
					),
					"state" => (object) array(
						"type" => "string",
						"default" => "bad"
					),
					"state_message" => (object) array(
						"type" => "string",
						"default" => "TBD",
					),
					"last_updated" => (object) array(
						"type" => "string",
						"default" => "Yesterday"
					)
				)
			);

		}

		// TODO - refactor to accept scope as parameter or maybe use filtering?
		protected function get_woocommerce_health_indicator_ids() {
			return array(
				(object) array(
					"id" => "woocommerce",
				)
			);
		}

		protected function get_woocommerce_health_indicators() {
			return array(
				"woocommerce" => (object) array(
					"id" => "woocommerce",
					"state" => "purple",
					"state_message" => "WooCommerce is Good to Go",
					"last_updated" => "Sometime recently"
				)
			);
		}

		/**
		 * Returns the form schema object for the entire help page
		 *
		 * @return object
		 */
		protected function get_form_schema() {
			return (object) array(
				"type" => "object",
				"required" => array(),
				// these definitions enumerate the specific indicator IDs for each
				"definitions" => (object) array(
					"woocommerce_health_indicators" => $this->get_woocommerce_health_indicator_ids()
				),
				"properties" => (object) array(
					"woocommerce_health" => (object) array(
						"title" => "WooCommerce Health",
						"type" => "object",
						"definition" => "woocommerce_health_indicators", // this tells the form which definition to select from definitions
						"items" => $this->get_indicator_schema()
					)
				)
			);
		}

		/**
		 * Returns the form layout array for the entire help page
		 *
		 * @return array
		 */
		protected function get_form_layout() {

			return array(
				(object) array(
					"title" => _x( "Health", "This section displays the overall health of WooCommerce Connect and the things it depends on", "woocommerce" ),
					"type" => "fieldset",
					"items" => array(
						(object) array(
							"key" => "woocommerce_health",
							"type" => "indicators",
						)
					)
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

			return array(
				"woocommerce_health" => $this->get_woocommerce_health_indicators(),
				"last_name" => "Snook",
			);
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
