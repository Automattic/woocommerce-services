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

			$this->enabled = $this->get_option( 'enabled' );
			$this->title = $this->get_option( 'title' );

			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

			// Note - we cannot hook admin_enqueue_scripts here because we need an instance id
			// and this constructor is not called with an instance id until after
			// admin_enqueue_scripts has already fired.  This is why WC_Connect_Loader
			// does it instead

		}

		/**
		 * Returns the JSON schema for the form from the settings for this service
		 *
		 * The key for each field is prefixed with pluginid id _ so that the $_POST
		 * from the form has keys compatible with WC_Settings_API processing logic
		 *
		 * Used by WC_Connect_Loader to embed the form schema in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_schema_with_prefixed_keys() {

			$service_settings = $this->service->service_settings;
			$form_schema = $service_settings;

			$form_schema['properties'] = array();

			// TODO - handle nested settings? are they even a thing?

			foreach ( $service_settings['properties'] as $property_key => $property_values ) {
				$prefixed_key = $this->get_field_key( $property_key );
				$form_schema['properties'][ $prefixed_key ] = $property_values;
			}

			return $form_schema;
		}

		/**
		 * Uses the JSON schema for the form to assemble the settings for this service
		 * (e.g. for use in the form or for sending to the rate request endpoint
		 *
		 * The key for each field is prefixed with pluginid id _ so that the $_POST
		 * from the form has keys compatible with WC_Settings_API processing logic
		 *
		 * Used by WC_Connect_Loader to embed the form schema in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_settings_with_prefixed_keys() {

			$form_settings = array();

			// TODO - handle nested settings? are they even a thing?

			foreach ( $this->instance_settings as $setting_key => $setting_value ) {
				$prefixed_key = $this->get_field_key( $setting_key );
				$form_settings[ $prefixed_key ] = $setting_value;
			}

			return $form_settings;
		}

		/**
		 * Takes the JSON schema for the form and extracts defaults (and other fields)
		 * in a format and with values compatible with the WC_Settings_API
		 *
		 * Besides driving WC form generation (which we are overriding with our React
		 * based form), the WC_Settings_API uses these fields to provide defaults when
		 * setting up a new instance of a method, for example
		 *
		 * @return array
		 */
		public function get_instance_form_fields() {

			$service_settings = $this->service->service_settings;
			$fields = array();

			if ( ! is_object( $service_settings->properties ) ) {
				return $fields;
			}

			// TODO - handle nested settings - is that even a thing?

			foreach ( $service_settings->properties as $property ) {

				if ( ! is_object ( $service_settings->properties->property ) ) {
					continue;
				}

				// Special handling for WC boolean, which is weird
				$type = $property_values['type'];
				$default = $property_values['default'];
				if ( "boolean" == $type ) {
					$type = "checkbox";
					$default = $default ? "yes" : "no";
				}

				$fields[ $property_key ] = array(
					'type'    => $type,
					'title'   => $property_values['title'],
					'label'   => $property_values['description'],
					'default' => $default,
				);
			}

			return $fields;

		}


		/**
		 * Handle the settings form submission.
		 *
		 * This method will pass the settings values off to the WCC server for validation.
		 *
		 * The parent (WC_Shipping_Method) process_admin_options walks the instance form
		 * fields, and foreach key grabs the "field value" (from $_POST) using
		 * WC_Settings_API::get_field_key and saves it into the options for the instance
		 *
		 * Note: WC_Settings_API::get_field_value uses WC_Settings_API::get_field_key which
		 * expects $_POST keys which are based on, but not the same as the properties
		 * array keys returned from get_form_schema.  For example:
		 *
		 * enabled <==> woocommerce_wc_connect_usps_enabled
		 * title   <==> woocommerce_wc_connect_usps_title
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

		}

		public function calculate_shipping( $package = array() ) {

			$response = WC_Connect_API_Client::get_shipping_rates( $this->instance_settings, $package );

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

