<?php

if ( ! class_exists( 'Jetpack_Install_Status' ) ) {
	abstract class Jetpack_Install_Status {
		const UNINSTALLED = 'uninstalled';
		const INSTALLED = 'installed';
		const ACTIVATED = 'activated';
		const DEV = 'dev';
		const CONNECTED = 'connected';
	}
}

if ( ! class_exists( 'WC_Connect_Nux' ) ) {

	class WC_Connect_Nux {

		function __construct() {
			$this->init_pointers();
			add_action( 'admin_init', array( $this, 'set_up_nux_notices' ) );
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

		public function get_jetpack_install_status() {
			// check if Jetpack is activated
			if ( ! class_exists( 'Jetpack_Data' ) ) {
				// not activated, check if installed
				if ( 0 === validate_plugin( 'jetpack/jetpack.php' ) ) {
					return Jetpack_Install_Status::INSTALLED;
				}
				return Jetpack_Install_Status::UNINSTALLED;
			} else if ( defined( 'JETPACK_DEV_DEBUG' ) && true === JETPACK_DEV_DEBUG ) {
				// installed, activated, and dev mode on
				return Jetpack_Install_Status::DEV;
			}

			// installed, activated, dev mode off
			// check if connected
			$user_token = Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			if ( isset( $user_token->external_user_id ) ) { // always an int
				return Jetpack_Install_Status::CONNECTED;
			}

			return Jetpack_Install_Status::ACTIVATED;
		}

		public function set_up_nux_notices() {
			$jetpack_install_status = $this->get_jetpack_install_status();
			switch ( $jetpack_install_status ) {
				case Jetpack_Install_Status::ACTIVATED:
					add_action( 'admin_notices', array( $this, 'show_banner_before_connection_get_access' ) );
					break;
			}
		}

		public function show_banner_before_connection_get_access() {
			if ( function_exists( 'get_current_screen' ) ) {
				$screen = get_current_screen();
			}
			if ( ! isset( $screen ) ) {
				return;
			}
			if ( ! (
				'edit' === $screen->parent_base
				&& 'post' === $screen->base
				&& 'product' === $screen->post_type
			) ) {
				return;
			}
			'add' === $screen->action
				? $redirect = admin_url( 'post-new.php?post_type=product' )
				: $redirect = get_edit_post_link();
			$connect_url = Jetpack::init()->build_connect_url( true, $redirect, 'woocommerce-services' );
			$this->show_nux_banner( array(
				'title'          => __( 'Get access to discount shipping labels by connecting to WordPress.com', 'woocommerce-services' ),
				'description'    => __( 'WooCommerce Services is almost ready to go. Once you connect your store to WordPress.com you can begin printing labels and saving money with discounted shipping rates all from your dashboard.', 'woocommerce-services' ),
				'url'            => $connect_url,
				'button_text'    => __( 'Connect your store to WordPress.com', 'woocommerce-services' ),
				'image_url'      => 'https://cldup.com/WpkrskfH_r.jpg',
				'should_show_jp' => true,
			) );
		}

		public function show_nux_banner( $content ) {
			?>
			<div class="notice wcs-nux__notice" style="display:flex;">
				<div class="wcs-nux__notice-logo">
					<img src="<?php echo esc_url( $content['image_url'] );  ?>">
				</div>
				<div class="wcs-nux__notice-content">
					<h1><?php echo esc_html( $content['title'] ); ?></h1>
					<p><?php echo esc_html( $content['description'] ); ?></p>
					<a href="<?php echo esc_url( $content['url'] ); ?>">
						<?php echo esc_html( $content['button_text'] ); ?>
					</a>
					<?php if ( $content['should_show_jp'] ) : ?>
						<p>By connecting your site you agree to our fascinating <a href="http://google.com">Terms of Service</a> and to <a>share details</a> with WordPress.com.</p>
					<?php endif; ?>
				</div>
				<?php if ( $content['should_show_jp'] ) : ?>
					<div class="wcs-nux__notice-jetpack">
						<img src="https://cldup.com/BxbWlzSyPC.jpg">
						<p>Powered by Jetpack</p>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}
	}
}
