<?php
/**
 * Plugin Name: WooCommerce Connect
 * Plugin URI: http://woothemes.com/
 * Description: Connects allthethings
 * Author: Automattic
 * Author URI: http://woothemes.com/
 * Version: 1.0.0
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
		 * @var WC_Connect_Service_Schemas_Validator
		 */
		protected $service_schemas_validator;

		protected $services = array();

		protected $service_object_cache = array();

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

		public function get_service_schemas_validator() {
			return $this->service_schemas_validator;
		}

		public function set_service_schemas_validator( WC_Connect_Service_Schemas_Validator $validator ) {
			$this->service_schemas_validator = $validator;
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

			$logger         = new WC_Connect_Logger( new WC_Logger() );
			$validator      = new WC_Connect_Service_Schemas_Validator();
			$api_client     = new WC_Connect_API_Client( $validator );
			$schemas_store  = new WC_Connect_Service_Schemas_Store( $api_client, $logger );
			$settings_store = new WC_Connect_Service_Settings_Store( $schemas_store, $api_client, $logger );

			$this->set_logger( $logger );
			$this->set_api_client( $api_client );
			$this->set_service_schemas_validator( $validator );
			$this->set_service_schemas_store( $schemas_store );
			$this->set_service_settings_store( $settings_store );

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

			if ( $schemas ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
				add_action( 'wc_connect_service_init', array( $this, 'init_service' ), 10, 2 );
			}

			add_action( 'wc_connect_fetch_service_schemas', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $settings_store, 'handle_wc_api' ) );
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

			if ( $service_schema = $this->get_service_schemas_store()->get_service_schema_by_id_or_instance_id( $id_or_instance_id ) ) {

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
		 * When on an wp-admin shipping zone shipping method instance page, enqueues
		 * the React UI bundle and shipping service instance form schema and settings
		 *
		 * @param string $hook
		 * @param string $tab
		 * @param int    $instance_id
		 */
		public function enqueue_service_script( $hook, $tab, $instance_id ) {

			if ( 'woocommerce_page_wc-settings' !== $hook ) {
				return;
			}

			if ( 'shipping' !== $tab ) {
				return;
			}

			if ( empty( $instance_id ) ) {
				return;
			}

			wp_register_style( 'noticons', plugins_url( 'assets/stylesheets/noticons.css', __FILE__ ), array(), '20150727' );
			wp_register_style( 'dashicons', plugins_url( 'assets/stylesheets/dashicons.css', __FILE__ ), array(), '20150727' );


			$wc_connect_base_url = defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_DEV_SERVER_URL : plugins_url( 'dist/', __FILE__ );
			wp_register_style( 'wc_connect_shipping_admin', $wc_connect_base_url . 'woocommerce-connect-client.css', array( 'noticons', 'dashicons' ) );
			wp_register_script( 'wc_connect_shipping_admin', $wc_connect_base_url . 'woocommerce-connect-client.js', array(), false, true );
		}

	}

	if ( ! defined( 'WC_UNIT_TESTING' ) ) {
		new WC_Connect_Loader();
	}
}
