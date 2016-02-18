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
						'id'                 => 'wc-connect-canada-post',
						'method_title'       => __( 'Canada Post (WooCommerce Connect)', 'woocommerce' ),
						'method_description' => __( 'Shipping via Canada Post, Powered by WooCommerce Connect', 'woocommerce' ),
						'service_settings'   => array()
					),
					'usps'        => array(
						'id'                 => 'wc-connect-usps',
						'method_title'       => __( 'USPS (WooCommerce Connect)', 'woocommerce' ),
						'method_description' => __( 'Shipping via USPS, Powered by WooCommerce Connect', 'woocommerce' ),
						'service_settings'   => array(
							'type'        => 'object',
							'title'       => 'USPS',
							'description' => 'The USPS extension obtains rates dynamically from the USPS API during cart/checkout.',
							'required'    => array(),
							'properties'  => array(
								'enabled' => array(
									'type'        => 'boolean',
									'title'       => 'Enable/Disable',
									'description' => 'Enable this shipping method.',
									'default'     => false,
								),
								'title'   => array(
									'type'        => 'string',
									'title'       => 'Method Title',
									'description' => 'This controls the title which the user sees during checkout.',
									'default'     => '',
								),
							),
						),
					),
				),
				'checkout' => array(
					'paypal' => array(
						'id' => 'wc-connect-paypal',
						'enabled' => 'yes',
						'title' => __( 'PayPal', 'woocommerce' ),
						'method_title' => __( 'PayPal (WooCommerce Connect)', 'woocommerce' ),
						'method_description' => __( 'Checkout via PayPal, Powered by WooCommerce Connect', 'woocommerce' )
					)
				),
			);

			add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
		}

		public function woocommerce_shipping_methods( $methods ) {

			$shipping_methods = (array) $this->services[ 'shipping' ];

			if ( $shipping_methods ) {
				require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			}

			foreach ( $shipping_methods as $key => $value ) {
				$methods[] = new WC_Connect_Shipping_Method( $value );
			}

			return $methods;
		}

		public function woocommerce_payment_gateways( $gateways ) {

			$payment_gateways = (array) $this->services[ 'checkout' ];

			if ( $payment_gateways ) {
				require_once( plugin_basename( 'classes/class-wc-connect-payment-gateway.php' ) );
			}

			foreach ( $payment_gateways as $key => $value ) {
				$gateways[] = new WC_Connect_Payment_Gateway( $value );
			}

			return $gateways;
		}
	}

	new WC_Connect_Loader();
}
