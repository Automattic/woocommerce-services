<?php

if ( ! class_exists( 'WC_Connect_Nux' ) ) {

	class WC_Connect_Nux {

		private function add_shared_notice_styles() {
			$wc_connect_base_url = defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_DEV_SERVER_URL : plugins_url( 'dist/', __FILE__ );
			wp_enqueue_style( 'wc_connect_banner', $wc_connect_base_url . 'woocommerce-connect-client-banner.css' );
		}

		private function get_notice_states() {
			return get_option( 'wc_connect_nux_notices', array() );
		}

		private function is_notice_dismissed( $notice ) {
			$notices = $this->get_notice_states();

			return isset( $notices[ $notice ] ) && $notices[ $notice ];
		}

		public function init_labels_notice() {
			if( $this->is_notice_dismissed( 'labels' ) ) {
				return false;
			}

			add_action( 'admin_notices', array( $this, 'show_labels_notice' ) );
			$this->add_shared_notice_styles();

			return true;
		}

		public function show_labels_notice() {
			return;
			/*$dismiss_url = add_query_arg( 'wc-connect-nux', 'labels' );

			?>
			<div class="notice is-dismissible wcc-admin-notice">
				<h1><?php _e( 'Shipping label NUX' ) ?></h1>
				<p>
					Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam sodales risus pharetra ante ornare interdum.  Nam et tortor lobortis est tincidunt tempus vel at quam. Morbi risus nunc, tincidunt in lacus a, varius finibus ante. Ut elit justo, commodo et metus sit amet, feugiat posuere leo. Vivamus ultricies, enim et fermentum aliquam, ante enim volutpat leo, vel tristique augue nisi vitae turpis. Nunc nec purus egestas, dictum nulla a, sagittis justo. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.
				</p>
				<p>
					Sed et placerat leo. Maecenas semper, tellus id pretium volutpat, ligula arcu dapibus sem, et facilisis eros ante vel augue. Maecenas tincidunt, ex eu dictum pretium, justo ipsum laoreet quam, id auctor metus neque quis dolor. Nulla eu vestibulum dui. Suspendisse potenti. Duis ac condimentum libero. Pellentesque interdum accumsan sapien lobortis gravida. Sed elit felis, tincidunt in pretium eget, interdum sed ipsum.
				</p>
				<p>
					<a href="<?php echo( esc_url( $dismiss_url ) ) ?>" class="button-primary"><?php _e( 'Ok' ) ?></a>
				</p>
			</div>
			<?php*/
		}

		public function check_notice_dismissal() {
			if ( ! isset( $_GET['wc-connect-nux'] ) ) {
				return;
			}

			$notice = $_GET['wc-connect-nux'];
			$notices = $this->get_notice_states();
			$notices[ $notice ] = true;
			update_option( 'wc_connect_nux_notices', $notices );
		}
	}

}