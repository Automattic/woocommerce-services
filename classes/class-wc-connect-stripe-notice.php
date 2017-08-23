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

		/**
		 * @var string
		 */
		private $oauth_url;

		/** @var string */
		private $error = '';

		public function __construct( WC_Connect_API_Client $client, WC_Connect_Options $options, WC_Connect_Logger $logger ) {
			$this->api = $client;
			$this->options = $options;
			$this->logger = $logger;
			$this->get_oauth_url();
		}

		private function get_oauth_url()
		{
			$return_url = 'https://wcs.loc/blah/blah';
			$result = $this->api->get_stripe_oauth_init( $return_url );
			if ( is_wp_error( $result ) ) {
				$this->logger->error( 'Failed to fetch OAuth URL', $result );
				return;
			}
			$this->oauth_url = $result[ 'url' ];
			$this->options->update_option( 'stripe_oauth_code', $result[ 'code' ] );
		}

		private function get_oauth_keys( $state, $code ) {
			if ( $state !== $this->options->get_option( 'stripe_oauth_code' ) ) {
				$this->logger->error( 'Invalid code received at end of OAuth flow.' );
				return false;
			}

			$result = $this->api->get_stripe_oauth_keys( $code );
			$this->save_stripe_keys( $result->accountId, $result->publishableKey, $result->secretKey );
			add_action( 'admin_notices', array( $this, 'success_notice' ) );
			return true;
		}

		public function set_up_notice() {
			if ( ! $this->should_show() ) {
				return;
			}

			if ( $this->try_connect() ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'render_notice' ) );
		}

		public function try_connect() {
			$email = $this->get_post_param( 'wcs_stripe_email' );
			$country = $this->get_post_param( 'wcs_stripe_country' );
			if ( '' === $email || '' === $country ) {
				return false;
			}

			$result = $this->api->create_stripe_account( $email, $country );
			if ( is_wp_error( $result ) ) {
				error_log( json_encode( $result ) );
				return false;
			}

			if ( ! ( isset( $result->accountId, $result->publishableKey, $result->secretKey ) ) ) {
				error_log( 'Unexpected response from server' );
				error_log( json_encode( $result ) );
				return false;
			}

			$this->save_stripe_keys( $result->accountId, $result->publishableKey, $result->secretKey );

			add_action( 'admin_notices', array( $this, 'success_notice' ) );
			return true;
		}

		public function should_show() {
			return class_exists( 'WC_Stripe_API' );// && empty( WC_Stripe_API::get_secret_key() );
		}

		public function render_notice() {
			$email = $this->get_post_param( 'wcs_stripe_email' );
			$country = $this->get_post_param( 'wcs_stripe_country' );
?>
			<div id="message" class="updated jp-wpcom-connect__container">
				<form method='POST'>
					<div>
						Already have a stripe account? <a href="/">Click here</a>.
					</div>
					<div>
						Email: <input name="wcs_stripe_email" value='<?php echo $email; ?>' />
					</div>
					<div>
						<label>Country:</label>
						<input name="wcs_stripe_country" value='<?php echo $country; ?>' />
					</div>
					<div>
						<input type="submit" />
					</div>
				</form>
			</div>
<?php
		}

		public function success_notice() {
?>
			<div id="message" class="updated jp-wpcom-connect__container">
				Successfully connected.
			</div>
<?php
		}

		public function failure_notice() {
?>
			<div id="message" class="updated jp-wpcom-connect__container">
				An error occurred: <?php echo $this->error; ?>
			</div>
<?php
		}
		private function get_post_param( $param ) {
			if ( isset( $_POST[ $param ] ) ) {
				return $_POST[ $param ];
			}

			return "";
		}

		private function save_stripe_keys( $account_id, $publishable_key, $secret_key ) {
			$option_name = 'woocommerce_stripe_settings';
			$options = get_option( $option_name );

			$options['testmode']        = 'yes';
			$options['account_id']      = $account_id;
			$options['test_publishable_key'] = $publishable_key;
			$options['test_secret_key']      = $secret_key;
			update_option( $option_name, $options );
		}
	}
}
