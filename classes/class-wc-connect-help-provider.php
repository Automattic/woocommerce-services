<?php

if ( ! class_exists( 'WC_Connect_Help_Provider' ) ) {

	class WC_Connect_Help_Provider {

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @array
		 */
		protected $fieldsets;

		public function __construct( WC_Connect_Service_Schemas_Store $service_schemas_store,
			WC_Connect_Service_Settings_Store $service_settings_store,
			WC_Connect_Logger $logger ) {

			$this->service_schemas_store = $service_schemas_store;
			$this->service_settings_store = $service_settings_store;
			$this->logger = $logger;
			$this->help_sections = array();

			add_filter( 'woocommerce_admin_status_tabs', array( $this, 'status_tabs' ) );
			add_action( 'woocommerce_admin_status_content_connect', array( $this, 'page' ) );

			$this->add_fieldset(
				'health',
				_x( 'Health', 'This section displays the overall health of WooCommerce Connect and the things it depends on', 'woocommerce' ),
				$this->get_health_items()
			);

			$this->add_fieldset(
				'services',
				__( 'Services', 'woocommerce' ),
				$this->get_services_items()
			);

			$this->add_fieldset(
				'debug',
				__( 'Debug', 'woocommerce' ),
				$this->get_debug_items()
			);

			$this->add_fieldset(
				'support',
				__( 'Support', 'woocommerce' ),
				$this->get_support_items()
			);

		}

		protected function get_health_items() {
			$health_items = array();

			// WooCommerce
			// Only one of the following should present
			// Check that WooCommerce is at least 2.6 or higher (feature-plugin only)
			// Check that WooCommerce base_country is set
			$base_country = WC()->countries->get_base_country();
			if ( version_compare( WC()->version, WOOCOMMERCE_CONNECT_MINIMUM_WOOCOMMERCE_VERSION, "<" ) ) {
				$health_item = $this->build_indicator(
					'woocommerce_indicator',
					'notice',
					'indicator-error',
					sprintf(
						__( 'WooCommerce %s or higher is required (You are running %s)', 'woocommerce' ),
						WOOCOMMERCE_CONNECT_MINIMUM_WOOCOMMERCE_VERSION,
						WC()->version
					),
					''
				);
			} else if ( empty( $base_country ) ) {
				$health_item = $this->build_indicator(
					'woocommerce_indicator',
					'notice',
					'indicator-error',
					__( 'Please set Base Location in WooCommerce Settings > General' ),
					''
				);
			} else {
				$health_item = $this->build_indicator(
					'woocommerce_indicator',
					'checkmark-circle',
					'indicator-success',
					sprintf(
						__( 'WooCommerce %s is configured correctly', 'woocommerce' ),
						WC()->version
					),
					''
				);
			}
			$health_items[] = (object) array(
				'key' => 'woocommerce_health_items',
				'title' => __( 'WooCommerce', 'woocommerce' ),
				'type' => 'indicators',
				'items' => array(
					'woocommerce_indicator' => $health_item
				)
			);

			// Jetpack
			// Only one of the following should present
			// Check that Jetpack is active
			// Check that Jetpack is connected
			include_once ( ABSPATH . 'wp-admin/includes/plugin.php' ); // required for is_plugin_active
			if ( method_exists( 'Jetpack', 'is_development_mode' ) && method_exists( 'Jetpack', 'is_active' ) ) {
				$is_connected = Jetpack::is_development_mode() ? true : Jetpack::is_active();
			} else {
				$is_connected = false;
			}
			if ( ! is_plugin_active( 'jetpack/jetpack.php' ) ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'indicator-error',
					sprintf(
						__( 'Please install and activate the Jetpack plugin, version %s or higher', 'woocommerce' ),
						WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION
					),
					''
				);
			} else if ( version_compare( JETPACK__VERSION, WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION, "<" ) ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'indicator-error',
					sprintf(
						__( 'Jetpack %s or higher is required (You are running %s)', 'woocommerce' ),
						WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION,
						JETPACK__VERSION
					),
					''
				);
			} else if ( ! $is_connected ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'indicator-error',
					__( 'Please connect Jetpack to WordPress.com', 'woocommerce' ),
					''
				);
			} else if ( Jetpack::is_staging_site() ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'indicator-warning',
					__( 'This is a Jetpack staging site', 'woocommerce' ),
					''
				);
			} else {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'checkmark-circle',
					'indicator-success',
					sprintf(
						__( 'Jetpack %s is connected and working correctly', 'woocommerce' ),
						JETPACK__VERSION
					),
					''
				);
			}
			$health_items[] = (object) array(
				'key' => 'jetpack_health_items',
				'title' => __( 'Jetpack', 'woocommerce' ),
				'type' => 'indicators',
				'items' => array(
					'jetpack_indicator' => $health_item
				)
			);

			// Lastly, do the WooCommerce Connect health check
			// Check that we have schema
			// Check that we are able to talk to the WooCommerce Connect server
			$schemas = $this->service_schemas_store->get_service_schemas();
			$last_fetch_timestamp = $this->service_schemas_store->get_last_fetch_timestamp();
			if ( ! is_null( $last_fetch_timestamp ) ) {
				$last_fetch_timestamp_formatted = sprintf(
					_x( 'Last updated %s ago', '%s = human-readable time difference', 'woocommerce' ),
					human_time_diff( $last_fetch_timestamp )
				);
			} else {
				$last_fetch_timestamp = '';
			}
			if ( is_null( $schemas ) ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'indicator-error',
					__( 'No service data available', 'woocommerce' ),
					''
				);
			} else if ( is_null( $last_fetch_timestamp ) ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'indicator-warning',
					__( 'Service data was found, but may be out of date', 'woocommerce' ),
					''
				);
			} else if ( $last_fetch_timestamp < time() - WOOCOMMERCE_CONNECT_SCHEMA_AGE_ERROR_THRESHOLD ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'indicator-error',
					__( 'Service data was found, but is more than three days old', 'woocommerce' ),
					$last_fetch_timestamp_formatted
				);
			} else if ( $last_fetch_timestamp < time() - WOOCOMMERCE_CONNECT_SCHEMA_AGE_WARNING_THRESHOLD ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'indicator-warning',
					__( 'Service data was found, but is more than one day old', 'woocommerce' ),
					$last_fetch_timestamp_formatted
				);
			} else {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'checkmark-circle',
					'indicator-success',
					__( 'Service data is up-to-date', 'woocommerce' ),
					$last_fetch_timestamp_formatted
				);
			}

			$health_items[] =	(object) array(
				'key' => 'wcc_health_items',
				'title' => __( 'WooCommerce Connect Service Data', 'woocommerce' ),
				'type' => 'indicators',
				'items' => array(
					'wcc_indicator' => $health_item
				)
			);

			return $health_items;
		}

		protected function get_services_items() {
			$service_items = array();

			$enabled_services = $this->service_settings_store->get_enabled_services();

			if ( empty( $enabled_services ) ) {
				$service_items[] =	(object) array(
					'key' => 'wcc_services_empty',
					'type' => 'text',
					'class' => 'form_text_body_copy',
					'value' => __( 'No services have been enabled', 'woocommerce' )
				);

				return $service_items;
			}

			foreach ( (array) $enabled_services as $enabled_service ) {
				$indicator_key = "{$enabled_service->method_id}_{$enabled_service->instance_id}_1";
				$last_failed_request_timestamp = intval( get_option( 'wc_connect_last_rate_request_failure', -1 ) );

				$service_settings_url = esc_url( add_query_arg(
					array(
						'page' => 'wc-settings',
						'tab' => 'shipping',
						'instance_id' => $enabled_service->instance_id
					),
					admin_url( 'admin.php' )
				) );

				$review_link = sprintf(
					wp_kses(
						__( '<a href="%s">Review service settings</a>', 'woocommerce' ),
						array(  'a' => array( 'href' => array() ) )
					),
					esc_url( $service_settings_url )
				);

				$edit_link = sprintf(
					wp_kses(
						__( '<a href="%s">Edit service settings</a>', 'woocommerce' ),
						array(  'a' => array( 'href' => array() ) )
					),
					esc_url( $service_settings_url )
				);

				$ago_edit_link = sprintf(
					wp_kses(
						_x( 'Request was made %1$s ago - <a href="%2$s">edit service settings</a>', 'e.g. two hours', 'woocommerce' ),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_html( human_time_diff( current_time( 'timestamp' ) - $last_failed_request_timestamp ) ),
					esc_url( $service_settings_url )
				);

				// Figure out if the service has any settings saved at all
				$service_settings = $this->service_settings_store->get_service_settings( $enabled_service->method_id, $enabled_service->instance_id );
				error_log( "service settings:" );
				error_log( print_r( $service_settings, true ) );
				if ( empty( $service_settings ) ) {
					$indicator = $this->build_indicator(
						$indicator_key,
						'notice',
						'indicator-error',
						__( 'Setup for this service has not yet been completed', 'woocommerce' ),
						$edit_link
					);
				} else if ( -1 === $last_failed_request_timestamp ) {
					$indicator = $this->build_indicator(
						$indicator_key,
						'notice',
						'indicator-warning',
						__( 'No rate requests have yet been made for this service', 'woocommerce' ),
						$review_link
					);
				} else if ( 0 === $last_failed_request_timestamp ) {
					$indicator = $this->build_indicator(
						$indicator_key,
						'checkmark-circle',
						'indicator-success',
						__( 'The most recent rate request was successful', 'woocommerce' ),
						$edit_link
					);
				} else {
					$indicator = $this->build_indicator(
						$indicator_key,
						'notice',
						'indicator-error',
						__( 'The most recent rate request failed', 'woocommerce' ),
						$ago_edit_link
					);
				}

				$items_key = "{$enabled_service->method_id}_{$enabled_service->instance_id}_items";
				$items_title = sprintf(
					_x( '%s (%s Shipping Zone)', 'e.g. US Post Office (Domestic Shipping Zone)', 'woocommerce' ),
					$enabled_service->title,
					$enabled_service->zone_name
				);

				$service_items[] = (object) array(
					'key' => $items_key,
					'title' => $items_title,
					'type' => 'indicators',
					'items' => array(
						$indicator_key => $indicator
					)
				);
			}

			return $service_items;
		}

		/**
		 * Gets the last 10 lines from the WooCommerce Connect log, if it exists
		 */
		protected function get_debug_log_data() {
			$data = new stdClass;
			$data->key = '';
			$data->file = '';
			$data->tail = array();

			if ( method_exists( 'WC_Admin_Status', 'scan_log_files' ) ) {
				$logs = WC_Admin_Status::scan_log_files();

				foreach ( $logs as $log_key => $log_file ) {
					if ( "wc-connect-" === substr( $log_key, 0, 11 ) ) {
						$complete_log = file( WC_LOG_DIR . $log_file );
						$data->key = $log_key;
						$data->file = $log_file;
						$data->tail = array_slice( $complete_log, -10 );
					}
				}
			}

			return $data;
		}

		protected function get_debug_items() {
			$debug_items = array();

			// add debug on/off boolean
			$debug_items[] = (object) array(
				'key' => 'wcc_debug_on',
				'title' => 'Debug Logging',
				'type' => 'boolean',
				'true_text' => __( 'Enabled', 'woocommerce' ),
				'false_text' => __( 'Disabled', 'woocommerce' ),
				'description' => '',
				'value' => $this->logger->is_logging_enabled(),
				'save_on_toggle' => true
			);

			// add connect log tail
			$log_data = $this->get_debug_log_data();
			$log_tail_line_count = count( $log_data->tail );
			if ( $log_tail_line_count < 1 ) {
				$description = '';
				$log_tail = __( 'Log is empty', 'woocommerce' );
			} else {
				$url = add_query_arg(
					array(
						'page' => 'wc-status',
						'tab' => 'logs',
						'log_file' => $log_data->key
					),
					admin_url( 'admin.php' )
				);
				$description = sprintf(
					wp_kses(
						__( 'Last %d entries <a href="%s">Show full log</a>', 'woocommerce' ),
						array(  'a' => array( 'href' => array() ) ) ),
					$log_tail_line_count,
					esc_url( $url )
				);
				$log_tail = implode( $log_data->tail, '' );
			}

			$debug_items[] = (object) array(
				'key' => 'wcc_debug_log_tail',
				'title' => __( 'Debug Log', 'woocommerce' ),
				'type' => 'textarea',
				'description' => $description,
				'readonly' => true,
				'value' => $log_tail
			);

			return $debug_items;
		}

		protected function get_support_items() {
			return array();
		}

		protected function add_fieldset( $section_slug, $title, $items ) {

			$this->fieldsets[ $section_slug ] = array(
				'title' => $title,
				'items' => $items
			);

		}

		protected function build_indicator( $id, $icon, $class, $message, $last_updated ) {

			return (object) array(
				'id' => $id,
				'icon' => $icon,
				'class' => $class,
				'message' => $message,
				'last_updated' => $last_updated
			);

		}

		/**
		 * Filters the WooCommerce System Status Tabs to add connect
		 *
		 * @param array $tabs
		 * @return array
		 */
		public function status_tabs( $tabs ) {

			if ( ! is_array( $tabs ) ) {
				$tabs = array();
			}
			$tabs[ 'connect' ] = _x( 'WooCommerce Connect', 'The WooCommerce Connect brandname', 'woocommerce' );
			return $tabs;

		}

		protected function get_indicator_schema() {

			return (object) array(
				"properties" => (object) array(
					"id" => (object) array(
						"type" => "string"
					),
					"icon" => (object) array(
						"type" => "string",
						"default" => "notice"
					),
					"class" => (object) array(
						"type" => "string",
						"default" => ""
					),
					"message" => (object) array(
						"type" => "string",
						"default" => "",
					),
					"last_updated" => (object) array(
						"type" => "string",
						"default" => ""
					)
				)
			);

		}

		/**
		 * Returns the form schema object for the entire help page
		 *
		 * @return object
		 */
		protected function get_form_schema() {

			$form_properties = array();
			$form_definitions = array();

			// Iterate over the items in each fieldset to build a list of properties
			foreach ( $this->fieldsets as $fieldset ) {
				foreach ( $fieldset[ 'items' ] as $fieldsetitem ) {

					if ( 'indicators' === $fieldsetitem->type ) {
						$form_properties[ $fieldsetitem->key ] = array(
							'title' => $fieldsetitem->title,
							'type' => 'object',
							'definition' => $fieldsetitem->key . '_definitions',
							'items' => $this->get_indicator_schema()
						);

						foreach ( $fieldsetitem->items as $item ) {
							$form_definitions[ $fieldsetitem->key . '_definitions' ][] = (object) array(
								'id' => $item->id
							);
						}
					}

					if ( 'text' === $fieldsetitem->type ) {
						$form_properties[ $fieldsetitem->key ] = array(
							'type' => 'string', // text is a layout concept, not a schema concept
						);
					}

					if ( 'textarea' === $fieldsetitem->type ) {
						$form_properties[ $fieldsetitem->key ] = array(
							'title' => $fieldsetitem->title,
							'type' => 'string', // textarea is a layout concept, not a schema concept
							'description' => property_exists( $fieldsetitem, 'description' ) ? $fieldsetitem->description : '',
						);
					}

					if ( 'boolean' === $fieldsetitem->type ) {
						$form_properties[ $fieldsetitem->key ] = array(
							'title' => $fieldsetitem->title,
							'type' => 'boolean',
							'trueText' => property_exists( $fieldsetitem, 'true_text' ) ? $fieldsetitem->true_text : '',
							'falseText' => property_exists( $fieldsetitem, 'false_text' ) ? $fieldsetitem->false_text : '',
							'description' => property_exists( $fieldsetitem, 'description' ) ? $fieldsetitem->description : '',
							'saveOnToggle' => property_exists( $fieldsetitem, 'save_on_toggle' ) ? $fieldsetitem->save_on_toggle : false,
						);
					}
				}
			}

			return (object) array(
				"type" => "object",
				"required" => array(),
				// these definitions enumerate the specific indicator IDs for each
				"definitions" => $form_definitions,
				"properties" => $form_properties
			);
		}

		/**
		 * Returns the form layout array for the entire help page
		 * Iterates over $this->fieldsets to build the form layout
		 *
		 * @return array
		 */
		protected function get_form_layout() {

			$form_layout = array();

			foreach ( $this->fieldsets as $fieldset ) {

				// Filter the fieldset's items to only include key and type
				// and possibly readonly since that is all form layout items
				// should have in them
				$items = array();
				foreach( $fieldset[ 'items' ] as $fieldsetitem ) {
					$item = array(
						'key' => $fieldsetitem->key,
						'type' => $fieldsetitem->type
					);
					if ( property_exists( $fieldsetitem, 'readonly' ) ) {
						$item['readonly'] = $fieldsetitem->readonly;
					}
					if ( property_exists( $fieldsetitem, 'class' ) ) {
						$item['class'] = $fieldsetitem->class;
					}
					$items[] = (object) $item;
				}

				$form_layout[] = (object) array(
					'title' => $fieldset[ 'title' ],
					'type'  => 'fieldset',
					'items' => $items
				);
			}

			return $form_layout;

		}

		/**
		 * Returns the data bootstrap for the help page
		 *
		 * @return array
		 */
		protected function get_form_data() {

			// Iterate over the fieldsets, extracting the settable items

			$form_data = array();

			foreach ( $this->fieldsets as $fieldset ) {
				foreach ( $fieldset[ 'items' ] as $fieldsetitem ) {
					if ( 'indicators' === $fieldsetitem->type ) {
						$form_data[ $fieldsetitem->key ] = $fieldsetitem->items;
					} else {
						$form_data[ $fieldsetitem->key ] = $fieldsetitem->value;
					}
				}
			}

			return $form_data;

		}

		/**
		 * Localizes the bootstrap, enqueues the script and styles for the help page
		 */
		public function page() {

			$form_schema = new stdClass();
			$form_schema->properties = new stdClass();

			$admin_array = array(
				'storeOptions' => $this->service_settings_store->get_shared_settings(),
				'formSchema'   => $this->get_form_schema(),
				'formLayout'   => $this->get_form_layout(),
				'formData'     => $this->get_form_data(),
				'callbackURL'  => get_rest_url( null, "/wc/v1/connect/self-help" ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
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
