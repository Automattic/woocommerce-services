<?php

if ( ! class_exists( 'WC_Connect_Remote_Notices_Store' ) ) {

	class WC_Connect_Remote_Notices_Store {

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		protected $allowed_html = array(
			 'a' => array(
				 'href' => array(),
				 'title' => array(),
			 ),
			 'br' => array(),
			 'em' => array(),
			 'strong' => array(),
		 );

		public function __construct( WC_Connect_API_Client $api_client ) {
			$this->api_client = $api_client;
		}

		public function set_notices( $notices ) {
			update_option( 'wc_connect_remote_notices', $notices );
		}

		public function fetch_notices_from_connect_server() {
			$response = $this->api_client->get_notices( 1, 'en-us' );
			// TODO: save more than one notice
			$notices = (object) array(
				'title' => wp_strip_all_tags( $response->title ),
				'body' => wp_kses( $response->body, $this->allowed_html ),
				'button' => wp_strip_all_tags( $response->button ),
			);
			$this->set_notices( $notices );
		}
	}
}
