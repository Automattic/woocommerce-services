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
		protected $services = array();

		public function __construct() {
			// Dummy data until we can fetch it
			$this->services = array(
				'shipping' => array(
					'canada_post' => array(
						'id' => 'wc-connect-canada-post',
						'enabled' => 'yes',
						'title' => __( 'Canada Post', 'woocommerce' ),
						'method_title' => __( 'Canada Post (WooCommerce Connect)', 'woocommerce' ),
						'method_description' => __( 'Shipping via Canada Post, Powered by WooCommerce Connect', 'woocommerce' )
					),
					'usps' => array(
						'id' => 'wc-connect-usps',
						'enabled' => 'yes',
						'title' => __( 'USPS', 'woocommerce' ),
						'method_title' => __( 'USPS (WooCommerce Connect)', 'woocommerce' ),
						'method_description' => __( 'Shipping via USPS, Powered by WooCommerce Connect', 'woocommerce' )
					),
				)
			);

			add_action( 'woocommerce_shipping_init', array( $this, 'woocommerce_shipping_init' ) );
			add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
		}

		public function woocommerce_shipping_init() {
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
		}

		public function woocommerce_shipping_methods( $methods ) {
			foreach ( (array) $this->services[ 'shipping' ] as $key => $value ) {
				$methods[] = new WC_Connect_Shipping_Method( $value );
			}

			return $methods;
		}
	}

	new WC_Connect_Loader();
}