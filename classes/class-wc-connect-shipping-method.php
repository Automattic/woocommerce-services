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

		/**
		 * Store validation errors in property for later retrieval.
		 *
		 * @var WP_Error
		 */
		protected $package_validation_errors;

		/**
		 * Cache of destinations which have already been validated.
		 *
		 * @var array
		 */
		protected $validated_package_destinations = array();

		public function __construct( $id_or_instance_id = null ) {
			parent::__construct( $id_or_instance_id );

			// If $arg looks like a number, treat it as an instance_id,
			// otherwise, treat it as a (method) id (e.g. wc_connect_usps).
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
				$this->log_error(
					'Error. A WC_Connect_Shipping_Method was constructed without an id or instance_id',
					__FUNCTION__
				);
				$this->id                 = 'wc_connect_uninitialized_shipping_method';
				$this->method_title       = '';
				$this->method_description = '';
				$this->supports           = array();
				$this->title              = '';
			} else {
				$this->id                 = $this->service_schema->method_id;
				$this->method_title       = $this->service_schema->method_title;
				$this->method_description = $this->service_schema->method_description;
				$this->supports           = array(
					'shipping-zones',
					'instance-settings',
				);

				// Set title to default value.
				$this->title = $this->service_schema->method_title;

				// Load form values from options, updating title if present.
				$this->init_form_settings();

				// Note - we cannot hook admin_enqueue_scripts here because we need an instance id
				// and this constructor is not called with an instance id until after
				// admin_enqueue_scripts has already fired.  This is why WC_Connect_Loader
				// does it instead.
			}
			$this->package_validation_errors = new WP_Error();
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
		 * @param string          $context
		 */
		protected function log( $message, $context = '' ) {

			$logger = $this->get_logger();

			if ( is_a( $logger, 'WC_Connect_Logger' ) ) {

				$logger->log( $message, $context );

			}
		}

		protected function log_error( $message, $context = '' ) {
			$logger = $this->get_logger();
			if ( is_a( $logger, 'WC_Connect_Logger' ) ) {
				$logger->error( $message, $context );
			}
		}

		/**
		 * Restores any values persisted to the DB for this service instance
		 * and sets up title for WC core to work properly
		 */
		protected function init_form_settings() {

			$form_settings = $this->get_service_settings();

			// We need to initialize the instance title ($this->title)
			// from the settings blob.
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
		 * @param array $package Current Package.
		 * @return bool
		 */
		public function is_valid_package_destination( $package ) {
			$country   = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
			$postcode  = isset( $package['destination']['postcode'] ) ? $package['destination']['postcode'] : '';
			$state     = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';
			$countries = WC()->countries->get_countries();

			$destination_key = md5( wp_json_encode( $package['destination'] ) );

			if ( isset( $this->validated_package_destinations[ $destination_key ] ) ) {
				// We are using a cache because this method could be called multiple times and we don't want to show double errors.
				return $this->validated_package_destinations[ $destination_key ];
			}

			// Ensure that Country is specified.
			if ( empty( $country ) ) {
				$this->package_validation_errors->add(
					'country_required',
					esc_html__( 'A country is required', 'woocommerce-services' ),
					array( 'id' => 'country' )
				);
			}

			// Validate Postcode.
			if ( ! WC_Validation::is_postcode( $postcode, $country ) ) {
				$fields = WC()->countries->get_address_fields( $country, '' );
				if ( empty( $postcode ) ) {
					$this->package_validation_errors->add(
						'postcode_required',
						sprintf(
							/* Translators: %1$s: Localized label for Zip/postal code, %2$s: Country name */
							esc_html__(
								'A %1$s is required for %2$s.',
								'woocommerce-services'
							),
							'<strong>' . esc_html( $fields['postcode']['label'] ) . '</strong>',
							'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
						),
						array( 'id' => 'postcode' )
					);
				} else {
					$this->package_validation_errors->add(
						'postcode_validation',
						sprintf(
						/* Translators: %1$s: Localized label for Zip/postal code, %2$s: submitted zip/postal code, %3$s: Country name */
							esc_html__(
								'%1$s %2$s is invalid for %3$s.',
								'woocommerce-services'
							),
							esc_html( $fields['postcode']['label'] ),
							'<strong>' . esc_html( $postcode ) . '</strong>',
							'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
						),
						array( 'id' => 'postcode' )
					);
				}
			}

			// Validate State.
			$valid_states = WC()->countries->get_states( $country );

			if ( $valid_states && ! isset( $valid_states[ $state ] ) ) {
				if ( empty( $state ) ) {
					$fields = WC()->countries->get_address_fields( $country, '' );
					$this->package_validation_errors->add(
						'state_required',
						sprintf(
						/* Translators: %1$s: Localized label for province/region/state, %2$s: Country name */
							esc_html__(
								'A %1$s is required for %2$s.',
								'woocommerce-services'
							),
							'<strong>' . esc_html( $fields['state']['label'] ) . '</strong>',
							'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
						),
						array( 'id' => 'state' )
					);
				} else {
					$this->package_validation_errors->add(
						'state_validation',
						sprintf(
						/* Translators: %1$s: State name, %2$s: Country name */
							esc_html__(
								'State %1$s is invalid for %2$s.',
								'woocommerce-services'
							),
							'<strong>' . esc_html( $state ) . '</strong>',
							'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
						),
						array( 'id' => 'state' )
					);
				}
			}
			$is_valid = ! $this->package_validation_errors->has_errors();
			$this->validated_package_destinations[ $destination_key ] = $is_valid;
			return $is_valid;
		}

		/**
		 * Return WP_Error object which may have validation errors.
		 *
		 * @return WP_Error
		 */
		public function get_package_validation_errors() {
			return $this->package_validation_errors;
		}

		private function lookup_product( $package, $product_id ) {
			foreach ( $package['contents'] as $item ) {
				if ( $item['product_id'] === $product_id || $item['variation_id'] === $product_id ) {
					return $item['data'];
				}
			}

			return false;
		}

		private function filter_preset_boxes( $preset_id ) {
			return is_string( $preset_id );
		}

		private function check_and_handle_response_error( $response_body, $service_settings ) {
			if ( is_wp_error( $response_body ) ) {
				$this->debug(
					sprintf(
						'Request failed: %s',
						$response_body->get_error_message()
					),
					'error'
				);
				$this->log_error(
					sprintf(
						'Error. Unable to get shipping rate(s) for %s instance id %d.',
						$this->id,
						$this->instance_id
					),
					__FUNCTION__
				);

				$this->set_last_request_failed();

				$this->log_error( $response_body, __FUNCTION__ );
				$this->add_fallback_rate( $service_settings );
				return true;
			}

			if ( ! property_exists( $response_body, 'rates' ) ) {
				$this->debug( 'Response is missing `rates` property', 'error' );
				$this->set_last_request_failed();
				$this->add_fallback_rate( $service_settings );
				return true;
			}

			return false;
		}

		private function add_fallback_rate( $service_settings ) {
			if ( ! property_exists( $service_settings, 'fallback_rate' ) || 0 >= $service_settings->fallback_rate ) {
				return;
			}

			$this->debug( 'No rates found, adding fallback.', 'error' );

			$rate_to_add = array(
				'id'    => self::format_rate_id( 'fallback', $this->id, 0 ),
				'label' => self::format_rate_title( $this->service_schema->carrier_name ),
				'cost'  => $service_settings->fallback_rate,
			);

			$this->add_rate( $rate_to_add );
		}

		public function calculate_shipping( $package = array() ) {
			if ( ! WC_Connect_Functions::should_send_cart_api_request() ) {
				return;
			}

			$this->debug(
				sprintf(
					'WooCommerce Tax debug mode is on - to hide these messages, turn debug mode off in the <a href="%s" style="text-decoration: underline;">settings</a>.',
					admin_url( 'admin.php?page=wc-status&tab=connect' )
				)
			);

			if ( ! $this->is_valid_package_destination( $package ) ) {
				if ( WC_Connect_functions::is_cart() || WC_Connect_functions::is_checkout() || WC_Connect_functions::is_store_api_call() ) {
					foreach ( $this->package_validation_errors->errors as $code => $messages ) {
						foreach ( $messages as $message ) {
							// Using debug instead of regular notice because the error always shows before customer enters any shipping information.
							$this->debug( $message, 'error' );
						}
					}
				}
				return;
			}

			$service_settings = $this->get_service_settings();
			$settings_keys    = get_object_vars( $service_settings );

			if ( empty( $settings_keys ) ) {
				$this->log(
					sprintf(
						'Service settings empty. Skipping %s rate request (instance id %d).',
						$this->id,
						$this->instance_id
					),
					__FUNCTION__
				);
				return;
			}

			// TODO: Request rates for all WooCommerce Shipping & Tax powered methods in
			// the current shipping zone to avoid each method making an independent request.
			$services = array(
				array(
					'id'               => $this->service_schema->id,
					'instance'         => $this->instance_id,
					'service_settings' => $service_settings,
				),
			);

			$custom_boxes     = $this->service_settings_store->get_packages();
			$predefined_boxes = $this->service_settings_store->get_predefined_packages_for_service( $this->service_schema->id );
			$predefined_boxes = array_values( array_filter( $predefined_boxes, array( $this, 'filter_preset_boxes' ) ) );

			$cache_key     = sprintf(
				'wcs_rates_%s',
				md5( serialize( array( $services, $package, $custom_boxes, $predefined_boxes ) ) ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
			);
			$is_debug_mode = 'yes' === get_option( 'woocommerce_shipping_debug_mode', 'no' );
			$response_body = get_transient( $cache_key );
			$this->debug( false === $response_body ? 'Cache does not contain rates response' : 'Cache contains rates response' );
			if ( ! $is_debug_mode && false !== $response_body ) {
				$this->debug( 'Rates response retrieved from cache' );
			} else {
				$response_body = $this->api_client->get_shipping_rates( $services, $package, $custom_boxes, $predefined_boxes );
				if ( $this->check_and_handle_response_error( $response_body, $service_settings ) ) {
					return;
				}
				set_transient( $cache_key, $response_body, HOUR_IN_SECONDS );
			}

			$instances = $response_body->rates;

			foreach ( (array) $instances as $instance ) {
				if ( property_exists( $instance, 'error' ) ) {
					$this->log_error( $instance->error, __FUNCTION__ );
					$this->set_last_request_failed();
				}

				if ( ! property_exists( $instance, 'rates' ) ) {
					continue;
				}

				$packaging_lookup = $this->service_settings_store->get_package_lookup();

				foreach ( (array) $instance->rates as $rate_idx => $rate ) {
					$package_summaries = array();
					$service_ids       = array();

					$dimension_unit      = get_option( 'woocommerce_dimension_unit' );
					$weight_unit         = get_option( 'woocommerce_weight_unit' );
					$measurements_format = '(%s x %s x %s ' . $dimension_unit . ', %s ' . $weight_unit . ')';

					foreach ( $rate->packages as $rate_package ) {
						$service_ids[] = $rate_package->service_id;

						$item_product_ids = array();
						$item_by_product  = array();
						foreach ( $rate_package->items as $package_item ) {
							$item_product_ids[]                           = $package_item->product_id;
							$item_by_product[ $package_item->product_id ] = $package_item;
						}

						$product_summaries = array();
						$product_counts    = array_count_values( $item_product_ids );
						foreach ( $product_counts as $product_id => $count ) {
							/**
							 * WC Product.
							 *
							 * @var WC_product $product
							 */
							$product = $this->lookup_product( $package, $product_id );
							if ( is_a( $product, 'WC_Product' ) ) {
								$item_name           = $product->get_name();
								$item                = $item_by_product[ $product_id ];
								$item_measurements   = sprintf( $measurements_format, $item->length, $item->width, $item->height, $item->weight );
								$product_summaries[] =
									( $count > 1 ? sprintf( '<em>%d x</em> ', $count ) : '' ) .
									sprintf( '(ID: %d) <strong>%s</strong> %s', $product_id, esc_html( $item_name ), esc_html( $item_measurements ) );
							}
						}

						$package_measurements = '';

						if ( ! property_exists( $rate_package, 'box_id' ) ) {
							$package_name = __( 'Unknown package', 'woocommerce-services' );
						} elseif ( 'individual' === $rate_package->box_id ) {
							$package_name = __( 'Individual packaging', 'woocommerce-services' );
						} elseif (
							isset( $packaging_lookup[ $rate_package->box_id ] ) &&
							isset( $packaging_lookup[ $rate_package->box_id ]['name'] )
						) {
							$package_name         = $packaging_lookup[ $rate_package->box_id ]['name'];
							$package_measurements = sprintf(
								$measurements_format,
								$rate_package->length,
								$rate_package->width,
								$rate_package->height,
								$rate_package->weight
							);
						}

						$package_summaries[] = sprintf( '<strong>%s</strong> %s', $package_name, $package_measurements )
							. '<ul><li>' . implode( '</li><li>', $product_summaries ) . '</li></ul>';
					}

					$packaging_info  = implode( ', ', $package_summaries );
					$services_list   = implode( '-', array_unique( $service_ids ) );
					$box_packing_log = empty( $rate->box_packing_log ) ? array() : $rate->box_packing_log;

					$rate_to_add = array(
						// Make sure the rate ID is identifiable for extensions like Conditional Shipping and Payments.
						// The new format looks like: `wc_services_usps:1:pri_medium_flat_box_top`.
						'id'        => self::format_rate_id( $this->id, $instance->instance, $services_list ),
						'label'     => self::format_rate_title( $rate->title ),
						'cost'      => $rate->rate,
						'meta_data' => array(
							'wc_connect_packages'    => $rate->packages,
							__( 'Packaging', 'woocommerce-services' ) => $packaging_info,
							'wc_connect_packing_log' => $box_packing_log,
						),
					);

					if ( $this->logger->is_debug_enabled() ) {
						if ( 'fallback' === $services_list ) {
							// Notify the merchant when the fallback rate is added by the WCS server.
							$this->debug( 'No rates found, adding fallback.', 'error' );
						} else {
							$rate_debug  = '<strong>';
							$rate_debug .= sprintf(
								/* translators: 1: name of shipping service, 2: shipping rate (price) */
								__( 'Received rate: %1$s (%2$s)', 'woocommerce-services' ),
								$rate_to_add['label'],
								wc_price( $rate->rate )
							);
							$rate_debug .= '</strong><ul><li>' . implode( '</li><li>', $package_summaries ) . '</li></ul>';

							if ( ! empty( $box_packing_log ) ) {
								$rate_debug .= '<strong>' . __( 'Packing log:', 'woocommerce-services' ) . '</strong>';
								$rate_debug .= '<ul><li>' . implode( '</li><li>', array_map( 'esc_html', $box_packing_log ) ) . '</li></ul>';
							}

							$this->debug( $rate_debug, 'success' );
						}
					}

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
			// hide WP native save button on settings page.
			global $hide_save_button;
			$hide_save_button = true;

			do_action( 'wc_connect_service_admin_options', $this->id, $this->instance_id );
		}

		/**
		 * @param string $method_id
		 * @param int    $instance_id
		 * @param string $service_ids
		 *
		 * @return string
		 */
		public static function format_rate_id( $method_id, $instance_id, $service_ids ) {
			return sprintf( '%s:%d:%s', $method_id, $instance_id, $service_ids );
		}

		public static function format_rate_title( $rate_title ) {
			$formatted_title = wp_kses(
				html_entity_decode( $rate_title ),
				array(
					'sup'    => array(),
					'del'    => array(),
					'small'  => array(),
					'em'     => array(),
					'i'      => array(),
					'strong' => array(),
				)
			);

			return $formatted_title;
		}

		/**
		 * Log debug by printing it as notice.
		 *
		 * @param string $message Debug message.
		 * @param string $type    Notice type.
		 */
		public function debug( $message, $type = 'notice' ) {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing --- No input from $_POST is used as input.
			if ( WC_Connect_Functions::is_cart() || WC_Connect_Functions::is_checkout() || isset( $_POST['update_cart'] ) || WC_Connect_Functions::is_store_api_call() ) {
				$debug_message = sprintf( '%s (%s:%d)', $message, esc_html( $this->title ), $this->instance_id );
				$this->logger->debug( $debug_message, $type );
				$this->logger->log( $debug_message, __CLASS__ );
			}
		}

		/**
		 * Is this method available?
		 *
		 * @param array $package Package.
		 * @return bool
		 */
		public function is_available( $package ) {
			if ( ! parent::is_available( $package ) ) {
				return false;
			}

			if ( ! $this->matches_package_shipping_classes( $package ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Checks whether the shipping classes of all products in a package are
		 * actually supported by the method. If a single product has an un-supported class,
		 * the whole package will not be supported by the method.
		 *
		 * @param array $package The contents of a package.
		 * @return bool
		 */
		public function matches_package_shipping_classes( $package ) {
			$settings       = $this->get_service_settings();
			$method_classes = property_exists( $settings, 'shipping_classes' )
				? $settings->shipping_classes
				: array();

			// No checks needed if the method is not limited to certain classes.
			if ( empty( $method_classes ) ) {
				return true;
			}

			// Go through the cart contents and check if all products are supported.
			foreach ( $package['contents'] as $item ) {
				$shipping_class_id = $item['data']->get_shipping_class_id();

				if ( in_array( $shipping_class_id, $method_classes, true ) ) {
					continue;
				}

				if ( ! $this->logger->is_debug_enabled() ) {
					return false;
				}

				$message = 'Skipping the "%1$s" shipping method because %2$s (%3$s) does not match the shipping classes specified in the method settings (%4$s).';

				$product_class_name = 'No shipping class';
				if ( $shipping_class_id ) {
					$shipping_class = get_term_by( 'id', $shipping_class_id, 'product_shipping_class' );

					if ( $shipping_class ) {
						$product_class_name = $shipping_class->name;
					}
				}

				$method_classes = get_terms(
					array(
						'taxonomy'   => 'product_shipping_class',
						'hide_empty' => false,
						'include'    => $method_classes,
					)
				);

				if ( ! is_wp_error( $method_classes ) && ! empty( $method_classes ) ) {
					$class_names = implode( ', ', wp_list_pluck( $method_classes, 'name' ) );
				} else {
					$class_names = 'No shipping classes found';
				}

				$message = sprintf(
					$message,
					$this->title,
					$item['data']->get_title(),
					$product_class_name,
					$class_names
				);

				$this->debug( $message );

				return false;
			}

			return true;
		}
	}
}
