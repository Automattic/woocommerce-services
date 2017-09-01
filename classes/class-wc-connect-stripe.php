<?php

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

		/** @var string */
		private $error = '';

		public function __construct( WC_Connect_API_Client $client, WC_Connect_Options $options, WC_Connect_Logger $logger ) {
			$this->api = $client;
			$this->options = $options;
			$this->logger = $logger;
		}

		public function init() {
			if ( ! session_id() ) {
				session_start();
			}

			if ( isset( $_GET[ 'wcs_stripe_create' ] ) ) {
				$this->create_deferred_account();
			} elseif ( isset( $_GET[ 'wcs_stripe_connect' ] ) ) {
				$this->start_oauth();
			} elseif ( isset( $_GET[ 'wcs_stripe_code' ] ) && isset( $_GET[ 'wcs_stripe_state' ] ) ) {
				$this->connect_oauth();
			} else {
				add_action( 'admin_notices', array( $this, 'render_notice' ) );
			}
		}

		public function get_settings() {
			$email = get_option( 'admin_email' );
			$country = WC()->countries->get_base_country();
			$return_url = site_url( remove_query_arg( 'wcs_stripe_connect' ) );
			$result = $this->api->get_stripe_oauth_init( $return_url );

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			set_transient( 'wcs_stripe_state', $result->state );

			return array (
				'email' => $email,
				'country' => $country,
				'oauthUrl' => $result->oauthUrl,
			);
		}

		private function create_deferred_account() {
			$email = get_option( 'admin_email' );
			$country = WC()->countries->get_base_country();
			$result = $this->api->create_stripe_account( $email, $country );
			$this->save_stripe_keys( $result );
		}

		private function start_oauth() {
			$return_url = site_url( remove_query_arg( 'wcs_stripe_connect' ) );
			$result = $this->api->get_stripe_oauth_init( $return_url );
			if ( is_wp_error( $result ) ) {
				$this->error = json_encode( $result );
				add_action( 'admin_notices', array( $this, 'error_notice' ) );
				exit;
			}

			$_SESSION[ 'wcs_stripe_oauth_state' ] = $result->state;
			wp_redirect( $result->oauthUrl );
			exit;
		}

		private function connect_oauth() {
			if ( $_SESSION[ 'wcs_stripe_oauth_state' ] !== $_GET[ 'wcs_stripe_state' ] ) {
				$this->error = 'Invalid state received at end of OAuth flow.';
				add_action( 'admin_notices', array( $this, 'error_notice' ) );
				return;
			}

			$result = $this->api->get_stripe_oauth_keys( $_GET[ 'wcs_stripe_code' ] );
			$this->save_stripe_keys( $result );
		}

		public function save_stripe_keys( $result ) {
			if ( ! isset( $result->accountId, $result->publishableKey, $result->secretKey ) ) {
				$this->error = 'Unexpected server response: ' . json_encode( $result );
				add_action( 'admin_notices', array( $this, 'error_notice' ) );
				return;
			}

			$option_name = 'woocommerce_stripe_settings';
			$options = get_option( $option_name );
			$options['testmode']        = 'no';
			$options['account_id']      = $result->accountId;
			$options['publishable_key'] = $result->publishableKey;
			$options['secret_key']      = $result->secretKey;

			update_option( $option_name, $options );
			add_action( 'admin_notices', array( $this, 'success_notice' ) );
		}
	}
}
