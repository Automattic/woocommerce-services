<?php

if ( ! class_exists( 'WC_Connect_Shipping_Method' ) ) {

	class WC_Connect_Shipping_Method extends WC_Shipping_Method {

		public function __construct( $settings ) {
			foreach ( (array) $settings as $key => $value ) {
				$this->{$key} = $value;
			}

			$this->init();
		}

		public function init() {
			$this->init_form_fields();
			$this->init_settings();
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		public function calculate_shipping( $package ) {
			// TODO - request the rate from connect.woocommerce.com
			$rate = array(
				'id' => $this->id,
				'label' => $this->title,
				'cost' => '19.99',
				'calc_tax' => 'per_item'
			);

			$this->add_rate( $rate );
		}

		public function admin_options() {
			global $hide_save_button;
			$hide_save_button = true;

			?>
			</form>
				<div id="wc-connect-admin-container">
					React goes here
				</div>
			<form>
			<?php
		}

		public function admin_enqueue_scripts( $hook ) {
			if ( 'woocommerce_page_wc-settings' !== $hook ) {
				return;
			}

			if ( ! isset( $_GET['section'] ) || 'wc_connect_shipping_method' !== $_GET['section'] ) {
				return;
			}

			wp_register_script( 'wc_connect_shipping_admin', plugins_url( 'build/bundle.js', dirname( __FILE__ ) ), array() );

			$admin_array = array(
				'foo' => 'bar'
			);

			wp_localize_script( 'wc_connect_shipping_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_shipping_admin' );
		}
	}
}

