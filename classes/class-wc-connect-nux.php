<?php

if ( ! class_exists( 'WC_Connect_Nux' ) ) {

	class WC_Connect_Nux {
		/**
		 * Jetpack status constants.
		 */
		const JETPACK_NOT_INSTALLED = 'uninstalled';
		const JETPACK_INSTALLED_NOT_ACTIVATED = 'installed';
		const JETPACK_ACTIVATED_NOT_CONNECTED = 'activated';
		const JETPACK_DEV = 'dev';
		const JETPACK_CONNECTED = 'connected';

		const IS_NEW_LABEL_USER = 'wcc_is_new_label_user';

		/**
		 * Option name for dismissing success banner
		 * after the JP connection flow
		 */
		const SHOULD_SHOW_AFTER_CXN_BANNER = 'should_display_nux_after_jp_cxn_banner';

		/**
		 * @var WC_Connect_Tracks
		 */
		protected $tracks;

		/**
		 * @var WC_Connect_Shipping_Label
		 */
		private $shipping_label;

		function __construct( WC_Connect_Tracks $tracks, WC_Connect_Shipping_Label $shipping_label ) {
			$this->tracks = $tracks;
			$this->shipping_label = $shipping_label;

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
			add_filter( 'wc_services_pointer_post.php', array( $this, 'register_order_page_labels_pointer' ) );
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

			$dismissed_pointers = $this->get_dismissed_pointers();
			$valid_pointers = array();

			foreach ( $pointers as $pointer ) {
				if ( ! in_array( $pointer['id'], $dismissed_pointers, true ) ) {
					$valid_pointers[] =  $pointer;
				}
			}

			if ( empty( $valid_pointers ) ) {
				return;
			}

			wp_enqueue_style( 'wp-pointer' );
			wp_localize_script( 'wc_services_admin_pointers', 'wcSevicesAdminPointers', $valid_pointers );
			wp_enqueue_script( 'wc_services_admin_pointers' );
		}

		public function get_dismissed_pointers() {
			$data = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
			if ( is_string( $data ) && 0 < strlen( $data ) ) {
				return explode( ',', $data );
			}
			return array();
		}

		public function register_add_service_to_zone_pointer( $pointers ) {
			$pointers[] = array(
				'id' => 'wc_services_add_service_to_zone',
				'target' => 'th.wc-shipping-zone-methods',
				'options' => array(
					'content' => sprintf( '<h3>%s</h3><p>%s</p>',
						__( 'Add a WooCommerce shipping service to a Zone' ,'woocommerce-services' ),
						__( "To ship products to customers using USPS or Canada Post, you will need to add them as a shipping method to an applicable zone. If you don't have any zones, add one first.", 'woocommerce-services' )
					),
					'position' => array( 'edge' => 'right', 'align' => 'left' ),
				),
			);
			return $pointers;
		}

		public function is_new_labels_user() {
			$is_new_user = get_transient( self::IS_NEW_LABEL_USER );
			if ( false === $is_new_user ) {
				global $wpdb;
				$query = "SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key = 'wc_connect_labels' LIMIT 1";
				$results = $wpdb->get_results( $query );
				$is_new_user = 0 === count( $results ) ? 'yes' : 'no';
				set_transient( self::IS_NEW_LABEL_USER, $is_new_user );
			}

			return 'yes' === $is_new_user;
		}

		public function register_order_page_labels_pointer( $pointers ) {
			$dismissed_pointers = $this->get_dismissed_pointers();
			if ( in_array( 'wc_services_labels_metabox', $dismissed_pointers, true ) ) {
				return $pointers;
			}

			// If the user is not new to labels, we should just dismiss this pointer
			if ( ! $this->is_new_labels_user() ) {
				$dismissed_pointers[] = 'wc_services_labels_metabox';
				$dismissed_data = implode( ',', $dismissed_pointers );
				update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed_data );
				return $pointers;
			}

			if ( $this->shipping_label->should_show_meta_box() ) {
				$pointers[] = array(
					'id' => 'wc_services_labels_metabox',
					'target' => '#woocommerce-order-label',
					'options' => array(
						'content' => sprintf( '<h3>%s</h3><p>%s</p>',
							__( 'Discounted Shipping Labels' ,'woocommerce-services' ),
							__( "When you're ready, purchase and print discounted labels from USPS right here.", 'woocommerce-services' )
						),
						'position' => array( 'edge' => 'right', 'align' => 'left' ),
					),
					'dim' => true,
				);
			}

			return $pointers;
		}

		/**
		 * Check that the current user is the owner of the Jetpack connection
		 * - Only that person can accept the TOS
		 *
		 * @uses self::get_jetpack_install_status()
		 *
		 * @return bool
		 */
		public function can_accept_tos() {
			$jetpack_status = $this->get_jetpack_install_status();

			if (
				( self::JETPACK_NOT_INSTALLED === $jetpack_status ) ||
				( self::JETPACK_INSTALLED_NOT_ACTIVATED === $jetpack_status )
			) {
				return false;
			}

			// Developer case
			if ( self::JETPACK_DEV === $jetpack_status ) {
				return true;
			}

			$user_token = Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			$can_accept = (
				isset( $user_token->external_user_id ) &&
				get_current_user_id() === $user_token->external_user_id
			);

			return $can_accept;
		}

		public static function get_banner_type_to_display( $status = array() ) {
			if ( ! isset( $status['jetpack_connection_status'] ) ) {
				return false;
			}

			/* The NUX Flow:
			- Case 1: Jetpack not connected (with TOS or no TOS accepted):
				1. show_banner_before_connection()
				2. connect to JP
				3. show_banner_after_connection(), which sets the TOS acceptance in options
			- Case 2: Jetpack connected, no TOS
				1. show_tos_only_banner(), which accepts TOS on button click
			- Case 3: Jetpack connected, and TOS accepted
				This is an existing user. Do nothing.
			*/
			switch ( $status['jetpack_connection_status'] ) {
				case self::JETPACK_NOT_INSTALLED:
				case self::JETPACK_INSTALLED_NOT_ACTIVATED:
				case self::JETPACK_ACTIVATED_NOT_CONNECTED:
					return 'before_jetpack_connection';
				case self::JETPACK_CONNECTED:
				case self::JETPACK_DEV:
					// Has the user just gone through our NUX connection flow?
					if ( isset( $status['should_display_after_cxn_banner'] ) && $status['should_display_after_cxn_banner'] ) {
						return 'after_jetpack_connection';
					}

					// Has the user already accepted our TOS? Then do nothing.
					// Note: TOS is accepted during the after_connection banner
					if (
						isset( $status['tos_accepted'] )
						&& ! $status['tos_accepted']
						&& isset( $status['can_accept_tos'] )
						&& $status['can_accept_tos']
					) {
						return 'tos_only_banner';
					}

					return false;
				default:
					return false;
			}
		}

		public function get_jetpack_install_status() {
			// we need to use validate_plugin to check that Jetpack is installed
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			// check if jetpack is installed
			if ( 0 !== validate_plugin( 'jetpack/jetpack.php' ) ) {
				return self::JETPACK_NOT_INSTALLED;
			}

			// check if Jetpack is activated
			if ( ! class_exists( 'Jetpack_Data' ) ) {
				return self::JETPACK_INSTALLED_NOT_ACTIVATED;
			}

			if ( defined( 'JETPACK_DEV_DEBUG' ) && true === JETPACK_DEV_DEBUG ) {
				// installed, activated, and dev mode on
				return self::JETPACK_DEV;
			}

			// installed, activated, dev mode off
			// check if connected
			$user_token = Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			if ( ! isset( $user_token->external_user_id ) ) { // always an int
				return self::JETPACK_ACTIVATED_NOT_CONNECTED;
			}

			return self::JETPACK_CONNECTED;
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
				|| ( // WooCommerce featured extension page
					'woocommerce_page_wc-addons' === $screen->base
					&& isset( $_GET['section'] ) && 'featured' === $_GET['section']
					)
				|| ( // WooCommerce shipping extension page
					'woocommerce_page_wc-addons' === $screen->base
					&& isset( $_GET['section'] ) && 'shipping_methods' === $_GET['section']
					)
				|| 'plugins' === $screen->base
			) {
				return true;
			}
			return false;
		}

		public function should_display_nux_notice_for_current_store_locale() {
			$base_location = wc_get_base_location();
			$country = isset( $base_location['country'] )
				? $base_location['country']
				: '';
			// Do not display for non-US, non-CA stores.
			if ( 'CA' === $country || 'US' === $country ) {
				return true;
			}
			return false;
		}

		public function get_jetpack_redirect_url() {
			$full_path = add_query_arg( array() );
			// Remove [...]/wp-admin so we can use admin_url().
			$new_index = strpos( $full_path, '/wp-admin' ) + strlen( '/wp-admin' );
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

			if ( ! $this->should_display_nux_notice_for_current_store_locale() ) {
				return;
			}

			$jetpack_install_status = $this->get_jetpack_install_status();
			$banner_to_display = self::get_banner_type_to_display( array(
				'jetpack_connection_status'       => $jetpack_install_status,
				'tos_accepted'                    => WC_Connect_Options::get_option( 'tos_accepted' ),
				'can_accept_tos'                  => $this->can_accept_tos(),
				'should_display_after_cxn_banner' => WC_Connect_Options::get_option( self::SHOULD_SHOW_AFTER_CXN_BANNER ),
			) );

			switch ( $banner_to_display ) {
				case 'before_jetpack_connection':
					$ajax_data = array(
						'nonce'                  => wp_create_nonce( 'wcs_nux_notice' ),
						'initial_install_status' => $jetpack_install_status,
						'redirect_url'           => $this->get_jetpack_redirect_url(),
						'translations'           => array(
							'activating'   => __( 'Activating...', 'woocommerce-services' ),
							'connecting'   => __( 'Connecting...', 'woocommerce-services' ),
							'installError' => __( 'There was an error installing Jetpack. Please try installing it manually.', 'woocommerce-services' ),
							'defaultError' => __( 'Something went wrong. Please try connecting to Jetpack manually, or contact support on the WordPress.org forums.', 'woocommerce-services' ),
						),
					);
					wp_enqueue_script( 'wc_connect_banner' );
					wp_localize_script( 'wc_connect_banner', 'wcs_nux_notice', $ajax_data );
					add_action( 'wp_ajax_woocommerce_services_activate_jetpack',
						array( $this, 'ajax_activate_jetpack' )
					);
					add_action( 'wp_ajax_woocommerce_services_get_jetpack_connect_url',
						array( $this, 'ajax_get_jetpack_connect_url' )
					);
					wp_enqueue_style( 'wc_connect_banner' );
					add_action( 'admin_notices', array( $this, 'show_banner_before_connection' ), 9 );
					break;
				case 'after_jetpack_connection':
					wp_enqueue_style( 'wc_connect_banner' );
					add_action( 'admin_notices', array( $this, 'show_banner_after_connection' ) );
					break;
				case 'tos_only_banner':
					wp_enqueue_style( 'wc_connect_banner' );
					add_action( 'admin_notices', array( $this, 'show_tos_banner' ) );
					break;
			}
		}

		public function show_banner_before_connection() {
			if ( ! $this->should_display_nux_notice_on_screen( get_current_screen() ) ) {
				return;
			}

			// Remove Jetpack's connect banners since we're showing our own.
			if ( class_exists( 'Jetpack_Connection_Banner' ) ) {
				$jetpack_banner = Jetpack_Connection_Banner::init();

				remove_action( 'admin_notices', array( $jetpack_banner, 'render_banner' ) );
				remove_action( 'admin_notices', array( $jetpack_banner, 'render_connect_prompt_full_screen' ) );
			}

			// Make sure that we wait until the button is clicked before displaying
			// the after_connection banner
			// so that we don't accept the TOS pre-maturely
			WC_Connect_Options::delete_option( self::SHOULD_SHOW_AFTER_CXN_BANNER );

			$jetpack_status = $this->get_jetpack_install_status();

			$button_text = __( 'Connect', 'woocommerce-services' );

			$image_url = plugins_url( 'images/nux-printer-laptop-illustration.png', dirname( __FILE__ ) );

			switch ( $jetpack_status ) {
				case self::JETPACK_NOT_INSTALLED:
					$button_text = __( 'Install Jetpack and connect', 'woocommerce-services' );
					break;
				case self::JETPACK_INSTALLED_NOT_ACTIVATED:
					$button_text = __( 'Activate Jetpack and connect', 'woocommerce-services' );
					break;
			}

			$default_content = array(
				'title'           => __( 'Connect your store to activate WooCommerce Shipping', 'woocommerce-services' ),
				'description'     => esc_html( __( "WooCommerce Shipping is almost ready to go! Once you connect your store you'll be able to access discounted rates and printing services for USPS and Canada Post from your dashboard (fewer trips to the post office, winning).", 'woocommerce-services' ) ),
				'button_text'     => $button_text,
				'image_url'       => $image_url,
				'should_show_jp'  => true,
				'should_show_terms' => true,
			);

			$base_location = wc_get_base_location();
			$country = isset( $base_location['country'] )
				? $base_location['country']
				: '';
			switch ( $country ) {
				case 'CA':
					$localized_content = array(
						'description'     => esc_html( __( "WooCommerce Shipping is almost ready to go! Once you connect your store you'll be able to show your customers live shipping rates when they check out.", 'woocommerce-services' ) ),
					);
					break;
				default:
					$localized_content = array();
			}

			$this->show_nux_banner( array_merge( $default_content, $localized_content ) );
		}

		public function show_banner_after_connection() {
			if ( ! $this->should_display_nux_notice_on_screen( get_current_screen() ) ) {
				return;
			}

			// Did the user just dismiss?
			if ( isset( $_GET['wcs-nux-notice'] ) && 'dismiss' === $_GET['wcs-nux-notice'] ) {
				// No longer need to keep track of whether the before connection banner was displayed.
				WC_Connect_Options::delete_option( self::SHOULD_SHOW_AFTER_CXN_BANNER );
				wp_safe_redirect( remove_query_arg( 'wcs-nux-notice' ) );
				exit;
			}

			// By going through the connection process, the user has accepted our TOS
			WC_Connect_Options::update_option( 'tos_accepted', true );

			$this->tracks->opted_in( 'connection_banner' );

			$this->show_nux_banner( array(
				'title'          => __( 'Setup complete! You can now enjoy discounted shipping rates and print labels directly from your dashboard.', 'woocommerce-services' ),
				'description'    => esc_html( __( 'When you’re ready, you can purchase discounted labels from USPS, and print USPS labels at home.', 'woocommerce-services' ) ),
				'button_text'    => __( 'Got it, thanks!', 'woocommerce-services' ),
				'button_link'    => add_query_arg( array(
					'wcs-nux-notice' => 'dismiss',
				) ),
				'image_url'      => plugins_url(
					'images/nux-printer-laptop-illustration.png', dirname( __FILE__ )
				),
				'should_show_jp' => false,
				'should_show_terms' => false,
			) );
		}

		public function show_tos_banner() {
			if ( isset( $_GET['wcs-nux-tos'] ) && 'accept' === $_GET['wcs-nux-tos'] ) {
				WC_Connect_Options::update_option( 'tos_accepted', true );

				$this->tracks->opted_in( 'tos_banner' );

				wp_safe_redirect( remove_query_arg( 'wcs-nux-tos' ) );
				exit;
			}

			$this->show_nux_banner( array(
				'title'          => __( 'Almost ready to enjoy discounted shipping rates', 'woocommerce-services' ),
				'description'    => sprintf( wp_kses( __( 'Everything is ready to roll, we just need you to agree to our <a href="%1$s">Terms of Service</a>.', 'woocommerce-services' ),
					array(
						'a' => array(
							'href' => array(),
						),
					) ),
					'https://woocommerce.com/terms-conditions/'
				),
				'button_text'    => __( 'I accept', 'woocommerce-services' ),
				'button_link'    => add_query_arg( array(
					'wcs-nux-tos' => 'accept',
				) ),
				'image_url'      => plugins_url(
					'images/nux-printer-laptop-illustration.png', dirname( __FILE__ )
				),
				'should_show_jp' => false,
				'should_show_terms' => true,
			) );
		}

		public function show_nux_banner( $content ) {
			?>
			<div class="notice wcs-nux__notice">
				<div class="wcs-nux__notice-logo">
					<img src="<?php echo esc_url( $content['image_url'] );  ?>">
				</div>
				<div class="wcs-nux__notice-content">
					<h1 class="wcs-nux__notice-content-title">
						<?php echo esc_html( $content['title'] ); ?>
					</h1>
					<p class="wcs-nux__notice-content-text">
						<?php echo $content['description']; ?>
					</p>
					<?php if ( isset( $content['should_show_terms'] ) && $content['should_show_terms'] ) : ?>
						<p class="wcs-nux__notice-content-tos"><?php
						/* translators: %1$s example values include "Install Jetpack and CONNECT >", "Activate Jetpack and CONNECT >", "CONNECT >" */
						printf(
							wp_kses( __( 'By clicking "%1$s", you agree to the <a href="%2$s">Terms of Service</a> and understand that <a href="%3$s">some of your data will be passed to external servers</a>.', 'woocommerce-services' ),
								array(
								'a' => array(
									'href' => array(),
								),
							) ),
							esc_html( $content['button_text'] ),
							'https://woocommerce.com/terms-conditions/',
							'https://woocommerce.com/terms-conditions/services-privacy/'
						); ?></p>
					<?php endif; ?>
					<?php if ( isset( $content['button_link'] ) ) : ?>
						<a
							class="wcs-nux__notice-content-button button button-primary"
							href="<?php echo esc_url( $content['button_link'] ); ?>"
						>
							<?php echo esc_html( $content['button_text'] ); ?>
						</a>
					<?php else : ?>
						<button
							class="woocommerce-services__connect-jetpack wcs-nux__notice-content-button button button-primary"
						>
							<?php echo esc_html( $content['button_text'] ); ?>
						</button>
					<?php endif; ?>
				</div>
				<?php if ( $content['should_show_jp'] ) : ?>
					<div class="wcs-nux__notice-jetpack">
						<img src="<?php
						echo esc_url( plugins_url( 'images/jetpack-logo.png', dirname( __FILE__ ) ) );
						?>">
						<p class="wcs-nux__notice-jetpack-text"><?php echo esc_html( __( 'Powered by Jetpack', 'woocommerce-services' ) ); ?></p>
					</div>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Activates Jetpack after an ajax request
		 */
		public function ajax_activate_jetpack() {
			check_ajax_referer( 'wcs_nux_notice' );

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
			check_ajax_referer( 'wcs_nux_notice' );

			$redirect_url = '';
			if ( isset( $_POST['redirect_url'] ) ) {
				$redirect_url = esc_url_raw( wp_unslash( $_POST['redirect_url'] ) );
			}

			$connect_url = Jetpack::init()->build_connect_url(
				true,
				$redirect_url,
				'woocommerce-services'
			);

			// Make sure we always display the after-connection banner
			// after the before_connection button is clicked
			WC_Connect_Options::update_option( self::SHOULD_SHOW_AFTER_CXN_BANNER, true );

			echo esc_url_raw( $connect_url );
			wp_die();
		}
	}
}
