<?php

if ( ! class_exists( 'WC_Connect_Shipping_Method' ) ) {

	class WC_Connect_Shipping_Method extends WC_Shipping_Method {

		/**
		 * @var object A reference to a the fetched properties of the service
		 */
		protected $service_schema = null;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

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
			do_action( 'wc_connect_service_init', $this, $id_or_instance_id );

			if ( ! $this->service_schema ) {
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
				$this->id = $this->service_schema->id;
				$this->method_title = $this->service_schema->method_title;
				$this->method_description = $this->service_schema->method_description;
				$this->supports = array(
					'shipping-zones',
					'instance-settings'
				);

				// Set title to default value
				$this->title = $this->service_schema->method_title;

				// Load form values from options, updating title if present
				$this->init_form_settings();

				// Note - we cannot hook admin_enqueue_scripts here because we need an instance id
				// and this constructor is not called with an instance id until after
				// admin_enqueue_scripts has already fired.  This is why WC_Connect_Loader
				// does it instead
			}
		}

		public function get_service_schema() {

			return $this->service_schema;

		}

		public function set_service_schema( $service_schema ) {

			$this->service_schema = $service_schema;

		}

		public function get_service_settings_store() {

			return $this->service_settings_store;

		}

		public function set_service_settings_store( $service_settings_store ) {

			$this->service_settings_store = $service_settings_store;

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


		/**
		 * Restores any values persisted to the DB for this service instance
		 * and sets up title for WC core to work properly
		 *
		 */
		protected function init_form_settings() {

			$form_settings = $this->get_service_settings();

			// We need to initialize the instance title ($this->title)
			// from the settings blob
			if ( property_exists( $form_settings, 'title' ) ) {
				$this->title = $form_settings->title;
			}

		}

		/**
		 * Returns selected WooCommerce settings for the form
		 *
		 * Used by WC_Connect_Loader to embed the settings in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_woocommerce_settings() {
			return array(
				'currency_symbol' => html_entity_decode( get_woocommerce_currency_symbol() ),
				'dimension_unit' => strtolower( get_option( 'woocommerce_dimension_unit' ) ),
				'weight_unit' => strtolower( get_option( 'woocommerce_weight_unit' ) )
			);
		}

		/**
		 * Returns the JSON schema for the form from the settings for this service
		 *
		 * Used by WC_Connect_Loader to embed the form schema in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_schema() {
			return $this->service_schema->service_settings;
		}

		/**
		 * Returns the form layout array for this service.
		 *
		 * Used by WC_Connect_Loader to embed the form layout in the page for JS to consume
		 *
		 * @return array
		 */
		public function get_form_layout() {
			return $this->service_schema->form_layout;
		}

		/**
		 * Returns the settings for this service (e.g. for use in the form or for
		 * sending to the rate request endpoint
		 *
		 * Used by WC_Connect_Loader to embed the form schema in the page for JS to consume
		 *
		 * @return object
		 */
		public function get_service_settings() {
			$service_settings = $this->service_settings_store->get_service_settings( $this->id, $this->instance_id );
			if ( ! is_object( $service_settings ) ) {
				$service_settings = new stdClass();
			}
			return $service_settings;
		}

		/**
		 * Returns the callback URL for the settings form for this service
		 *
		 * @return string
		 */
		public function get_form_callback_URL() {
			return $this->service_settings_store->get_wc_api_callback_url();
		}

		/**
		 * Returns the nonce for the settings form for this service
		 *
		 * @return string
		 */
		public function get_form_nonce() {
			return $this->service_settings_store->get_wc_api_callback_nonce( $this->id, $this->instance_id );
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
				array(
					'id'               => $this->id,
					'instance'         => $this->instance_id,
					'service_settings' => $this->get_service_settings(),
				),
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
				'wooCommerceSettings' => $this->get_woocommerce_settings(),
				'formSchema'  => $this->get_form_schema(),
				'formLayout'  => $this->get_form_layout(),
				'formData'    => $this->get_service_settings(),
				'id'          => $this->id,
				'instance'    => $this->instance_id,
				'callbackURL' => $this->get_form_callback_URL(),
				'nonce'       => $this->get_form_nonce(),
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
