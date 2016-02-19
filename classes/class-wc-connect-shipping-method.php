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
			$this->init_settings();
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		/**
		 * Handle the settings form submission.
		 *
		 * This method will pass the settings values off to the WCC server for validation.
		 *
		 * @return bool
		 */
		public function process_admin_options() {

			$settings = $_POST;
			unset( $settings['subtab'], $settings['_wpnonce'], $settings['_wp_http_referer'] );

			// TODO: validate settings with WCC server
			$result = true;

			// TODO: check for errors, use $this->add_error() ?

			$this->settings = $settings;

			return update_option( $this->get_option_key(), apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings ) );

		}

		public function calculate_shipping( $package = array() ) {

			require_once( plugin_basename( 'class-wc-connect-api-client.php' ) );

			// TODO - fetch settings when that layer is defined
			$response = WC_Connect_API_Client::get_shipping_rates( array(), $package );

			if ( ! is_wp_error( $response ) ) {
				if ( array_key_exists( $this->id, $response ) ) {
					$rates = $response[$this->id];

					foreach ( (array) $rates as $rate ) {
						$rate_to_add = array(
							'id' => $this->id,
							'label' => $rate['title'],
							'cost' => $rate['rate'],
							'calc_tax' => 'per_item'
						);

						$this->add_rate( $rate_to_add );
					}
				}
			}
		}

		public function admin_options() {
			global $hide_save_button;
			$hide_save_button = true;

			?>
			<div id="wc-connect-admin-container">
				React goes here
			</div>
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
				'formSchema' => isset( $this->service_settings ) ? $this->service_settings : null
			);

			wp_localize_script( 'wc_connect_shipping_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_shipping_admin' );
		}
	}
}

