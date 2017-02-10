<?php

if ( ! class_exists( 'WC_Connect_Options' ) ) {
	class WC_Connect_Options {
		public static $tos_accepted = 'wc_connect_tos_accepted';
		public static $store_guid = 'wc_connect_store_guid';
		public static $error_notice = 'wc_connect_error_notice';
		public static $debug_enabled = 'wc_connect_debug_logging_enabled';
		public static $payment_methods = 'wc_connect_payment_methods';
		public static $service_schemas = 'wc_connect_services';
		public static $last_schema_update = 'wc_connect_services_last_update';
		public static $last_heartbeat = 'wc_connect_last_heartbeat';
		public static $account_settings = 'wc_connect_account_settings';
		public static $paper_size = 'wc_connect_paper_size';
		public static $origin_address = 'wc_connect_origin_address';
		public static $custom_packages = 'wc_connect_packages';
		public static $predefined_packages = 'wc_connect_predefined_packages';
		public static $last_rate_request = 'wc_connect_last_rate_request';
		public static $nux_notices = 'wc_connect_nux_notices';

		/**
		 * Based on the service id and optional instance, generates the options key that
		 * should be used to get/set the service's settings
		 *
		 * @param $service_id
		 * @param $service_instance
		 *
		 * @return string
		 */
		public static function get_service_settings_key( $service_id, $service_instance = false ) {
			if ( ! $service_instance ) {
				return 'woocommerce_' . $service_id . '_form_settings';
			}

			return 'woocommerce_' . $service_id . '_' . $service_instance . '_form_settings';
		}

		/**
		 * Based on the service id and optional instance, generates the options key that
		 * should be used to get/set the service's last failure timestamp
		 *
		 * @param $service_id
		 * @param $service_instance
		 *
		 * @return string
		 */
		public static function get_service_failure_timestamp_key( $service_id, $service_instance = false ) {
			if ( ! $service_instance ) {
				return 'woocommerce_' . $service_id . '_failure_timestamp';
			}

			return 'woocommerce_' . $service_id . '_' . $service_instance . '_failure_timestamp';
		}

		/**
		 * Deletes all options related to a shipping method
		 * @param $service_id
		 * @param $service_instance
		 */
		public static function delete_shipping_method_options( $service_id, $service_instance = false ) {
			delete_option( self::get_service_settings_key( $service_id, $service_instance ) );
			delete_option( self::get_service_failure_timestamp_key( $service_id, $service_instance ) );
		}

		/**
		 * Deletes all options created by WooCommerce Services
		 */
		public static function delete_all_options() {
			if ( defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ) {
				return;
			}

			delete_option( self::$tos_accepted );
			delete_option( self::$store_guid );
			delete_option( self::$error_notice );
			delete_option( self::$debug_enabled );
			delete_option( self::$payment_methods );
			delete_option( self::$service_schemas );
			delete_option( self::$last_schema_update );
			delete_option( self::$last_heartbeat );
			delete_option( self::$account_settings );
			delete_option( self::$paper_size );
			delete_option( self::$origin_address );
			delete_option( self::$custom_packages );
			delete_option( self::$predefined_packages );
			delete_option( self::$last_rate_request );

			self::delete_all_shipping_methods_options();
		}

		/**
		 * Deletes all options of all shipping methods
		 */
		private static function delete_all_shipping_methods_options() {
			global $wpdb;
			$methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods " );

			foreach ( (array) $methods as $method ) {
				self::delete_shipping_method_options( $method->method_id, $method->instance_id );
			}
		}


	}
}