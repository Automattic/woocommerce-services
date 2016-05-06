<?php

if ( ! class_exists( 'WC_Connect_Help_Provider' ) ) {

	class WC_Connect_Help_Provider {

		public function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 50 ); // 50 was chosen to have it appear after Reports but before Settings
		}

		public function admin_menu() {
			add_submenu_page( 'woocommerce', __( 'Connect', 'woocommerce' ),  __( 'Connect', 'woocommerce' ) , 'manage_woocommerce', 'wc-connect', array( $this, 'page' ) );
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
