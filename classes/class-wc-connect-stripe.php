<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Stripe' ) ) {

	class WC_Connect_Stripe {

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

		const STATE_VAR_NAME = 'stripe_state';
		const SETTINGS_OPTION = 'woocommerce_stripe_settings';

		public function __construct( WC_Connect_API_Client $client, WC_Connect_Options $options, WC_Connect_Logger $logger ) {
			$this->api = $client;
			$this->options = $options;
			$this->logger = $logger;
		}

		public function is_stripe_plugin_enabled() {
			return class_exists( 'WC_Stripe' );
		}

		public function get_oauth_url( $return_url ) {
			$result = $this->api->get_stripe_oauth_init( $return_url );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$this->options->update_option( self::STATE_VAR_NAME, $result->state );

			return $result->oauthUrl;
		}

		public function create_account( $email, $country ) {
			$response = $this->api->create_stripe_account( $email, $country );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
			return $this->save_stripe_keys( $response );
		}

		public function get_account_details() {
			return $this->api->get_stripe_account_details();
		}

		public function deauthorize_account() {
			$response = $this->api->deauthorize_stripe_account();
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$this->clear_stripe_keys();
			return $response;
		}

		public function connect_oauth( $state, $code ) {
			if ( $state !== $this->options->get_option( self::STATE_VAR_NAME, false ) ) {
				return new WP_Error( 'Invalid stripe state' );
			}

			$response = $this->api->get_stripe_oauth_keys( $code );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			return $this->save_stripe_keys( $response );
		}

		private function save_stripe_keys( $result ) {
			if ( ! isset( $result->publishableKey, $result->secretKey ) ) {
				return new WP_Error( 'Invalid credentials received from server' );
			}

			$is_test = false !== strpos( $result->publishableKey, '_test_' );
			$prefix = $is_test ? 'test_' : '';

			$default_options = $this->get_default_stripe_config();

			$options = array_merge( $default_options, get_option( self::SETTINGS_OPTION, array() ) );
			$options['enabled']                     = 'yes';
			$options['testmode']                    = $is_test ? 'yes' : 'no';
			$options[ $prefix . 'publishable_key' ] = $result->publishableKey;
			$options[ $prefix . 'secret_key' ]      = $result->secretKey;

			// While we are at it, let's also clear the account_id and
			// test_account_id if present

			// Those used to be stored by save_stripe_keys but should not have
			// been since they were not used by anyone

			unset( $options[ 'account_id' ] );
			unset( $options[ 'test_account_id' ] );

			update_option( self::SETTINGS_OPTION, $options );
			return $result;
		}

		/**
		 * Clears keys for test or production (whichever is presently enabled).
		 * Especially useful after Stripe Connect account deauthorization.
		 */
		private function clear_stripe_keys() {
			$default_options = $this->get_default_stripe_config();
			$options = array_merge( $default_options, get_option( self::SETTINGS_OPTION, array() ) );

			if ( 'yes' === $options['testmode'] ) {
				$options[ 'test_publishable_key' ] = '';
				$options[ 'test_secret_key' ] = '';
			} else {
				$options[ 'publishable_key' ] = '';
				$options[ 'secret_key' ] = '';
			}

			// While we are at it, let's also clear the account_id and
			// test_account_id if present

			// Those used to be stored by save_stripe_keys but should not have
			// been since they were not used by anyone

			unset( $options[ 'account_id' ] );
			unset( $options[ 'test_account_id' ] );

			update_option( self::SETTINGS_OPTION, $options );
		}

		private function get_default_stripe_config() {
			if ( ! class_exists( 'WC_Gateway_Stripe' ) ) {
				return array();
			}

			$result = array();
			$gateway = new WC_Gateway_Stripe();
			foreach ( $gateway->form_fields as $key => $value ) {
				if ( isset( $value['default'] ) ) {
					$result[ $key ] = $value['default'];
				}
			}

			return $result;
		}
	}
}
