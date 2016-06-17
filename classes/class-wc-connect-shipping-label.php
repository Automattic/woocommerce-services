<?php

if ( ! class_exists( 'WC_Connect_Shipping_Label' ) ) {

	class WC_Connect_Shipping_Label {

		private $settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->settings_store = $settings_store;
		}

		public function meta_box( $post ) {
			global $theorder;

			if ( ! is_object( $theorder ) ) {
				$theorder = wc_get_order( $post->ID );
			}

			$order = $theorder;

			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab' => 'connect'
				),
				admin_url( 'admin.php' )
			) );

			$admin_array = array(
				'storeOptions' => $this->settings_store->get_shared_settings(),
				'formSchema'   => array(
					'properties' => array(),
				),
				'formLayout'   => array(),
				'formData'     => array(),
				'callbackURL'  => get_rest_url( null, "/wc/v1/connect/shipping-label" ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'rootView'     => 'shipping-label',
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			?>
			<div id="wc-connect-admin-container"><span class="form-troubles" style="opacity: 0"><?php printf( __( 'Settings not loading? Visit the WooCommerce Connect <a href="%s">debug page</a> to get some troubleshooting steps.', 'woocommerce' ), $debug_page_uri ); ?></span></div>
			<?php
		}

	}
}
