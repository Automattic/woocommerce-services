<?php
/**
 * Plugin Name: WooCommerce Connect
 * Plugin URI: http://woothemes.com/
 * Description: WooCommerce Connect: Shipping, Simplified. Powered by Jetpack, this feature plugin provides a Shipping Zones compatible USPS shipping method for your WooCommerce powered store.
 * Author: Automattic
 * Author URI: http://woothemes.com/
 * Version: 0.2
 *
 * Copyright (c) 2016 Automattic
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Loader' ) ) {

	define( 'WOOCOMMERCE_CONNECT_MINIMUM_WOOCOMMERCE_VERSION', '2.6' );
	define( 'WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION', '3.9' );
	define( 'WOOCOMMERCE_CONNECT_SCHEMA_AGE_WARNING_THRESHOLD', DAY_IN_SECONDS );
	define( 'WOOCOMMERCE_CONNECT_SCHEMA_AGE_ERROR_THRESHOLD', 3 * DAY_IN_SECONDS );

	class WC_Connect_Loader {

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_REST_Connect_Services_Controller
		 */
		protected $rest_services_controller;

		/**
		 * @var WC_REST_Connect_Self_Help_Controller
		 */
		protected $rest_self_help_controller;

		/**
		 * @var WC_Connect_Service_Schemas_Validator
		 */
		protected $service_schemas_validator;

		/**
		 * @var WC_Connect_Help_Provider
		 */
		protected $help_provider;

		protected $services = array();

		protected $service_object_cache = array();

		static function load_tracks_for_activation_hooks() {
			require_once( plugin_basename( 'classes/class-wc-connect-logger.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-tracks.php' ) );
			$logger = new WC_Connect_Logger( new WC_Logger() );
			return new WC_Connect_Tracks( $logger );

		}

		static function plugin_activation() {
			$tracks = self::load_tracks_for_activation_hooks();
			$tracks->opted_in();
		}

		static function plugin_deactivation() {
			$tracks = self::load_tracks_for_activation_hooks();
			$tracks->opted_out();
			wp_clear_scheduled_hook( 'wc_connect_fetch_service_schemas' );
		}

		public function __construct() {
			add_action( 'woocommerce_init', array( $this, 'init' ) );
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

		public function get_service_schemas_store() {
			return $this->service_schemas_store;
		}

		public function set_service_schemas_store( WC_Connect_Service_Schemas_Store $schemas_store ) {
			$this->service_schemas_store = $schemas_store;
		}

		public function get_service_settings_store() {
			return $this->service_settings_store;
		}

		public function set_service_settings_store( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->service_settings_store = $settings_store;
		}

		public function get_tracks() {
			return $this->tracks;
		}

		public function set_tracks( WC_Connect_Tracks $tracks ) {
			$this->tracks = $tracks;
		}

		public function get_rest_services_controller() {
			return $this->rest_services_controller;
		}

		public function set_rest_services_controller( WC_REST_Connect_Services_Controller $rest_services_controller ) {
			$this->rest_services_controller = $rest_services_controller;
		}

		public function get_rest_self_help_controller() {
			return $this->rest_self_help_controller;
		}

		public function set_rest_self_help_controller( WC_REST_Connect_Self_Help_Controller $rest_self_help_controller ) {
			$this->rest_self_help_controller = $rest_self_help_controller;
		}

		public function get_service_schemas_validator() {
			return $this->service_schemas_validator;
		}

		public function set_service_schemas_validator( WC_Connect_Service_Schemas_Validator $validator ) {
			$this->service_schemas_validator = $validator;
		}

		public function get_help_provider() {
			return $this->help_provider;
		}

		public function set_help_provider( WC_Connect_Help_Provider $help_provider ) {
			$this->help_provider = $help_provider;
		}

		/**
		 * Bootstrap our plugin and hook into WP/WC core.
		 *
		 * @codeCoverageIgnore
		 */
		public function init() {

			$this->load_dependencies();
			$this->attach_hooks();
			$this->schedule_service_schemas_fetch();

		}

		/**
		 * Load all plugin dependencies.
		 */
		public function load_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-logger.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-service-schemas-validator.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-service-schemas-store.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-service-settings-store.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-tracks.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-help-provider.php' ) );

			$logger         = new WC_Connect_Logger( new WC_Logger() );
			$validator      = new WC_Connect_Service_Schemas_Validator();
			$api_client     = new WC_Connect_API_Client( $validator, $this );
			$schemas_store  = new WC_Connect_Service_Schemas_Store( $api_client, $logger );
			$settings_store = new WC_Connect_Service_Settings_Store( $schemas_store, $api_client, $logger );
			$tracks         = new WC_Connect_Tracks( $logger );
			$help_provider  = new WC_Connect_Help_Provider( $schemas_store, $settings_store, $logger );

			$this->set_logger( $logger );
			$this->set_api_client( $api_client );
			$this->set_service_schemas_validator( $validator );
			$this->set_service_schemas_store( $schemas_store );
			$this->set_service_settings_store( $settings_store );
			$this->set_tracks( $tracks );
			$this->set_help_provider( $help_provider );

			add_action( 'admin_init', array( $this, 'load_admin_dependencies' ) );
		}

		/**
		 * Load admin-only plugin dependencies.
		 */
		public function load_admin_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-debug-tools.php' ) );
			new WC_Connect_Debug_Tools( $this->api_client );
		}

		/**
		 * Hook plugin classes into WP/WC core.
		 */
		public function attach_hooks() {

			$schemas_store = $this->get_service_schemas_store();
			$schemas = $schemas_store->get_service_schemas();
			$settings_store = $this->get_service_settings_store();
			$rest_services_controller = $this->get_rest_services_controller();

			if ( $schemas ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
				add_action( 'wc_connect_service_init', array( $this, 'init_service' ), 10, 2 );
				add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
				add_action( 'wc_connect_service_admin_options', array( $this, 'localize_and_enqueue_service_script' ), 10, 2 );
				add_action( 'woocommerce_shipping_zone_method_added', array( $this, 'shipping_zone_method_added' ), 10, 3 );
				add_action( 'woocommerce_shipping_zone_method_deleted', array( $this, 'shipping_zone_method_deleted' ), 10, 3 );
				add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'shipping_zone_method_status_toggled' ), 10, 4 );
			}

			add_action( 'woocommerce_settings_saved', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );
			add_action( 'wc_connect_fetch_service_schemas', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );

		}

		/**
		 * Hook the REST API
		 * Note that we cannot load our controller until this time, because prior to
		 * rest_api_init firing, WP_REST_Controller is not yet defined
		 */
		public function rest_api_init() {
			$schemas_store = $this->get_service_schemas_store();
			$settings_store = $this->get_service_settings_store();

			//////////////////////////////////////////////////////////////////////////////
			// TODO - Remove this when woocommerce/pull/10435 lands
			if ( ! class_exists( 'WP_REST_Controller' ) ) {
				include_once( plugin_basename( 'vendor/class-wp-rest-controller.php' ) );
			}
			//////////////////////////////////////////////////////////////////////////////

			if ( ! class_exists( 'WP_REST_Controller' ) ) {
				$this->logger->log( 'Error. WP_REST_Controller could not be found', __FUNCTION__ );
				return;
			}

			require_once( plugin_basename( 'classes/class-wc-rest-connect-services-controller.php' ) );
			$rest_services_controller = new WC_REST_Connect_Services_Controller( $schemas_store, $settings_store );
			$this->set_rest_services_controller( $rest_services_controller );
			$rest_services_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-self-help-controller.php' ) );
			$rest_self_help_controller = new WC_REST_Connect_Self_Help_Controller();
			$this->set_rest_self_help_controller( $rest_self_help_controller );
			$rest_self_help_controller->register_routes();
		}

		/**
		 * This function is added to the wc_connect_service_admin_options action by this class
		 * (see attach_hooks) and then that action is fired by WC_Connect_Shipping_Method::admin_options
		 * to get the service instance form layout and settings bundled inside wcConnectData
		 * as the form container is emitted into the body's HTML
		 */
		public function localize_and_enqueue_service_script( $id, $instance = false ) {
			if ( ! function_exists( 'get_rest_url' ) ) {
				return;
			}

			$settings_store = $this->get_service_settings_store();
			$schemas_store = $this->get_service_schemas_store();
			$service_schema = $schemas_store->get_service_schema_by_id_or_instance_id( $instance ? $instance : $id );

			if ( ! $service_schema ) {
				return;
			}

			$path = $instance ? "/wc/v1/connect/services/{$id}/{$instance}" : "/wc/v1/connect/services/{$id}";

			$admin_array = array(
				'storeOptions' => $settings_store->get_shared_settings(),
				'formSchema'   => $service_schema->service_settings,
				'formLayout'   => $service_schema->form_layout,
				'formData'     => $settings_store->get_service_settings( $id, $instance ),
				'callbackURL'  => get_rest_url( null, $path ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );
		}

		/**
		 * Hook fetching the available services from the connect server
		 */
		public function schedule_service_schemas_fetch() {

			$schemas_store = $this->get_service_schemas_store();
			$schemas = $schemas_store->get_service_schemas();

			if ( ! $schemas ) {
				add_action( 'admin_init', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );
			} else if ( defined( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH' ) && WOOCOMMERCE_CONNECT_FREQUENT_FETCH ) {
				add_action( 'admin_init', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );
			} else if ( ! wp_next_scheduled( 'wc_connect_fetch_service_schemas' ) ) {
				wp_schedule_event( time(), 'daily', 'wc_connect_fetch_service_schemas' );
			}

		}

		/**
		 * Inject API Client and Logger into WC Connect shipping method instances.
		 *
		 * @param WC_Connect_Shipping_Method $method
		 * @param int|string                 $id_or_instance_id
		 */
		public function init_service( WC_Connect_Shipping_Method $method, $id_or_instance_id ) {

			// TODO - make more generic - allow things other than WC_Connect_Shipping_Method to work here

			$method->set_api_client( $this->get_api_client() );
			$method->set_logger( $this->get_logger() );
			$method->set_service_settings_store( $this->get_service_settings_store() );

			$service_schema = $this->get_service_schemas_store()->get_service_schema_by_id_or_instance_id( $id_or_instance_id );

			if ( $service_schema ) {
				$method->set_service_schema( $service_schema );
			}

		}

		/**
		 * Returns a reference to a service (e.g. WC_Connect_Shipping_Method) of
		 * a particular id so we can avoid instantiating them multiple times
		 *
		 * @param string $class_name Class name of service to create (e.g. WC_Connect_Shipping_Method)
		 * @param string $service_id Service id of service to create (e.g. usps)
		 * @return mixed
		 */
		protected function get_service_object_by_id( $class_name, $service_id ) {
			if ( ! array_key_exists( $service_id, $this->service_object_cache ) ) {
				$this->service_object_cache[ $service_id ] = new $class_name( $service_id );
			}

			return $this->service_object_cache[ $service_id ];
		}

		/**
		 * Filters in shipping methods for things like WC_Shipping::get_shipping_method_class_names
		 *
		 * @param $shipping_methods
		 * @return mixed
		 */
		public function woocommerce_shipping_methods( $shipping_methods ) {

			$shipping_service_ids = $this->get_service_schemas_store()->get_all_service_ids_of_type( 'shipping' );

			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_methods[ $shipping_service_id ] = $this->get_service_object_by_id( 'WC_Connect_Shipping_Method', $shipping_service_id );
			}

			return $shipping_methods;
		}

		/**
		 * Registers shipping methods for use in things like the Add Shipping Method dialog
		 * on the Shipping Zones view
		 *
		 */
		public function woocommerce_load_shipping_methods() {

			$shipping_service_ids = $this->get_service_schemas_store()->get_all_service_ids_of_type( 'shipping' );

			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_method = $this->get_service_object_by_id( 'WC_Connect_Shipping_Method', $shipping_service_id );
				WC_Shipping::instance()->register_shipping_method( $shipping_method );
			}
		}


		public function woocommerce_payment_gateways( $payment_gateways ) {
			return $payment_gateways;
		}

		/**
		 * Wrapper for enqueuing scripts based on page hook and GET parameters.
		 *
		 * @codeCoverageIgnore
		 * @see 'admin_enqueue_scripts'
		 * @see self::enqueue_shipping_script
		 * @param string $hook
		 */
		public function admin_enqueue_scripts( $hook ) {

			$tab      = isset( $_GET['tab'] ) ? $_GET['tab'] : null;
			$instance = isset( $_GET['instance_id'] ) ? $_GET['instance_id'] : null;

			$this->enqueue_service_script( $hook, $tab, $instance );

		}

		/**
		 * When on an wp-admin shipping zone shipping method instance page or
		 * on our system status tab, registers the React UI bundle
		 *
		 * @param string $hook
		 * @param string $tab
		 * @param int    $instance_id
		 */
		public function enqueue_service_script( $hook, $tab, $instance_id ) {

			if ( 'woocommerce_page_wc-settings' === $hook ) {
				// If we are on a settings page, make sure it is the shipping tab
				// and that we have an instance id
				if ( 'shipping' !== $tab || empty( $instance_id ) ) {
					return;
				}
			} else if ( 'woocommerce_page_wc-status' === $hook ) {
				// If we are on a system status page, make sure it is the connect tab
				if ( 'connect' !== $tab ) {
					return;
				}
			} else {
				// Don't recognize the hook? Go no further
				return;
			}

			wp_register_style( 'noticons', plugins_url( 'assets/stylesheets/noticons.css', __FILE__ ), array(), '20150727' );
			wp_register_style( 'dashicons', plugins_url( 'assets/stylesheets/dashicons.css', __FILE__ ), array(), '20150727' );

			$wc_connect_base_url = defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_DEV_SERVER_URL : plugins_url( 'dist/', __FILE__ );
			wp_register_style( 'wc_connect_admin', $wc_connect_base_url . 'woocommerce-connect-client.css', array( 'noticons', 'dashicons' ) );
			wp_register_script( 'wc_connect_admin', $wc_connect_base_url . 'woocommerce-connect-client.js', array(), false, true );
		}

		public function get_active_shipping_services() {
			global $wpdb;
			$active_shipping_services = array();
			$shipping_service_ids = $this->get_service_schemas_store()->get_all_service_ids_of_type( 'shipping' );

			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$is_active = $wpdb->get_var( $wpdb->prepare(
					"SELECT instance_id FROM wp_woocommerce_shipping_zone_methods WHERE is_enabled = 1 AND method_id = %s LIMIT 1;",
					$shipping_service_id
				) );

				if ( $is_active ) {
					$active_shipping_services[] = $shipping_service_id;
				}
			}

			return $active_shipping_services;
		}

		public function get_active_services() {
			return $this->get_active_shipping_services();
		}

		public function is_wc_connect_shipping_service( $service_id ) {
			$shipping_service_ids = $this->get_service_schemas_store()->get_all_service_ids_of_type( 'shipping' );
			return in_array( $service_id, $shipping_service_ids );
		}

		public function shipping_zone_method_added( $instance_id, $service_id, $zone_id ) {
			if ( $this->is_wc_connect_shipping_service( $service_id ) ) {
				do_action( 'wc_connect_shipping_zone_method_added', $instance_id, $service_id, $zone_id );
			}
		}

		public function shipping_zone_method_deleted( $instance_id, $service_id, $zone_id ) {
			if ( $this->is_wc_connect_shipping_service( $service_id ) ) {
				do_action( 'wc_connect_shipping_zone_method_deleted', $instance_id, $service_id, $zone_id );
			}
		}

		public function shipping_zone_method_status_toggled( $instance_id, $service_id, $zone_id, $enabled ) {
			if ( $this->is_wc_connect_shipping_service( $service_id ) ) {
				do_action( 'wc_connect_shipping_zone_method_status_toggled', $instance_id, $service_id, $zone_id, $enabled );
			}
		}

	}

	if ( ! defined( 'WC_UNIT_TESTING' ) ) {
		new WC_Connect_Loader();
	}
}

// todo: update this once we merge into WC core
register_activation_hook( __FILE__, array( 'WC_Connect_Loader', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'WC_Connect_Loader', 'plugin_deactivation' ) );
