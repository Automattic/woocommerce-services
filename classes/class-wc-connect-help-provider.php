<?php

if ( ! class_exists( 'WC_Connect_Help_Provider' ) ) {

	class WC_Connect_Help_Provider {

		public function __construct() {
			add_filter( 'woocommerce_admin_status_tabs', array( $this, 'status_tabs' ) );
			add_action( 'woocommerce_admin_status_connect', array( $this, 'page' ) );
		}

		public function status_tabs( $tabs ) {
			if ( ! is_array( $tabs ) ) {
				$tabs = array();
			}
			$tabs[ 'connect' ] = _x( 'Connect', 'The WooCommerce Connect brandname', 'woocommerce' );
			return $tabs;
		}

		protected function get_form_schema() {
			$form_schema = new stdClass();
			$form_schema->properties = new stdClass();
			return $form_schema;
		}

		protected function get_form_layout() {
			return array(
				(object) array(
					'title' => __( 'Health', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array()
				),
				(object) array(
					'title' => __( 'Services', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array()
				),
				(object) array(
					'title' => __( 'Debug', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array()
				),
				(object) array(
					'title' => __( 'Support', 'woocommerce' ),
					'type' => 'fieldset',
					'items' => array()
				)
			);
		}

		protected function get_form_data() {
			return array(); // TODO
		}

		public function page() {
			$form_schema = new stdClass();
			$form_schema->properties = new stdClass();

			$admin_array = array(
				'storeOptions' => new stdClass(),
				'formSchema'   => $this->get_form_schema(),
				'formLayout'   => $this->get_form_layout(),
				'formData'     => $this->get_form_data(),
				'callbackURL'  => '',
				'nonce'        => '',
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
