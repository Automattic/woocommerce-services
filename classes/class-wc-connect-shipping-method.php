<?php

if ( ! class_exists( 'WC_Connect_Shipping_Method' ) ) {

	class WC_Connect_Shipping_Method extends WC_Shipping_Method {

		/**
		 * @var object A reference to a the fetched properties of the service
		 */
		protected $service = null;

		public function __construct( $id_or_instance_id = null ) {

			// If $arg looks like a number, treat it as an instance_id
			// Otherwise, treat it as a (method) id (e.g. wc_connect_usps)
			$this->instance_id = null;
			if ( is_numeric( $id_or_instance_id ) ) {
				$this->instance_id = absint( $id_or_instance_id );
				$this->service = WC_Connect_Services_Store::getInstance()->get_service_by_instance_id( $this->instance_id );
			} else if ( ! empty( $id_or_instance_id ) ) {
				$this->service = WC_Connect_Services_Store::getInstance()->get_service_by_id( $id_or_instance_id );
			} else {
				throw new Exception( 'Attempted to construct a default WC_Connect_Shipping_Method' );
			}

			$this->id = $this->service->id;
			$this->method_title = $this->service->method_title;
			$this->method_description = $this->service->method_description;

			$this->supports = array(
				'shipping-zones',
				'instance-settings'
			);

			$this->enabled		         = $this->get_option( 'enabled' );
			$this->title 		         = $this->get_option( 'title' );

			error_log( "{$this->id} enabled = {$this->enabled}" );

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		}

		public function get_instance_form_fields() {

			// TODO - generate defaults from the service json schema so WC can use it to generate
			// defaults for wc_settings_api for this instance

			return array(
				'enabled' => array(
					'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable/Disable', 'woocommerce' ),
					'default' 		=> 'yes'
				),
				'title' => array(
					'title' 		=> __( 'Title', 'woocommerce' ),
					'type' 			=> 'text',
					'description' 	=> __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'		=> $this->method_title,
					'desc_tip'		=> true,
				)
			);
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

			// Validate settings with WCC server
			$result = WC_Connect_API_Client::validate_service_settings( $this->id, $settings );

			if ( is_wp_error( $result ) ) {

				$this->add_error( $result->get_error_message() );

				return false;

			}

		}

		public function calculate_shipping( $package = array() ) {

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
			} else {
				error_log( "unable to fetch rates for {$this->id}" );
				error_log( $response->get_error_message() );
			}
		}

		/* TEMP DO NOT COMMIT public function admin_options() {
			global $hide_save_button;
			$hide_save_button = true;

			?>
				<div id="wc-connect-admin-container"></div>
			<?php
		} */

		public function admin_enqueue_scripts( $hook ) {
			if ( 'woocommerce_page_wc-settings' !== $hook ) {
				return;
			}

			if ( ! isset( $_GET['section'] ) || 'wc_connect_shipping_method' !== $_GET['section'] ) {
				return;
			}

			wp_register_script( 'wc_connect_shipping_admin', plugins_url( 'build/bundle.js', dirname( __FILE__ ) ), array() );

			$admin_array = array(
				'formSchema' => empty( $this->service_settings ) ? null : $this->service_settings,
				'formData'   => empty( $this->settings ) ? array() : $this->settings,
			);

			wp_localize_script( 'wc_connect_shipping_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_shipping_admin' );
		}
	}
}

