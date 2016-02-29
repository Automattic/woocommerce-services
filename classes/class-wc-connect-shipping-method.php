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

			// Set title and enabled to default values
			$this->title = $this->service->method_title;
			$this->enabled = 'yes';

			// Load form values from options, updating title and enabled if present
			$this->init_form_settings();

			// Process any changes to values present in $_POST
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

			// Note - we cannot hook admin_enqueue_scripts here because we need an instance id
			// and this constructor is not called with an instance id until after
			// admin_enqueue_scripts has already fired.  This is why WC_Connect_Loader
			// does it instead

		}

		protected function get_instance_form_settings_key() {
			return $this->plugin_id . $this->id . '_' . $this->instance_id . '_form_settings';
		}

		/**
		 * Restores any values persisted to the DB for this service instance
		 * and sets up both enabled and title for WC core to work properly
		 *
		 */
		protected function init_form_settings() {

			$form_settings = $this->get_form_settings();

			// We need to initialize the instance property $this->enabled
			// to "yes" or "no" (which WC expects instead of a boolean)

			if ( array_key_exists( 'enabled', $form_settings ) ) {

				$enabled = $form_settings['enabled'];
				if ( in_array( $enabled, array( "yes", "no" ) ) ) {
					$this->enabled = $enabled;
				} else if ( is_bool( $enabled ) ) {
					$this->enabled = $enabled ? "yes" : "no";
				}
			}

			// We also need to initialize the instance title ($this->title)
			// from the settings blob
			if ( array_key_exists( 'title', $form_settings ) ) {
				$this->title = $form_settings['title'];
			}

		}

		/**
		 * Returns the JSON schema for the form from the settings for this service
		 *
		 * Used by WC_Connect_Loader to embed the form schema in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_schema() {

			return $this->service->service_settings;

		}

		/**
		 * Returns the settings for this service (e.g. for use in the form or for
		 * sending to the rate request endpoint
		 *
		 * Used by WC_Connect_Loader to embed the form schema in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_settings() {

			$form_settings = get_option( $this->get_instance_form_settings_key(), array() );
			return $form_settings;

		}

		/**
		 * Handle the settings form submission.
		 *
		 * This method will pass the settings values off to the WCC server for validation.
		 *
		 * If the WooCommerce Connect server doesn't like one of the settings, we
		 * won't save anything but will return the error message(s) from the server
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

			return update_option( $this->get_instance_form_settings_key(), $settings );
		}

		public function calculate_shipping( $package = array() ) {

			require_once( plugin_basename( 'class-wc-connect-api-client.php' ) );

			$response = WC_Connect_API_Client::get_shipping_rates( $this->get_form_settings(), $package );

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

			// TODO log error if get_shipping_rates fails
		}

		public function admin_options() {
			?>
				<div id="wc-connect-admin-container"></div>
			<?php
		}

	}
}

