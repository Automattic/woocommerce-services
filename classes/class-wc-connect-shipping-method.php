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
			parent::__construct( $id_or_instance_id );

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
				$this->debug(
					'Error. A WC_Connect_Shipping_Method was constructed without an id or instance_id',
					__FUNCTION__
				);
				$this->id = 'wc_connect_uninitialized_shipping_method';
				$this->method_title = '';
				$this->method_description = '';
				$this->supports = array();
				$this->title = '';
			} else {
				$this->id = $this->service_schema->method_id;
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
		 * @see WC_Connect_Logger::debug()
		 * @param string|WP_Error $message
		 * @param string $context
		 */
		protected function debug( $message, $context = '' ) {

			$logger = $this->get_logger();

			if ( is_a( $logger, 'WC_Connect_Logger' ) ) {

				$logger->debug( $message, $context );

			}

		}

		protected function error( $message, $context = '' ) {
			$logger = $this->get_logger();
			if ( is_a( $logger, 'WC_Connect_Logger' ) ) {
				$logger->error( $message, $context );
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

			if ( ! property_exists( $service_settings, 'services' ) ) {
				return $service_settings;
			}

			return $service_settings;
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

		private function lookup_product( $package, $product_id ) {
			foreach ( $package[ 'contents' ] as $item ) {
				if ( $item[ 'product_id' ] === $product_id ) {
					return $item[ 'data' ];
				}
			}

			return false;
		}

		private function filter_preset_boxes( $preset_id ) {
			return is_string( $preset_id );
		}

		private function add_fallback_rate( $service_settings ) {
			if ( ! property_exists( $service_settings, 'fallback_rate' ) || 0 >= $service_settings->fallback_rate ) {
				return;
			}

			$rate_to_add = array(
				'id'        => self::format_rate_id( 'fallback', $this->id, 0 ),
				'label'     => self::format_rate_title( $this->service_schema->carrier_name ),
				'cost'      => $service_settings->fallback_rate,
			);

			$this->add_rate( $rate_to_add );
		}

		public function calculate_shipping( $package = array() ) {

			if ( ! $this->is_valid_package_destination( $package ) ) {
				return;
			}

			$service_settings = $this->get_service_settings();
			$settings_keys    = get_object_vars( $service_settings );

			if ( empty( $settings_keys ) ) {
				$this->debug(
					sprintf(
						'Service settings empty. Skipping %s rate request (instance id %d).',
						$this->id,
						$this->instance_id
					),
					__FUNCTION__
				);
				return;
			}

			// TODO: Request rates for all WooCommerce Services powered methods in
			// the current shipping zone to avoid each method making an independent request
			$services = array(
				array(
					'id'               => $this->service_schema->id,
					'instance'         => $this->instance_id,
					'service_settings' => $service_settings,
				),
			);

			$custom_boxes = $this->service_settings_store->get_packages();
			$predefined_boxes = $this->service_settings_store->get_predefined_packages_for_service( $this->service_schema->id );
			$predefined_boxes = array_values( array_filter( $predefined_boxes, array( $this, 'filter_preset_boxes' ) ) );

			$response_body = $this->api_client->get_shipping_rates( $services, $package, $custom_boxes, $predefined_boxes );

			if ( is_wp_error( $response_body ) ) {
				$this->error(
					sprintf(
						'Error. Unable to get shipping rate(s) for %s instance id %d.',
						$this->id,
						$this->instance_id
					),
					__FUNCTION__
				);

				$this->set_last_request_failed();

				$this->error( $response_body, __FUNCTION__ );
				$this->add_fallback_rate( $service_settings );
				return;
			}

			if ( ! property_exists( $response_body, 'rates' ) ) {
				$this->set_last_request_failed();
				$this->add_fallback_rate( $service_settings );
				return;
			}

			$instances = $response_body->rates;

			foreach ( (array) $instances as $instance ) {
				if ( property_exists( $instance, 'error' ) ) {
					$this->error( $instance->error, __FUNCTION__ );
					$this->set_last_request_failed();
				}

				if ( ! property_exists( $instance, 'rates' ) ) {
					continue;
				}

				$packaging_lookup = $this->service_settings_store->get_package_lookup();

				foreach ( (array) $instance->rates as $rate_idx => $rate ) {
					$package_names = array();
					foreach ( $rate->packages as $rate_package ) {
						$package_format = '';
						$items = array();

						foreach ( $rate_package->items as $package_item ) {
							/** @var WC_Product $product */
							$product = $this->lookup_product( $package, $package_item->product_id );
							if ( $product ) {
								$items[] = $product->get_title();
							}
						}

						if ( ! property_exists( $rate_package, 'box_id' ) ) {
							$package_format = __( 'Unknown package (%s)', 'woocommerce-services' );
						} else if ( 'individual' === $rate_package->box_id ) {
							$package_format = __( 'Individual packaging (%s)', 'woocommerce-services' );
						} else if ( isset( $packaging_lookup[ $rate_package->box_id ] )
							&& isset( $packaging_lookup[ $rate_package->box_id ][ 'name' ] ) ) {
							$package_format = $packaging_lookup[ $rate_package->box_id ][ 'name' ] . ' (%s)';
						}

						$package_names[] = sprintf( $package_format, implode( ', ', $items ) );
					}

					$packaging_info = implode( ', ', $package_names );

					$rate_to_add = array(
						'id'        => self::format_rate_id( $instance->id, $instance->instance, $rate_idx ),
						'label'     => self::format_rate_title( $rate->title ),
						'cost'      => $rate->rate,
						'meta_data' => array(
							'wc_connect_packages' => $rate->packages,
							__( 'Packaging', 'woocommerce-services' ) => $packaging_info
						),
					);

					$this->add_rate( $rate_to_add );
				}
			}

			if ( 0 === count( $this->rates ) ) {
				$this->add_fallback_rate( $service_settings );
			} else {
				$this->set_last_request_failed( 0 );
			}

			$this->update_last_rate_request_timestamp();
		}

		public function update_last_rate_request_timestamp() {
			$previous_timestamp = WC_Connect_Options::get_option( 'last_rate_request' );
			if ( false === $previous_timestamp ||
				( time() - HOUR_IN_SECONDS ) > $previous_timestamp ) {
				WC_Connect_Options::update_option( 'last_rate_request', time() );
			}
		}

		public function set_last_request_failed( $timestamp = null ) {
			if ( is_null( $timestamp ) ) {
				$timestamp = time();
			}

			WC_Connect_Options::update_shipping_method_option( 'failure_timestamp', $timestamp, $this->id, $this->instance_id );
		}

		public function admin_options() {
			// hide WP native save button on settings page
			global $hide_save_button;
			$hide_save_button = true;

			do_action( 'wc_connect_service_admin_options', $this->id, $this->instance_id );
		}

		public static function format_rate_id( $method_id, $instance, $rate_idx ) {
			return sprintf( '%s:%d:%d', $method_id, $instance, $rate_idx );
		}

		public static function format_rate_title( $rate_title ) {
			$formatted_title = wp_kses(
				html_entity_decode( $rate_title ),
				array(
					'sup' => array(),
					'del' => array(),
					'small' => array(),
					'em' => array(),
					'i' => array(),
					'strong' => array(),
				)
			);

			return $formatted_title;
		}

	}
}
