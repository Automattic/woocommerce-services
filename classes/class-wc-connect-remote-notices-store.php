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
			add_action( 'admin_notices', array( $this, 'display_notices' ) );
		}

		public function set_notices( $notices ) {
			update_option( 'wc_connect_remote_notices', $notices );
		}

		public function get_notices() {
			return get_option( 'wc_connect_remote_notices', null );
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

		public function display_notices() {
			// TODO: show more than one notice
			$notices = $this->get_notices();
			$this->display_notice( $notices->title , $notices->body, $notices->button );
		}

		public function display_notice( $title, $body, $button ) {
			?>
			<div class="notice notice-info">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p><?php echo wp_kses( $body, $this->allowed_html ); ?></p>
				<p>
					<a class="button-primary">
						<?php echo esc_html( $button ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}
}
