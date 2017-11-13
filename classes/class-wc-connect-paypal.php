<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_PayPal' ) ) {

	class WC_Connect_PayPal {

		/**
		 * @var WC_Connect_API_Client
		 */
		private $api;

		/**
		 * @var WC_Connect_Options
		 */
		private $options;

		/**
		 * @var WC_Connect_Logger
		 */
		private $logger;

		const STATE_VAR_NAME = 'paypal_state';
		
		public function __construct( WC_Connect_API_Client $client, WC_Connect_Options $options, WC_Connect_Logger $logger ) {
			$this->api = $client;
			$this->options = $options;
			$this->logger = $logger;
		}

		// public function is_ppec_plugin_enabled() {
		// 	return class_exists( 'WC_Gateway_PPEC_Plugin' );
		// }

		public function create_payment( $email, $total, $currency ) {
			// TODO get info from WC data instead of REST request. See how stripe / paypal plugins do it!
			$result = $this->api->create_paypal_payment( $email, $total, $currency );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$this->options->update_option( self::STATE_VAR_NAME, $result->state );
			return $result;
		}

		public function execute_payment( $payment_id, $payer_id ) {
			$result = $this->api->execute_paypal_payment( $payment_id, $payer_id );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$this->options->update_option( self::STATE_VAR_NAME, $result->state );
			return $result;
		}
	}
}
