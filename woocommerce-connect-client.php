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

require_once( plugin_basename( 'classes/class-wc-connect-services-store.php' ) );

if ( ! class_exists( 'WC_Connect_Loader' ) ) {
	class WC_Connect_Loader {
		protected $services = array();

		public function __construct() {
			add_action( 'woocommerce_init', array( $this, 'load_dependencies' ) );

			$this->services = get_option( 'wc_connect_services', null );
			if ( $this->services ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
			}
		}

		public function woocommerce_shipping_methods( $shipping_methods ) {

			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			$shipping_service_ids = WC_Connect_Services_Store::getInstance()->get_all_service_ids_of_type( 'shipping' );
			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_methods[ $shipping_service_id ] = new WC_Connect_Shipping_Method( $shipping_service_id );
			}

			return $shipping_methods;

		}

		public function load_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );
		}

		/**
		 * Registers shipping methods for use in things like the Add Shipping Method dialog
		 * on the Shipping Zones view
		 *
		 * Has an artifact that it also seems to be displaying one for the class in the
		 * submenu navigation bar for Shipping
		 *
		 */
		public function woocommerce_load_shipping_methods() {

			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			$shipping_service_ids = WC_Connect_Services_Store::getInstance()->get_all_service_ids_of_type( 'shipping' );
			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_method = new WC_Connect_Shipping_Method( $shipping_service_id );
				WC_Shipping::instance()->register_shipping_method( $shipping_method );
			}

		}


		public function woocommerce_payment_gateways( $payment_gateways ) {

			return $payment_gateways;

		}

	}

	new WC_Connect_Loader();
}
