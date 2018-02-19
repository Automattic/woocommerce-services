<?php

if ( ! class_exists( 'WC_Connect_Help_View' ) ) {

	class WC_Connect_Help_View {

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

			add_filter( 'woocommerce_admin_status_tabs', array( $this, 'status_tabs' ) );
			add_action( 'woocommerce_admin_status_content_connect', array( $this, 'page' ) );
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
					'is-error',
					sprintf(
						__( 'WooCommerce %s or higher is required (You are running %s)', 'woocommerce-services' ),
						WOOCOMMERCE_CONNECT_MINIMUM_WOOCOMMERCE_VERSION,
						WC()->version
					),
					''
				);
			} else if ( empty( $base_country ) ) {
				$health_item = $this->build_indicator(
					'woocommerce_indicator',
					'notice',
					'is-error',
					__( 'Please set Base Location in WooCommerce Settings > General', 'woocommerce-services' ),
					''
				);
			} else {
				$health_item = $this->build_indicator(
					'woocommerce_indicator',
					'checkmark-circle',
					'is-success',
					sprintf(
						__( 'WooCommerce %s is configured correctly', 'woocommerce-services' ),
						WC()->version
					),
					''
				);
			}
			$health_items[] = (object) array(
				'key' => 'woocommerce_health_items',
				'title' => __( 'WooCommerce', 'woocommerce-services' ),
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
			$is_connected = WC_Connect_Jetpack::is_active() || WC_Connect_Jetpack::is_development_mode();
			if ( ! is_plugin_active( 'jetpack/jetpack.php' ) ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'is-error',
					sprintf(
						__( 'Please install and activate the Jetpack plugin, version %s or higher', 'woocommerce-services' ),
						WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION
					),
					''
				);
			} else if ( version_compare( JETPACK__VERSION, WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION, "<" ) ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'is-error',
					sprintf(
						__( 'Jetpack %s or higher is required (You are running %s)', 'woocommerce-services' ),
						WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION,
						JETPACK__VERSION
					),
					''
				);
			} else if ( ! $is_connected ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'is-error',
					__( 'Jetpack is not connected to WordPress.com. Make sure the Jetpack plugin is installed, activated, and connected.', 'woocommerce-services' ),
					''
				);
			} else if ( WC_Connect_Jetpack::is_staging_site() ) {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'notice',
					'is-warning',
					__( 'This is a Jetpack staging site', 'woocommerce-services' ),
					''
				);
			} else {
				$health_item = $this->build_indicator(
					'jetpack_indicator',
					'checkmark-circle',
					'is-success',
					sprintf(
						__( 'Jetpack %s is connected and working correctly', 'woocommerce-services' ),
						JETPACK__VERSION
					),
					''
				);
			}
			$health_items[] = (object) array(
				'key' => 'jetpack_health_items',
				'title' => __( 'Jetpack', 'woocommerce-services' ),
				'type' => 'indicators',
				'items' => array(
					'jetpack_indicator' => $health_item
				)
			);

			// Lastly, do the WooCommerce Services health check
			// Check that we have schema
			// Check that we are able to talk to the WooCommerce Services server
			$schemas = $this->service_schemas_store->get_service_schemas();
			$last_fetch_timestamp = $this->service_schemas_store->get_last_fetch_timestamp();
			$refresh_link = sprintf(
				wp_kses(
					'<a href="%s">' . __( 'Refresh', 'woocommerce-services' ) . '</a>',
					array( 'a' => array( 'href' => array() ) )
				),
				add_query_arg(
					array(
						'page' => 'wc-status',
						'tab' => 'connect',
						'refresh' => 'true',
					),
					admin_url( 'admin.php' )
				)
			);
			if ( ! is_null( $last_fetch_timestamp ) ) {
				$last_fetch_timestamp_formatted = sprintf(
					_x( 'Last updated %1s ago. %2s', '%1s = human-readable time difference, %2s = refresh url', 'woocommerce-services' ),
					human_time_diff( $last_fetch_timestamp ),
					$refresh_link
				);
			} else {
				$last_fetch_timestamp = '';
				$last_fetch_timestamp_formatted = $refresh_link;
			}
			if ( is_null( $schemas ) ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'is-error',
					__( 'No service data available', 'woocommerce-services' ),
					$last_fetch_timestamp_formatted
				);
			} else if ( is_null( $last_fetch_timestamp ) ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'is-warning',
					__( 'Service data was found, but may be out of date', 'woocommerce-services' ),
					$last_fetch_timestamp_formatted
				);
			} else if ( $last_fetch_timestamp < time() - WOOCOMMERCE_CONNECT_SCHEMA_AGE_ERROR_THRESHOLD ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'is-error',
					__( 'Service data was found, but is more than three days old', 'woocommerce-services' ),
					$last_fetch_timestamp_formatted
				);
			} else if ( $last_fetch_timestamp < time() - WOOCOMMERCE_CONNECT_SCHEMA_AGE_WARNING_THRESHOLD ) {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'notice',
					'is-warning',
					__( 'Service data was found, but is more than one day old', 'woocommerce-services' ),
					$last_fetch_timestamp_formatted
				);
			} else {
				$health_item = $this->build_indicator(
					'wcc_indicator',
					'checkmark-circle',
					'is-success',
					__( 'Service data is up-to-date', 'woocommerce-services' ),
					$last_fetch_timestamp_formatted
				);
			}

			$health_items[] =	(object) array(
				'key' => 'wcc_health_items',
				'title' => __( 'WooCommerce Services Data', 'woocommerce-services' ),
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
				$service_items[] = (object) array(
					'key' => 'wcc_services_empty',
					'type' => 'text',
					'class' => 'form-text-body-copy',
					'value' => __( 'No services have been enabled', 'woocommerce-services' )
				);

				return $service_items;
			}

			foreach ( (array) $enabled_services as $enabled_service ) {
				$indicator_key = "{$enabled_service->method_id}_{$enabled_service->instance_id}";
				$last_failed_request_timestamp = intval( WC_Connect_Options::get_shipping_method_option( 'failure_timestamp', -1, $enabled_service->method_id, $enabled_service->instance_id ) );

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
						__( '<a href="%s">Review service settings</a>', 'woocommerce-services' ),
						array(  'a' => array( 'href' => array() ) )
					),
					esc_url( $service_settings_url )
				);

				$edit_link = sprintf(
					wp_kses(
						__( '<a href="%s">Edit service settings</a>', 'woocommerce-services' ),
						array(  'a' => array( 'href' => array() ) )
					),
					esc_url( $service_settings_url )
				);

				$ago_edit_link = sprintf(
					wp_kses(
						_x(
							'Request was made %1$s ago - check logs below or <a href="%2$s">edit service settings</a>',
							'e.g. two hours',
							'woocommerce-services'
						),
						array( 'a' => array( 'href' => array() ) )
					),
					esc_html( human_time_diff( $last_failed_request_timestamp ) ),
					esc_url( $service_settings_url ),
					esc_url( $service_settings_url )
				);

				// Figure out if the service has any settings saved at all
				$service_settings = $this->service_settings_store->get_service_settings( $enabled_service->method_id, $enabled_service->instance_id );
				if ( empty( $service_settings ) ) {
					$indicator = $this->build_indicator(
						$indicator_key,
						'notice',
						'is-error',
						__( 'Setup for this service has not yet been completed', 'woocommerce-services' ),
						$edit_link
					);
				} else if ( -1 === $last_failed_request_timestamp ) {
					$indicator = $this->build_indicator(
						$indicator_key,
						'notice',
						'is-warning',
						__( 'No rate requests have yet been made for this service', 'woocommerce-services' ),
						$review_link
					);
				} else if ( 0 === $last_failed_request_timestamp ) {
					$indicator = $this->build_indicator(
						$indicator_key,
						'checkmark-circle',
						'is-success',
						__( 'The most recent rate request was successful', 'woocommerce-services' ),
						$edit_link
					);
				} else {
					$indicator = $this->build_indicator(
						$indicator_key,
						'notice',
						'is-error',
						__( 'The most recent rate request failed', 'woocommerce-services' ),
						$ago_edit_link
					);
				}

				$items_key = "{$enabled_service->method_id}_{$enabled_service->instance_id}_items";
				$subtitle = sprintf(
					__( '%s Shipping Zone', 'woocommerce-services' ),
					$enabled_service->zone_name
				);

				$service_items[] = (object) array(
					'key' => $items_key,
					'title' => $enabled_service->title,
					'subtitle' => $subtitle,
					'type' => 'indicators',
					'items' => array(
						$indicator_key => $indicator
					)
				);
			}

			return $service_items;
		}

		/**
		 * Gets the last 10 lines from the WooCommerce Services log by feature, if it exists
		 */
		protected function get_debug_log_data( $feature = '' ) {
			$data       = new stdClass;
			$data->key  = '';
			$data->file = null;
			$data->tail = array();

			if ( ! method_exists( 'WC_Admin_Status', 'scan_log_files' ) ) {
				return $data;
			}

			$log_prefix = 'wc\-services';

			if ( ! empty( $feature ) ) {
				$log_prefix .= '\-' . $feature;
			}

			$logs             = WC_Admin_Status::scan_log_files();
			$latest_file_date = 0;

			foreach ( $logs as $log_key => $log_file ) {
				if ( ! preg_match( '/' . $log_prefix . '\-[0-9a-f]{32}\-log/', $log_key ) ) {
					continue;
				}

				$log_file_path = WC_LOG_DIR . $log_file;
				$file_date     = filemtime( $log_file_path );

				if ( $latest_file_date < $file_date ) {
					$latest_file_date = $file_date;
					$data->file       = $log_file_path;
					$data->key        = $log_key;
				}
			}

			if ( null !== $data->file ) {
				$complete_log = file( $data->file );
				$data->tail   = array_slice( $complete_log, -10 );
			}

			return $data;
		}

		protected function add_log_view( $title, $feature = '' ) {
			// add connect log tail
			$log_data   = $this->get_debug_log_data( $feature );
			$line_count = count( $log_data->tail );

			if ( $line_count < 1 ) {

				$description = '';
				$log_tail    = __( 'Log is empty', 'woocommerce-services' );

			} else {

				$url = add_query_arg(
					array(
						'page'     => 'wc-status',
						'tab'      => 'logs',
						'log_file' => $log_data->key
					),
					admin_url( 'admin.php' )
				);

				$description = sprintf(
					wp_kses(
						__( 'Last %d entries <a href="%s">Show full log</a>', 'woocommerce-services' ),
						array(  'a' => array( 'href' => array() ) ) ),
					$line_count,
					esc_url( $url )
				);

				$log_tail = implode( $log_data->tail, '' );

			}

			return (object) array(
				'key'         => 'wcc_' . $feature . '_log_tail',
				'title'       => $title,
				'type'        => 'textarea',
				'description' => $description,
				'readonly'    => true,
				'value'       => $log_tail,
			);
		}

		protected function get_debug_items() {
			$debug_items = array();

			// add logging on/off boolean
			$debug_items[] = (object) array(
				'key'            => 'wcc_logging_on',
				'title'          => 'Logging',
				'type'           => 'boolean',
				'true_text'      => __( 'Enabled', 'woocommerce-services' ),
				'false_text'     => __( 'Disabled', 'woocommerce-services' ),
				'description'    => __( 'Write diagnostic messages to log files. Helpful when contacting support.', 'woocommerce-services' ),
				'value'          => $this->logger->is_logging_enabled(),
				'save_on_toggle' => true,
			);

			// add debug on/off boolean
			$debug_items[] = (object) array(
				'key'            => 'wcc_debug_on',
				'title'          => 'Debug',
				'type'           => 'boolean',
				'true_text'      => __( 'Enabled', 'woocommerce-services' ),
				'false_text'     => __( 'Disabled', 'woocommerce-services' ),
				'description'    => __( 'Display troubleshooting information on the Cart and Checkout pages.', 'woocommerce-services' ),
				'value'          => $this->logger->is_debug_enabled(),
				'save_on_toggle' => true,
			);

			$debug_items[] = $this->add_log_view( __( 'Shipping Log', 'woocommerce-services' ), 'shipping' );
			$debug_items[] = $this->add_log_view( __( 'Taxes Log', 'woocommerce-services' ), 'taxes' );
			$debug_items[] = $this->add_log_view( __( 'Other Log', 'woocommerce-services' ) );

			return $debug_items;
		}

		protected function get_support_items() {
			$support_items = array();

			$doc_link = sprintf(
				wp_kses(
					__( 'Our team is here for you. View our <a href="%1$s">support docs</a> or <a href="%2$s">open a support ticket</a>.', 'woocommerce-services' ),
					array(  'a' => array( 'href' => array() ) )
				),
				esc_url( 'https://docs.woocommerce.com/document/woocommerce-services/' ),
				esc_url( 'https://woocommerce.com/my-account/create-a-ticket/' )
			);

			$support_items[] = (object) array(
				'key' => 'wcc_support_item',
				'type' => 'text',
				'class' => 'form-text-body-copy',
				'title' => __( 'Need help?', 'woocommerce-services' ),
				'value' => $doc_link
			);

			return $support_items;
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
			$tabs[ 'connect' ] = _x( 'WooCommerce Services', 'The WooCommerce Services brandname', 'woocommerce-services' );
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
						$indicator_schema = $this->get_indicator_schema();

						$form_properties[ $fieldsetitem->key ] = array(
							'title'      => $fieldsetitem->title,
							'type'       => 'object',
							'definition' => $fieldsetitem->key . '_definitions',
							'items'      => $indicator_schema,
							'properties' => $indicator_schema->properties,
						);

						if ( property_exists( $fieldsetitem, 'subtitle' ) ) {
							$form_properties[ $fieldsetitem->key ][ 'subtitle' ] = $fieldsetitem->subtitle;
						}

						foreach ( $fieldsetitem->items as $item ) {
							$form_definitions[ $fieldsetitem->key . '_definitions' ][] = (object) array(
								'id' => $item->id
							);
						}
					}

					if ( 'text' === $fieldsetitem->type ) {
						$form_properties[ $fieldsetitem->key ] = array(
							'title' => property_exists( $fieldsetitem, 'title' ) ? $fieldsetitem->title : '',
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
				// and possibly readonly and class since that is all form layout items
				// should have in them

				// Note that non interactive form elements, like type text, may also
				// pass a title through layout since they do not exist in the schema
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
					if ( 'text' === $fieldsetitem->type && property_exists( $fieldsetitem, 'title' ) ) {
						$item['title'] = $fieldsetitem->title;
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
						$form_data[ $fieldsetitem->key ] = reset( $fieldsetitem->items );
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
			if ( isset( $_GET['refresh'] ) && 'true' === $_GET['refresh'] ) {
				$this->service_schemas_store->fetch_service_schemas_from_connect_server();
				$url = remove_query_arg( 'refresh' );
				wp_safe_redirect( $url );
			}

			$this->help_sections = array();

			$this->add_fieldset(
				'health',
				_x( 'Health', 'This section displays the overall health of WooCommerce Services and the things it depends on', 'woocommerce-services' ),
				$this->get_health_items()
			);

			$this->add_fieldset(
				'services',
				__( 'Services', 'woocommerce-services' ),
				$this->get_services_items()
			);

			$this->add_fieldset(
				'debug',
				__( 'Debug', 'woocommerce-services' ),
				$this->get_debug_items()
			);

			$this->add_fieldset(
				'support',
				__( 'Support', 'woocommerce-services' ),
				$this->get_support_items()
			);

			?>
				<h2>
					<?php _e( 'WooCommerce Services Status', 'woocommerce-services' ); ?>
				</h2>
			<?php

			do_action( 'enqueue_wc_connect_script', 'wc-connect-admin-help', array(
				'storeOptions'       => $this->service_settings_store->get_store_options(),
				'formSchema'         => $this->get_form_schema(),
				'formLayout'         => $this->get_form_layout(),
				'formData'           => $this->get_form_data(),
				'formType'           => 'self-help',
				'noticeDismissed'    => true,
			) );

			do_action( 'enqueue_wc_connect_script', 'wc-connect-admin-test-print', array(
				'storeOptions'       => $this->service_settings_store->get_store_options(),
				'paperSize'          => $this->service_settings_store->get_preferred_paper_size(),
			) );
		}

	}

}
