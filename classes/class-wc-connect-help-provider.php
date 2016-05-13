<?php

if ( ! class_exists( 'WC_Connect_Help_Provider' ) ) {

	class WC_Connect_Help_Provider {

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @array
		 */
		protected $fieldsets;

		public function __construct( WC_Connect_Service_Settings_Store $service_settings_store ) {

			$this->service_settings_store = $service_settings_store;
			$this->help_sections = array();

			add_filter( 'woocommerce_admin_status_tabs', array( $this, 'status_tabs' ) );
			add_action( 'woocommerce_admin_status_content_connect', array( $this, 'page' ) );

			$this->add_fieldset(
				'health',
				_x( "Health", "This section displays the overall health of WooCommerce Connect and the things it depends on", "woocommerce" ),
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
			return array(
					(object) array(
						'key' => 'woocommerce_health_items',
						'title' => __( 'WooCommerce' ),
						'type' => 'indicators',
						'items' => array(
							'woocommerce_indicator_1' => (object) array(
								'id' => 'woocommerce_indicator_1',
								'state' => 'super-green',
								'state_message' => 'WooCommerce is very much Good to Go',
								'last_updated' => 'Sometime recently'
							)
						)
					),
					(object) array(
						'key' => 'jetpack_health_items',
						'title' => __( 'Jetpack' ),
						'type' => 'indicators',
						'items' => array(
							'jetpack_indicator_1' => (object) array(
								'id' => 'jetpack_indicator_1',
								'state' => 'super-duper-green',
								'state_message' => 'Jetpack is also very much Good to Go',
								'last_updated' => 'Sometime recently'
							)
						)
					),
					(object) array(
						'key' => 'wcc_health_items',
						'title' => __( 'WooCommerce Connect' ),
						'type' => 'indicators',
						'items' => array(
							'wcc_indicator_1' => (object) array(
								'id' => 'wcc_indicator_1',
								'state' => 'super-duper-duper-green',
								'state_message' => 'WooCommerce Connect is also very very much Good to Go',
								'last_updated' => 'Sometime recently'
							)
						)
					)
				);
		}

		protected function get_services_items() {
			return array();
		}

		protected function get_debug_items() {
			return array();
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
					"state" => (object) array(
						"type" => "string",
						"default" => "bad"
					),
					"state_message" => (object) array(
						"type" => "string",
						"default" => "TBD",
					),
					"last_updated" => (object) array(
						"type" => "string",
						"default" => "Yesterday"
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

					// TODO - support other types like toggles, textareas
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
				// since that is all form layout items should have in them
				$items = array();
				foreach( $fieldset[ 'items' ] as $fieldsetitem ) {
					$items[] = (object) array(
						'key' => $fieldsetitem->key,
						'type' => $fieldsetitem->type
					);
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
				'callbackURL'  => '', // TODO
				'nonce'        => '', // TODO
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
