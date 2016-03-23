<?php

if ( ! class_exists( 'WC_Connect_Shipping_Method' ) ) {

	class WC_Connect_Shipping_Method extends WC_Shipping_Method {

		/**
		 * @var object A reference to a the fetched properties of the service
		 */
		protected $service = null;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		public function __construct( $id_or_instance_id = null ) {

			// If $arg looks like a number, treat it as an instance_id
			// Otherwise, treat it as a (method) id (e.g. wc_connect_usps)
			if ( is_numeric( $id_or_instance_id ) ) {
				$this->instance_id = absint( $id_or_instance_id );
			} else {
				$this->instance_id = null;
			}

			/**
			 * Provide a dependency injection point for each shipping method.
			 *
			 * WooCommerce core instantiates shipping method with only a string ID
			 * or a numeric instance ID. We depend on more than that, so we need
			 * to provide a hook for our plugin to inject dependencies into each
			 * shipping method instance.
			 *
			 * @param WC_Connect_Shipping_Method $this
			 * @param int|string                 $id_or_instance_id
			 */
			do_action( 'wc_connect_shipping_method_init', $this, $id_or_instance_id );

			if ( ! $this->service ) {
				$this->log(
					'Error. A WC_Connect_Shipping_Method was constructed without an id or instance_id',
					__FUNCTION__
				);
				$this->id = 'wc_connect_uninitialized_shipping_method';
				$this->method_title = '';
				$this->method_description = '';
				$this->supports = array();
				$this->title = '';
			} else {
				$this->id = $this->service->id;
				$this->method_title = $this->service->method_title;
				$this->method_description = $this->service->method_description;
				$this->supports = array(
					'shipping-zones',
					'instance-settings'
				);

				// Set title to default value
				$this->title = $this->service->method_title;

				// Load form values from options, updating title if present
				$this->init_form_settings();

				// Process any changes to values present in $_POST
				add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );

				// Note - we cannot hook admin_enqueue_scripts here because we need an instance id
				// and this constructor is not called with an instance id until after
				// admin_enqueue_scripts has already fired.  This is why WC_Connect_Loader
				// does it instead
			}
		}

		public function get_service() {

			return $this->service;

		}

		public function set_service( $service ) {

			$this->service = $service;

		}

		public function get_logger() {

			return $this->logger;

		}

		public function set_logger( WC_Connect_Logger $logger ) {

			$this->logger = $logger;

		}

		public function get_api_client() {

			return $this->api_client;

		}

		public function set_api_client( WC_Connect_API_Client $api_client ) {

			$this->api_client = $api_client;

		}

		/**
		 * Logging helper.
		 *
		 * Avoids calling methods on an undefined object if no logger was
		 * injected during the init action in the constructor.
		 *
		 * @see WC_Connect_Logger::log()
		 * @param string|WP_Error $message
		 * @param string $context
		 */
		protected function log( $message, $context = '' ) {

			$logger = $this->get_logger();

			if ( is_a( $logger, 'WC_Connect_Logger' ) ) {

				$logger->log( $message, $context );

			}

		}

		protected function get_instance_form_settings_key() {
			return $this->plugin_id . $this->id . '_' . $this->instance_id . '_form_settings';
		}

		/**
		 * Restores any values persisted to the DB for this service instance
		 * and sets up title for WC core to work properly
		 *
		 */
		protected function init_form_settings() {

			$form_settings = $this->get_form_settings();

			// We need to initialize the instance title ($this->title)
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
		 * Returns the form layout array for this service.
		 *
		 * Used by WC_Connect_Loader to embed the form layout in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_layout() {
			return $this->service->form_layout;
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
		 * Retrieve posted settings, whitelisted with the form schema.
		 *
		 * @param array $posted Optional. Defaults to $_POST.
		 * @return array Posted settings found in schema definition.
		 */
		public function get_posted_form_settings( $posted = null ) {

			if ( is_null( $posted ) ) {
				$posted = $_POST;
			}

			if ( empty( $posted ) ) {
				return array();
			}

			$settings = array();
			$schema   = $this->get_form_schema();

			// Whitelist settings sent to the validation endpoint using the schema
			if ( isset( $schema->properties ) ) {
				foreach ( (array) $schema->properties as $field_name => $properties ) {
					// Special handling is needed to turn checkboxes back into booleans
					// since our form returns 'on' for checkboxes if they are checked and omits
					// the key if they are not checked
					if ( 'boolean' === $properties->type ) {
						$settings[ $field_name ] = isset( $posted[ $field_name ] );
					} elseif ( isset( $posted[ $field_name ] ) ) {
						$settings[ $field_name ] = $posted[ $field_name ];
					}
				}
			}

			return $settings;

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

			$settings = $this->get_posted_form_settings();

			// Validate settings with WCC server
			$response_body = $this->api_client->validate_service_settings( $this->id, $settings );

			if ( is_wp_error( $response_body ) ) {
				// TODO - handle multiple error messages when the validation endpoint can return them
				$this->add_error( $response_body->get_error_message() );

				// TODO - when the validation endpoint is ready, return false on failures
				// but go ahead and save for now to make development and testing possible
				// return false;

			}

			// TODO - so, returning false above doesn't actually prevent a
			// "Your settings have been saved." message from appearing.  At
			// first glance, WooCommerce may not prevent that.  Need to dig further.

			return update_option( $this->get_instance_form_settings_key(), $settings );
		}

		/**
		 * Determine if a package's destination is valid enough for a rate quote.
		 *
		 * @param array $package
		 * @return bool
		 */
		public function is_valid_package_destination( $package ) {

			$country  = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
			$postcode = isset( $package['destination']['postcode'] ) ? $package['destination']['postcode'] : '';
			$state    = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';

			// Ensure that Country is specified
			if ( empty( $country ) ) {
				return false;
			}

			// Validate Postcode
			if ( ! WC_Validation::is_postcode( $postcode, $country ) ) {
				return false;
			}

			// Validate State
			$valid_states = WC()->countries->get_states( $country );

			if ( $valid_states && ! array_key_exists( $state, $valid_states ) ) {
				return false;
			}

			return true;

		}

		public function calculate_shipping( $package = array() ) {

			if ( ! $this->is_valid_package_destination( $package ) ) {

				return $this->log(
					sprintf( 'Package destination failed validation. Skipping %s rate request.', $this->id ),
					__FUNCTION__
				);

			}

			// TODO: Request rates for all WooCommerce Connect powered methods in
			// the current shipping zone to avoid each method making an independent request
			$services = array(
				array_merge( $this->get_form_settings(),
					array(
						'id' => $this->id,
						'instance' => $this->instance_id
					)
				)
			);

			$response_body = $this->api_client->get_shipping_rates( $services, $package );

			if ( is_wp_error( $response_body ) ) {
				$this->log(
					sprintf(
						'Error. Unable to get shipping rate(s) for %s instance id %d.',
						$this->id,
						$this->instance_id
					),
					__FUNCTION__
				);

				$this->log( $response_body, __FUNCTION__ );
				return;
			}

			if ( ! property_exists( $response_body, 'rates' ) ) {
				return;
			}
			$instances = $response_body->rates;

			foreach ( (array) $instances as $instance ) {
				if ( ! property_exists( $instance, 'rates' ) ) {
					continue;
				}

				foreach ( (array) $instance->rates as $rate_idx => $rate ) {
					$rate_to_add = array(
						'id'       => sprintf( '%s:%d:%d', $instance->id, $instance->instance, $rate_idx ),
						'label'    => $rate->title,
						'cost'     => $rate->rate,
						'calc_tax' => 'per_item'
					);

					$this->add_rate( $rate_to_add );
				}
			}
		}

		public function localize_and_enqueue_form_script() {

			$admin_array = array(
				'formSchema' => $this->get_form_schema(),
				'formLayout' => $this->get_form_layout(),
				'formData'   => $this->get_form_settings(),
			);

			wp_localize_script( 'wc_connect_shipping_admin', 'wcConnectData', $admin_array );

			wp_enqueue_script( 'wc_connect_shipping_admin' );
			wp_enqueue_style( 'wc_connect_shipping_admin' );
		}

		public function admin_options() {

			$this->localize_and_enqueue_form_script();

			?>
				<div id="wc-connect-admin-container"></div>
			<?php
		}

	}
}

