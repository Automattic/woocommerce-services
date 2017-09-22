<?php

if ( ! class_exists( 'WC_Connect_Stripe_Notice' ) ) {

	class WC_Connect_Stripe_Notice {
		public function init() {
			add_action( 'admin_notices', array( $this, 'render_notice' ) );
		}

		public function render_notice() {
			$payload = array(
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'baseURL' => get_rest_url(),
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $payload );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab'  => 'connect'
				),
				admin_url( 'admin.php' )
			) );

			$extra_args = array();
			$data_args  = esc_attr( wp_json_encode( $extra_args ) );
			$message    = sprintf( __( 'Section not loading? Visit the <a href=\"%s\">status page</a> for troubleshooting steps.', 'woocommerce-services' ), $debug_page_uri );
			echo "
			<div class='updated jp-wpcom-connect__container wc-connect-stripe-connect wcc-root' data-args=\"$data_args\">
				<div>$message</div>
			</div>";
		}
	}
}
