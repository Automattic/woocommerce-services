<?php

if ( ! defined( '' ) ) {

	class WC_Connect_Jetpack_Sync_Options {
		private $options = array(
			'wc_connect_account_settings',
			'wc_connect_debug_logging_enabled',
			'wc_connect_error_notice',
			'wc_connect_last_heartbeat',
			'wc_connect_last_rate_request',
			'wc_connect_nux_notices',
			'wc_connect_payment_methods',
			'wc_connect_services',
			'wc_connect_services_last_update',
			'wc_connect_tos_accepted',
			'wc_connect_last_heartbeat',
			'wc_connect_packages',
			'wc_connect_paper_size',
			'wc_connect_predefined_packages',
			'woocommerce_dimension_unit',
			'woocommerce_weight_unit',
		);

		/** @var array Variable make sure these variables never accidentally get whitelisted*/
		private $blacklist = array(
			'wc_connect_store_guid',
			'wc_connect_origin_address',
		);

		private $settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->settings_store = $settings_store;
		}

		public function add_whitelist_options( $options ) {
			$options = array_merge( $options, $this->options, $this->get_service_failure_options(), $this->get_service_settings_options() );
			$options = array_diff( $options, $this->blacklist );
			$options = array_unique( $options );
			return $options;
		}

		private function get_service_failure_options() {
			$result = array();
			foreach ( array() as $id => $instance ) {
				$results[] = $this->settings_store->get_service_failure_timestamp_key( $id, $instance );
			}

			return $result;
		}

		private function get_service_settings_options() {
			$result = array();
			foreach ( array() as $id => $instance ) {
				$results[] = $this->settings_store->get_service_settings_key( $id, $instance );
			}

			return $result;
		}
	}
}