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
		 * @var WC_Connect_Services_Store
		 */
		protected $service_store;

		/**
		 * @var WC_Connect_Services_Validator
		 */
		protected $service_validator;

		protected $services = array();

		protected $service_object_cache = array();

		public function __construct() {
			add_action( 'woocommerce_init', array( $this, 'init' ) );
		}

		/**
		 * Bootstrap our plugin and hook into WP/WC core.
		 */
		public function init() {

			$this->load_dependencies();
			$this->attach_hooks();

		}

		/**
		 * Load all plugin dependencies.
		 */
		public function load_dependencies() {

			require_once( plugin_basename( 'classes/class-wc-connect-logger.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-services-validator.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-services-store.php' ) );

			$this->logger            = new WC_Connect_Logger( new WC_Logger() );
			$this->api_client        = new WC_Connect_API_Client();
			$this->service_validator = new WC_Connect_Services_Validator( $this->logger );
			$this->service_store     = new WC_Connect_Services_Store( $this->api_client, $this->logger, $this->service_validator );

		}

		/**
		 * Hook plugin classes into WP/WC core.
		 */
		public function attach_hooks() {

			$services = $this->service_store->get_services();

			if ( $services ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
				add_action( 'wc_connect_shipping_method_init', array( $this, 'init_shipping_method' ), 10, 2 );
			}

			// Hook fetching the available services from the connect server
			if ( ! $services ) {
				add_action( 'admin_init', array( $this->service_store, 'fetch_services_from_connect_server' ) );
			} else if ( defined( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH' ) && WOOCOMMERCE_CONNECT_FREQUENT_FETCH ) {
				add_action( 'admin_init', array( $this->service_store, 'fetch_services_from_connect_server' ) );
			} else if ( ! wp_next_scheduled( 'wc_connect_fetch_services' ) ) {
				wp_schedule_event( time(), 'daily', 'wc_connect_fetch_services' );
			}

			add_action( 'wc_connect_fetch_services', array( $this->service_store, 'fetch_services_from_connect_server' ) );

		}

		/**
		 * @param WC_Connect_Shipping_Method $method
		 * @param int|string                 $id_or_instance_id
		 */
		public function init_shipping_method( WC_Connect_Shipping_Method $method, $id_or_instance_id ) {

			$method->set_api_client( $this->api_client );
			$method->set_logger( $this->logger );

			if ( $method->instance_id ) {

				$method->set_service( $this->service_store->get_service_by_instance_id( $method->instance_id ) );

			} else if ( ! empty( $id_or_instance_id ) ) {

				$method->set_service( $this->service_store->get_service_by_id( $id_or_instance_id ) );

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

			$shipping_service_ids = $this->service_store->get_all_service_ids_of_type( 'shipping' );

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

			$shipping_service_ids = $this->service_store->get_all_service_ids_of_type( 'shipping' );

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
		 * @see 'admin_enqueue_scripts'
		 * @see self::enqueue_shipping_script
		 * @param string $hook
		 */
		public function admin_enqueue_scripts( $hook ) {

			$tab      = isset( $_GET['tab'] ) ? $_GET['tab'] : null;
			$instance = isset( $_GET['instance_id'] ) ? $_GET['instance_id'] : null;

			$this->enqueue_shipping_script( $hook, $tab, $instance );

		}

		/**
		 * When on an wp-admin shipping zone shipping method instance page, enqueues
		 * the React UI bundle and shipping service instance form schema and settings
		 *
		 * @param string $hook
		 * @param string $tab
		 * @param int    $instance_id
		 */
		public function enqueue_shipping_script( $hook, $tab, $instance_id ) {

			if ( 'woocommerce_page_wc-settings' !== $hook ) {
				return;
			}

			if ( 'shipping' !== $tab ) {
				return;
			}

			if ( empty( $instance_id ) ) {
				return;
			}

			wp_register_script( 'wc_connect_shipping_admin', plugins_url( 'build/bundle.js', __FILE__ ), array(), false, true );

		}

	}

	if ( ! defined( 'WC_UNIT_TESTING' ) ) {
		new WC_Connect_Loader();
	}
}
