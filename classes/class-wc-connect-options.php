<?php

if ( ! class_exists( 'WC_Connect_Options' ) ) {
	class WC_Connect_Options {
		const TOS_ACCEPTED = 'wc_connect_tos_accepted';
		const STORE_GUID = 'wc_connect_store_guid';
		const ERROR_NOTICE = 'wc_connect_error_notice';
		const DEBUG_ENABLED = 'wc_connect_debug_logging_enabled';
		const PAYMENT_METHODS = 'wc_connect_payment_methods';
		const SERVICE_SCHEMAS = 'wc_connect_services';
		const LAST_SCHEMA_UPDATE = 'wc_connect_services_last_update';
		const LAST_HEARTBEAT = 'wc_connect_last_heartbeat';
		const ACCOUNT_SETTINGS = 'wc_connect_account_settings';
		const PAPER_SIZE = 'wc_connect_paper_size';
		const ORIGIN_ADDRESS = 'wc_connect_origin_address';
		const CUSTOM_PACKAGES = 'wc_connect_packages';
		const PREDEFINED_PACKAGES = 'wc_connect_predefined_packages';
		const LAST_RATE_REQUEST = 'wc_connect_last_rate_request';
		const NUX_NOTICES = 'wc_connect_nux_notices';

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

			$reflect = new ReflectionClass( get_called_class() );
			$constants = $reflect->getConstants();
			foreach ( $constants as $constantName => $constantValue ) {
				delete_option( $constantValue );
			}

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