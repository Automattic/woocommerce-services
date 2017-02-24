<?php

if ( ! class_exists( 'WC_Connect_Nux' ) ) {

	class WC_Connect_Nux {

		function __construct() {
			$this->init_pointers();
		}

		private function get_notice_states() {
			$states = get_user_meta( get_current_user_id(), 'wc_connect_nux_notices', true );

			if ( ! is_array( $states ) ) {
				return array();
			}

			return $states;
		}

		public function is_notice_dismissed( $notice ) {
			$notices = $this->get_notice_states();

			return isset( $notices[ $notice ] ) && $notices[ $notice ];
		}

		public function dismiss_notice( $notice ) {
			$notices = $this->get_notice_states();
			$notices[ $notice ] = true;
			update_user_meta( get_current_user_id(), 'wc_connect_nux_notices', $notices );
		}

		private function init_pointers() {
			add_filter( 'wc_services_pointer_woocommerce_page_wc-settings', array( $this, 'register_add_service_to_zone_pointer' ) );
		}

		public function show_pointers( $hook ) {
			/* Get admin pointers for the current admin page.
			 *
			 * @since 0.9.6
			 *
			 * @param array $pointers Array of pointers.
			 */
			$pointers = apply_filters( 'wc_services_pointer_' . $hook, array() );

			if ( ! $pointers || ! is_array( $pointers ) ) {
				return;
			}

			$dismissed_pointers = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
			$valid_pointers = array();

			if( isset( $dismissed_pointers ) ) {
				foreach ( $pointers as $pointer ) {
					if ( ! in_array( $pointer['id'], $dismissed_pointers ) ) {
						$valid_pointers[] =  $pointer;
					}
				}
			} else {
				$valid_pointers = $pointers;
			}

			if ( empty( $valid_pointers ) ) {
				return;
			}

			wp_enqueue_style( 'wp-pointer' );
			wp_localize_script( 'wc_services_admin_pointers', 'wcSevicesAdminPointers', $valid_pointers );
			wp_enqueue_script( 'wc_services_admin_pointers' );
		}

		public function register_add_service_to_zone_pointer( $pointers ) {
			$pointers[] = array(
				'id' => 'wc_services_add_service_to_zone',
				'target' => 'th.wc-shipping-zone-methods',
				'options' => array(
					'content' => sprintf( '<h3>%s</h3><p>%s</p>',
						__( 'Add a WooCommerce shipping service to a Zone' ,'woocommerce-services' ),
						__( 'To ship products to customers using USPS or Canada Post, you will need to add them as a shipping method to an applicable zone. If you don\'t have any zones, add one first.', 'woocommerce-services' )
					),
					'position' => array( 'edge' => 'right', 'align' => 'left' ),
				)
			);
			return $pointers;
		}
	}
}
