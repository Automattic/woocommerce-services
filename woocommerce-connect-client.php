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

		protected $log = null;

		public function __construct() {
			add_action( 'woocommerce_init', array( $this, 'load_dependencies' ) );

			$this->services = get_option( 'wc_connect_services', null );
			if ( $this->services ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
			}

			if ( defined( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH' ) ) {
				add_action( 'admin_init', array( $this, 'fetch_services' ) );
			} else if ( ! wp_next_scheduled( 'wc_connect_fetch_services' ) ) {
				wp_schedule_event( time(), 'daily', 'wc_connect_fetch_services' );
			}

			add_action( 'wc_connect_fetch_services', array( $this, 'fetch_services' ) );
		}

		public function fetch_services() {
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );


			$response = WC_Connect_API_Client::get_services();
			if ( is_wp_error( $response ) ) {
				$this->log( $response->get_error_code() . ' ' . $response->get_error_message() . ' (fetch_services)' );
				return;
			}

			if ( ! array_key_exists( 'body', $response ) ) {
				$this->log( 'Server response did not include body.  Services not updated. (fetch_services)' );
				$this->log( 'Server response = ' . print_r( $response, true ) );
				return;
			}

			$body = json_decode( $response['body'] );

			if ( ! is_object( $body ) ) {
				$this->log( 'Server response body is not an object.  Services not updated. (fetch_services)' );
				$this->log( 'Server response body = ' . print_r( $body, true ) );
				return;
			}

			$this->update_services( $body );
		}

		protected function update_services( $services ) {

			// Validate
			// Make sure each of services properties is an array
			// e.g. $kind = "shipping" and $kind_services is an array of service objects (e.g. usps, canada post, etc)
			foreach ( $services as $kind => $kind_services ) {
				if ( ! is_array( $kind_services ) ) {
					$this->log(
						sprintf(
							"services['%s'] does not reference an array. Services not updated. (update_services)",
							$kind
						)
					);
					return;
				}

				$this->log(
					sprintf(
						"Found %d %s services to process",
						count( $kind_services ), $kind
					)
				);

				// Check each service of this kind for required properties
				$required_properties = array( 'id', 'method_description', 'method_title', 'service_settings' );
				$kind_service_offset = 0;
				// e.g. each kind_service should be an object
				foreach ( $kind_services as $kind_service ) {
					if ( ! is_object( $kind_service ) ) {
						$this->log(
							sprintf(
								"services['%s'][%d] is not an object. Services not updated. (update_services)",
								$kind, $kind_service_offset
							)
						);
						return;
					}

					foreach ( $required_properties as $required_property ) {
						if ( ! property_exists( $kind_service, $required_property ) ) {
							$this->log(
								sprintf(
									"services['%s'][%d] is missing %s, which is required. Services not updated. (update_services)",
									$kind, $kind_service_offset, $required_property
								)
							);
							$this->log(
								sprintf(
									"services['%s'][%d] = %s",
									$kind, $kind_service_offset, print_r( $kind_service, true )
								)
							);
							return;
						}
					}

					$kind_service_offset++;
				}
			}

			// If we made it this far, it is safe to store the object
			update_option( 'wc_connect_services', $services );
		}

		public function load_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );
		}

		public function woocommerce_shipping_methods( $shipping_methods ) {

			$wcc_shipping_methods = (array) $this->services[ 'shipping' ];

			if ( empty( $wcc_shipping_methods ) ) {
				return $shipping_methods;
			}

			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );

			foreach ( $wcc_shipping_methods as $wcc_shipping_method ) {
				$shipping_methods[] = new WC_Connect_Shipping_Method( $wcc_shipping_method );
			}

			return $shipping_methods;
		}

		public function woocommerce_payment_gateways( $payment_gateways ) {

			$wcc_payment_gateways = (array) $this->services[ 'payment' ];

			if ( empty( $wcc_payment_gateways ) ) {
				return $payment_gateways;
			}

			require_once( plugin_basename( 'classes/class-wc-connect-payment-gateway.php' ) );

			foreach ( $wcc_payment_gateways as $wcc_payment_gateway ) {
				$payment_gateways[] = new WC_Connect_Payment_Gateway( $wcc_payment_gateway );
			}

			return $payment_gateways;
		}

		public function log( $message ) {
			if ( empty( $this->log ) ) {
				$this->log = new WC_Logger();
			}

			$this->log->add( 'wc-connect', $message );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( $message );
			}
		}
	}

	new WC_Connect_Loader();
}
