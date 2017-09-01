<?php

if ( ! class_exists( 'WC_Connect_Stripe_Notice' ) ) {

	class WC_Connect_Stripe_Notice {

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

		public function render_notice() {
			$email = get_option( 'admin_email' );
			$country = WC()->countries->get_base_country();
			$create_link = site_url( add_query_arg( 'wcs_stripe_create', '1' ) );
			$connect_link = site_url( add_query_arg( 'wcs_stripe_connect', '1' ) );

			$payload = array(
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'baseURL'      => get_rest_url(),
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $payload );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab' => 'connect'
				),
				admin_url( 'admin.php' )
			) );

			$extra_args = array();
			$data_args = esc_attr( wp_json_encode( $extra_args ) );
			$message = sprintf( __( 'Section not loading? Visit the <a href=\"%s\">status page</a> for troubleshooting steps.', 'woocommerce-services' ), $debug_page_uri );
			echo "
			<div class='wcc-root updated jp-wpcom-connect__container wc-connect-stripe-connect' data-args=\"$data_args\">
					<span class=\"form-troubles\" style=\"opacity: 0\">$message</span>
			</div>";
		}

		public function success_notice() {
			echo '<div id="message" class="updated jp-wpcom-connect__container">Successfully connected Stripe.</div>';
		}

		public function error_notice() {
			echo "<div id='message' class='updated jp-wpcom-connect__container'>An error occurred: {$this->error}</div>";
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

		private function save_stripe_keys( $result ) {
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
