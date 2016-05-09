<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Tracks' ) ) {

	class WC_Connect_Tracks {
		static $product_name = 'woocommerceconnect';

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		public function __construct( WC_Connect_Logger $logger ) {
			$this->logger = $logger;
		}

		public function opted_in() {
			return $this->record_user_event( 'opted_in' );
		}

		public function opted_out() {
			return $this->record_user_event( 'opted_out' );
		}

		public function record_user_event( $event_type, $data = array() ) {
			if ( ! function_exists( 'jetpack_tracks_record_event' ) ) {
				$this->logger->log( 'Error. jetpack_tracks_record_event is not defined.' );
				return;
			}

			$user = wp_get_current_user();
			$site_url = get_option( 'siteurl' );

			$data['_via_ua'] = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
			$data['_via_ip'] = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
			$data['_lg'] = isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
			$data['blog_url'] = $site_url;
			$data['blog_id'] = Jetpack_Options::get_option( 'id' );
			$data['jetpack_version'] = JETPACK__VERSION;
			$data['wc_version'] = WC()->version;
			$data['wp_version'] = get_bloginfo( 'version' );

			$event_type = self::$product_name . '_' . $event_type;

			$this->logger->log( 'Tracked the following event: ' . $event_type );
			return jetpack_tracks_record_event( $user, $event_type, $data );
		}

	}

}
