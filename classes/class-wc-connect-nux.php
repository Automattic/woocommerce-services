<?php

if ( ! class_exists( 'WC_Connect_Nux' ) ) {

	class WC_Connect_Nux {
		/**
		 * Jetpack status constants.
		 */
		const JETPACK_UNINSTALLED = 'uninstalled';
		const JETPACK_INSTALLED = 'installed';
		const JETPACK_ACTIVATED = 'activated';
		const JETPACK_DEV = 'dev';
		const JETPACK_CONNECTED = 'connected';

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
					return self::JETPACK_INSTALLED;
				}
				return self::JETPACK_UNINSTALLED;
			} else if ( defined( 'JETPACK_DEV_DEBUG' ) && true === JETPACK_DEV_DEBUG ) {
				// installed, activated, and dev mode on
				return self::JETPACK_DEV;
			}

			// installed, activated, dev mode off
			// check if connected
			$user_token = Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			if ( isset( $user_token->external_user_id ) ) { // always an int
				return self::JETPACK_CONNECTED;
			}

			return self::JETPACK_ACTIVATED;
		}

		public function should_display_nux_notice_on_screen( $screen ) {
			if ( // Display if on any of these admin pages.
				( // Products list.
					'product' === $screen->post_type
					&& 'edit' === $screen->base
				)
				|| ( // Orders list.
					'shop_order' === $screen->post_type
					&& 'edit' === $screen->base
					)
				|| ( // Edit order page.
					'shop_order' === $screen->post_type
					&& 'post' === $screen->base
					)
				|| ( // WooCommerce settings.
					'woocommerce_page_wc-settings' === $screen->base
					)
				|| 'plugins' === $screen->base
			) {
				return true;
			}
			return false;
		}

		public function is_new_store() {
			$posts_by_status = wp_count_posts( 'shop_order' );
			$order_statuses = array(
				'wc-pending',
				'wc-processing',
				'wc-on-hold',
				'wc-completed',
				'wc-cancelled',
				'wc-refunded',
				'wc-failed',
			);
			foreach( $order_statuses as $order_status ) {
				if ( isset( $posts_by_status->$order_status ) && 0 < $posts_by_status->$order_status ) {
					return false;
				}
			}
			return true;
		}

		public function get_jetpack_redirect_url() {
			$full_path = add_query_arg( array() );
			// Remove [...]/wp-admin so we can use admin_url().
			$new_index = strpos( '/wp-admin', $full_path ) + strlen( '/wp-admin' );
			$path = substr( $full_path, $new_index );
			return admin_url( $path );
		}

		public function set_up_nux_notices() {
			if ( ! current_user_can( 'manage_woocommerce' )
				|| ! current_user_can( 'install_plugins' )
				|| ! current_user_can( 'activate_plugins' )
			) {
				return;
			}

			$jetpack_install_status = $this->get_jetpack_install_status();

			$ajax_data = array(
				'nonce'                  => wp_create_nonce( 'wcs_install_banner' ),
				'initial_install_status' => $jetpack_install_status,
				'redirect_url'           => $this->get_jetpack_redirect_url(),
				'translations'           => array(
					'activating'   => __( 'Activating...', 'woocommerce-services' ),
					'connecting'   => __( 'Connecting...', 'woocommerce-services' ),
					'installError' => __( 'There was an error installing Jetpack. Please try installing it manually.', 'woocommerce-services' ),
					'defaultError' => __( 'Something went wrong. Please try connecting to Jetpack manually, or contact support on the WordPress.org forums.', 'woocommerce-services' ),
				),
			);

			switch ( $jetpack_install_status ) {
				case self::JETPACK_UNINSTALLED:
				case self::JETPACK_INSTALLED:
				case self::JETPACK_ACTIVATED:
					wp_enqueue_script( 'wc_connect_banner' );
					wp_localize_script( 'wc_connect_banner', 'wcs_install_banner', $ajax_data );
					add_action( 'wp_ajax_woocommerce_services_activate_jetpack',
						array( $this, 'ajax_activate_jetpack' )
					);
					add_action( 'wp_ajax_woocommerce_services_get_jetpack_connect_url',
						array( $this, 'ajax_get_jetpack_connect_url' )
					);
					add_action( 'admin_notices', array( $this, 'show_banner_before_connection' ) );
					break;
				case self::JETPACK_CONNECTED:
					add_action( 'admin_notices', array( $this, 'show_banner_after_connection' ) );
					break;
			}
		}

		public function show_banner_before_connection() {
			if ( ! $this->should_display_nux_notice_on_screen( get_current_screen() ) ) {
				return;
			}

			$jetpack_status = $this->get_jetpack_install_status();

			$button_text = __( 'Connect your store to WordPress.com', 'woocommerce-services' );

			switch ( $jetpack_status ) {
				case self::JETPACK_UNINSTALLED:
					$button_text = __( 'Install Jetpack and connect your store to WordPress.com', 'woocommerce-services' );
					break;
				case self::JETPACK_INSTALLED:
					$button_text = __( 'Activate Jetpack and connect your store to WordPress.com', 'woocommerce-services' );
					break;
			}

			if ( $this->is_new_store() ) {
				$this->show_nux_banner( array(
					'title'           => __( 'Get access to discount shipping labels by connecting to WordPress.com', 'woocommerce-services' ),
					'description'     => __( 'WooCommerce Services is almost ready to go. Once you connect your store to WordPress.com you can begin printing labels and saving money with discounted shipping rates all from your dashboard.', 'woocommerce-services' ),
					'button_text'     => $button_text,
					'image_url'       => 'https://cldup.com/WpkrskfH_r.jpg',
					'should_show_jp'  => true,
				) );
			} else {
				$this->show_nux_banner( array(
					'title'           => __( 'Welcome to WooCommerce services', 'woocommerce-services' ),
					'description'     => __( 'WooCommerce services makes shipping a breeze. Print a label and take advantage of discounted shipping rates right as you process your order, all from the convenience of your WordPress dashboard.', 'woocommerce-services' ),
					'button_text'     => $button_text,
					'image_url'       => 'https://cldup.com/WpkrskfH_r.jpg',
					'should_show_jp'  => true,
				) );
			}
		}

		public function show_banner_after_connection() {
			if ( ! $this->should_display_nux_notice_on_screen( get_current_screen() ) ) {
				return;
			}

			$this->show_nux_banner( array(
				'title'          => __( 'You now have access to discount shipping rates and printing services directly within your dashboard!', 'woocommerce-services' ),
				'description'    => __( 'You can begin purchasing discounted labels from USPS, and printing them at any time.', 'woocommerce-services' ),
				'button_text'    => __( 'See how it works', 'woocommerce-services' ),
				'image_url'      => 'https://cldup.com/opSeqZzABZ.jpg',
				'should_show_jp' => false,
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
					<button
						class="woocommerce-services__connect-jetpack"
					>
						<?php echo esc_html( $content['button_text'] ); ?>
					</button>
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

		/**
		 * Activates Jetpack after an ajax request
		 */
		public function ajax_activate_jetpack() {
			check_ajax_referer( 'wcs_install_banner' );

			$result = activate_plugin( 'jetpack/jetpack.php' );

			if ( is_null( $result ) ) {
				// The function activate_plugin() returns NULL on success.
				echo 'success';
			} else {
				if ( is_wp_error( $result ) ) {
					echo esc_html( $result->get_error_message() );
				} else {
					echo 'error';
				}
			}

			wp_die();
		}

		/**
		 * Get Jetpack connection URL.
		 *
		 */
		public function ajax_get_jetpack_connect_url() {
			check_ajax_referer( 'wcs_install_banner' );

			$redirect_url = '';
			if ( isset( $_POST['redirect_url'] ) ) {
				$redirect_url = esc_url_raw( wp_unslash( $_POST['redirect_url'] ) );
			}

			$connect_url = Jetpack::init()->build_connect_url(
				true,
				$redirect_url,
				'woocommerce-services'
			);

			echo esc_url_raw( $connect_url );
			wp_die();
		}
	}
}
