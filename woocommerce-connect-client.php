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

require_once( plugin_basename( 'classes/class-wc-connect-logger.php' ) );
require_once( plugin_basename( 'classes/class-wc-connect-services-store.php' ) );

if ( ! class_exists( 'WC_Connect_Loader' ) ) {

	class WC_Connect_Loader {
		protected $services = array();

		protected $service_object_cache = array();

		public function __construct() {
			add_action( 'woocommerce_init', array( $this, 'load_dependencies' ) );

			$this->services = get_option( 'wc_connect_services', null );
			if ( $this->services ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			}

			// Hook fetching the available services from the connect server
			if ( ! $this->services ) {
				add_action( 'admin_init', array( $this, 'fetch_services_from_connect_server' ) );
			} else if ( defined( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH' ) && WOOCOMMERCE_CONNECT_FREQUENT_FETCH ) {
				add_action( 'admin_init', array( $this, 'fetch_services_from_connect_server' ) );
			} else if ( ! wp_next_scheduled( 'wc_connect_fetch_services' ) ) {
				wp_schedule_event( time(), 'daily', 'wc_connect_fetch_services' );
			}

			add_action( 'wc_connect_fetch_services', array( $this, 'fetch_services_from_connect_server' ) );
		}

		/**
		 * Prompts the services store to fetch services anew
		 *
		 */
		public function fetch_services_from_connect_server() {
			WC_Connect_Services_Store::fetch_services_from_connect_server();
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
			$shipping_service_ids = WC_Connect_Services_Store::get_all_service_ids_of_type( 'shipping' );
			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_methods[ $shipping_service_id ] = $this->get_service_object_by_id( 'WC_Connect_Shipping_Method', $shipping_service_id );
			}

			return $shipping_methods;
		}

		public function load_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
		}

		/**
		 * Registers shipping methods for use in things like the Add Shipping Method dialog
		 * on the Shipping Zones view
		 *
		 */
		public function woocommerce_load_shipping_methods() {
			$shipping_service_ids = WC_Connect_Services_Store::get_all_service_ids_of_type( 'shipping' );
			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_method = $this->get_service_object_by_id( 'WC_Connect_Shipping_Method', $shipping_service_id );
				WC_Shipping::instance()->register_shipping_method( $shipping_method );
			}
		}


		public function woocommerce_payment_gateways( $payment_gateways ) {
			return $payment_gateways;
		}

		/**
		 * When on an wp-admin shipping zone shipping method instance page, enqueues
		 * the React UI bundle and shipping service instance form schema and settings
		 *
		 */
		public function admin_enqueue_scripts( $hook ) {
			if ( ! is_user_logged_in() ) {
				return;
			}

			if ( 'woocommerce_page_wc-settings' !== $hook ) {
				return;
			}

			$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
			if ( 'shipping' !== $tab ) {
				return;
			}

			$instance_id = isset( $_GET['instance_id'] ) ? $_GET['instance_id'] : '';
			if ( empty( $instance_id ) ) {
				return;
			}

			$instance_id = absint( $instance_id );
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			$shipping_method = new WC_Connect_Shipping_Method( $instance_id );

			$shipping_method_form_schema = $shipping_method->get_form_schema();
			$shipping_method_settings = $shipping_method->get_form_settings();

			wp_register_script( 'wc_connect_shipping_admin', plugins_url( 'build/bundle.js', __FILE__ ), array() );

			$admin_array = array(
				'formSchema' => $shipping_method_form_schema,
				'formData'   => $shipping_method_settings,
			);

			wp_localize_script( 'wc_connect_shipping_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_shipping_admin' );
		}

	}

	new WC_Connect_Loader();
}
