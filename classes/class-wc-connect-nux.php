<?php

if ( ! class_exists( 'WC_Connect_Nux' ) ) {

	class WC_Connect_Nux {
		/**
		 * Jetpack status constants.
		 */
		const JETPACK_NOT_CONNECTED = 'not-connected';
		const JETPACK_OFFLINE_MODE  = 'offline-mode';
		const JETPACK_CONNECTED     = 'connected';

		const IS_NEW_LABEL_USER = 'wcc_is_new_label_user';

		/**
		 * Option name for dismissing success banner
		 * after the JP connection flow
		 */
		const SHOULD_SHOW_AFTER_CXN_BANNER  = 'should_display_nux_after_jp_cxn_banner';
		const SHOULD_SHOW_CONTEXTUAL_BANNER = 'should_display_nux_contextual_banner';

		/**
		 * @var WC_Connect_Tracks
		 */
		protected $tracks;

		/**
		 * @var WC_Connect_Shipping_Label
		 */
		private $shipping_label;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_Connect_Payment_Methods_Store
		 */
		protected $payment_methods_store;

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;


		function __construct(
			WC_Connect_Tracks $tracks,
			WC_Connect_Shipping_Label $shipping_label,
			WC_Connect_Service_Settings_Store $service_settings_store,
			WC_Connect_Payment_Methods_Store $payment_methods_store,
			WC_Connect_Service_Schemas_Store $service_schemas_store
		) {
			$this->tracks                 = $tracks;
			$this->shipping_label         = $shipping_label;
			$this->service_settings_store = $service_settings_store;
			$this->payment_methods_store  = $payment_methods_store;
			$this->service_schemas_store  = $service_schemas_store;

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
			$notices            = $this->get_notice_states();
			$notices[ $notice ] = true;
			update_user_meta( get_current_user_id(), 'wc_connect_nux_notices', $notices );
		}

		public function ajax_dismiss_notice() {
			if ( empty( $_POST['dismissible_id'] ) ) {
				return;
			}

			check_ajax_referer( 'wc_connect_dismiss_notice', 'nonce' );
			$this->dismiss_notice( sanitize_key( $_POST['dismissible_id'] ) );
			wp_die();
		}

		private function init_pointers() {
			add_filter( 'wc_services_pointer_post.php', array( $this, 'register_order_page_labels_pointer' ) );
			add_filter( 'wc_services_pointer_post.php', array( $this, 'register_new_carrier_dhl_pointer' ) );
		}

		public function show_pointers( $hook ) {
			/*
			Get admin pointers for the current admin page.
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
			$valid_pointers     = array();

			foreach ( $pointers as $pointer ) {
				if ( ! in_array( $pointer['id'], $dismissed_pointers, true ) ) {
					$valid_pointers[] = $pointer;
				}
			}

			if ( empty( $valid_pointers ) ) {
				return;
			}

			wp_enqueue_style( 'wp-pointer' );
			wp_localize_script( 'wc_services_admin_pointers', 'wcServicesAdminPointers', $valid_pointers );
			wp_enqueue_script( 'wc_services_admin_pointers' );
		}

		public function get_dismissed_pointers() {
			$data = get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true );
			if ( is_string( $data ) && 0 < strlen( $data ) ) {
				return explode( ',', $data );
			}

			return array();
		}

		/**
		 * Dismiss a WP pointer for the current user.
		 *
		 * @param string $pointer_to_dismiss Pointer ID to dismiss for the current user
		 */
		public function dismiss_pointer( $pointer_to_dismiss ) {
			$dismissed_pointers = $this->get_dismissed_pointers();

			if ( in_array( $pointer_to_dismiss, $dismissed_pointers, true ) ) {
				return;
			}

			$dismissed_pointers[] = $pointer_to_dismiss;
			$dismissed_data       = implode( ',', $dismissed_pointers );
			update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', $dismissed_data );
		}

		public function is_new_labels_user() {
			$is_new_user = get_transient( self::IS_NEW_LABEL_USER );
			if ( false === $is_new_user ) {
				global $wpdb;

				$results     = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT meta_key FROM {$wpdb->postmeta} WHERE meta_key = %s LIMIT 1",
						'wc_connect_labels'
					)
				);
				$is_new_user = 0 === count( $results ) ? 'yes' : 'no';
				set_transient( self::IS_NEW_LABEL_USER, $is_new_user );
			}

			return 'yes' === $is_new_user;
		}

		public function register_order_page_labels_pointer( $pointers ) {
			// If the user is not new to labels, we should just dismiss this pointer
			if ( ! $this->is_new_labels_user() ) {
				$this->dismiss_pointer( 'wc_services_labels_metabox' );

				return $pointers;
			}

			global $post;

			if ( ! $this->shipping_label->should_show_meta_box( $post ) ) {
				return $pointers;
			}

			$supported_carriers = array( 'USPS' );
			if ( $this->shipping_label->is_dhl_express_available() ) {
				$supported_carriers[] = 'DHL';
			}

			$pointers[] = array(
				'id'      => 'wc_services_labels_metabox',
				'target'  => '#woocommerce-order-label .button',
				'options' => array(
					'content'  => sprintf(
						'<h3>%s</h3><p>%s</p>',
						__( 'Discounted Shipping Labels', 'woocommerce-services' ),
						sprintf( __( "When you're ready, purchase and print discounted labels from %s right here.", 'woocommerce-services' ), implode( ' or ', $supported_carriers ) )
					),
					'position' => array(
						'edge'  => 'top',
						'align' => 'left',
					),
				),
				'dim'     => true,
			);

			return $pointers;
		}

		public function register_new_carrier_dhl_pointer( $pointers ) {
			// new user? no need to show this alert, `wc_services_labels_metabox` will take care of communicating about DHL
			if ( $this->is_new_labels_user() ) {
				$this->dismiss_pointer( 'wc_services_new_carrier_dhl_express' );

				return $pointers;
			}

			// existing user? figure out if the order supports DHL, then let them know DHL is a new carrier!
			if ( ! $this->shipping_label->is_order_dhl_express_eligible() ) {
				return $pointers;
			}

			$pointers[] = array(
				'id'      => 'wc_services_new_carrier_dhl_express',
				'target'  => '#woocommerce-order-label .button',
				'options' => array(
					'content'  => sprintf(
						'<h3>%s</h3><p>%s</p>',
						__( 'Discounted DHL Shipping Labels', 'woocommerce-services' ),
						__( 'WooCommerce Shipping now supports DHL labels for international shipments. Purchase and print discounted labels from DHL and USPS right here.', 'woocommerce-services' )
					),
					'position' => array(
						'edge'  => 'top',
						'align' => 'left',
					),
				),
				'dim'     => true,
			);

			return $pointers;
		}

		public static function get_banner_type_to_display( $status = array() ) {
			if ( ! isset( $status['jetpack_connection_status'] ) ) {
				return false;
			}

			/*
			The NUX Flow:
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
				case self::JETPACK_NOT_CONNECTED:
					return 'before_jetpack_connection';
				case self::JETPACK_CONNECTED:
				case self::JETPACK_OFFLINE_MODE:
					// Priority 1: Standard "after connection" banner (if pending from NUX flow).
					// This banner also handles initial TOS acceptance if coming from the NUX connection flow.
					if ( isset( $status['should_display_after_cxn_banner'] ) && $status['should_display_after_cxn_banner'] ) {
						return 'after_jetpack_connection';
					}

					// Priority 2: TOS acceptance banner (if Jetpack connected, but TOS not yet accepted,
					// and the standard "after connection" banner is not pending).
					if ( isset( $status['tos_accepted'] ) && ! $status['tos_accepted'] &&
						isset( $status['can_accept_tos'] ) && $status['can_accept_tos'] ) {
						return 'tos_only_banner';
					}

					// For existing users: if TOS accepted, after_cxn_banner done, but contextual_banner flag not yet set, set it now.
					if ( isset( $status['tos_accepted'] ) && $status['tos_accepted'] &&
						( ! isset( $status['should_display_after_cxn_banner'] ) || ! $status['should_display_after_cxn_banner'] ) &&
						( ! isset( $status['should_display_contextual_banner'] ) || ! $status['should_display_contextual_banner'] )
					) {
						// This user is eligible for contextual banners but the flag isn't set. Set it now.
						WC_Connect_Options::update_option( self::SHOULD_SHOW_CONTEXTUAL_BANNER, true );
						// Update the status for the current execution path, so Priority 3 check below can pick it up.
						$status['should_display_contextual_banner'] = true;
					}

					// Fallback for non-US stores if contextual banner flag is not set
					if ( isset( $status['tos_accepted'] ) && $status['tos_accepted'] &&
						( ! isset( $status['should_display_after_cxn_banner'] ) || ! $status['should_display_after_cxn_banner'] ) &&
						( ! isset( $status['should_display_contextual_banner'] ) || ! $status['should_display_contextual_banner'] )
					) {
						$is_us_store = ( isset( $status['store_country'] ) && 'US' === $status['store_country'] );
						if ( ! $is_us_store ) {
							return 'after_cxn_non_us';
						}
					}

					// Priority 3: Contextual banners (if standard "after connection" is done or was not applicable,
					// TOS is accepted, and the contextual flag is set - either previously or by the block above).
					if ( isset( $status['should_display_contextual_banner'] ) && $status['should_display_contextual_banner'] ) {
						// Determine which specific contextual banner to show.
						$is_us_store = ( isset( $status['store_country'] ) && 'US' === $status['store_country'] );

						if ( $is_us_store ) {
							if ( isset( $status['is_wcs_shipping_plugin_active'] ) && ! $status['is_wcs_shipping_plugin_active'] ) {
								return 'after_cxn_us_no_wcs_plugin';
							} else {
								return 'after_cxn_us_with_wcs_plugin';
							}
						} else {
							return 'after_cxn_non_us';
						}
					}

					return false; // All NUX banners handled or no NUX banner needed for this state.
				default:
					return false;
			}
		}

		public function get_jetpack_install_status() {
			if ( WC_Connect_Jetpack::is_offline_mode() ) {
				// activated, and dev mode on
				return self::JETPACK_OFFLINE_MODE;
			}

			// dev mode off, check if connected
			if ( ! WC_Connect_Jetpack::is_connected() ) {
				return self::JETPACK_NOT_CONNECTED;
			}

			return self::JETPACK_CONNECTED;
		}

		public function should_display_nux_notice_on_screen( $screen ) {
			if ( // Display if on any of these admin pages.
				( // Products list.
					'product' === $screen->post_type
					&& 'edit' === $screen->base
				)
				|| ( // Orders list and edit order page when not using HPOS.
					'shop_order' === $screen->post_type
					&& in_array( $screen->base, array( 'edit', 'post' ), true )
					)
				|| ( // Orders list and edit order page when using HPOS.
					wc_get_page_screen_id( 'shop_order' ) === $screen->id
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

		/**
		 * https://developers.taxjar.com/api/reference/#countries
		 */
		public function is_taxjar_supported_country( $country_code ) {
			$taxjar_supported_countries = array_merge(
				array(
					'US',
					'CA',
					'AU',
				),
				WC()->countries->get_european_union_countries()
			);

			return in_array( $country_code, $taxjar_supported_countries );
		}

		public function should_display_nux_notice_for_current_store_locale() {
			$store_country = WC()->countries->get_base_country();

			$supports_taxes    = $this->is_taxjar_supported_country( $store_country );
			$supports_shipping = in_array( $store_country, array( 'US', 'CA' ) );

			return $supports_shipping || $supports_taxes;
		}

		public function get_feature_list_for_country( $country ) {
			$feature_list   = false;
			$supports_taxes = $this->is_taxjar_supported_country( $country );

			$is_ppec_active    = is_plugin_active( 'woocommerce-gateway-paypal-express-checkout/woocommerce-gateway-paypal-express-checkout.php' );
			$ppec_settings     = get_option( 'woocommerce_ppec_paypal_settings', array() );
			$supports_payments = $is_ppec_active && ( ! isset( $ppec_settings['enabled'] ) || 'yes' === $ppec_settings['enabled'] );

			if ( $supports_payments && $supports_taxes ) {
				$feature_list = __( 'automated tax calculation and smoother payment setup', 'woocommerce-services' );
			} elseif ( $supports_payments ) {
				$feature_list = __( 'smoother payment setup', 'woocommerce-services' );
			} elseif ( $supports_taxes ) {
				$feature_list = __( 'automated tax calculation', 'woocommerce-services' );
			}

			return $feature_list;
		}

		public function get_jetpack_redirect_url() {
			$full_path = add_query_arg( array() );
			// Remove [...]/wp-admin so we can use admin_url().
			$new_index = strpos( $full_path, '/wp-admin' ) + strlen( '/wp-admin' );
			$path      = substr( $full_path, $new_index );

			return esc_url( admin_url( $path ) );
		}

		public function set_up_nux_notices() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			// Check for plugin install and activate permissions to handle Jetpack on multisites:
			// Admins might not be able to install or activate plugins, but Jetpack might already have been installed by a superadmin.
			// If this is the case, the admin can connect the site on their own, and should be able to use WCS as ususal
			$jetpack_install_status = $this->get_jetpack_install_status();

			// Ensure is_plugin_active() is available for WCS check
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$is_wcs_shipping_plugin_active = is_plugin_active( 'woocommerce-shipping/woocommerce-shipping.php' );
			$store_country                 = WC()->countries->get_base_country();

			$banner_to_display = self::get_banner_type_to_display(
				array(
					'jetpack_connection_status'        => $jetpack_install_status,
					'tos_accepted'                     => WC_Connect_Options::get_option( 'tos_accepted' ),
					'can_accept_tos'                   => WC_Connect_Jetpack::is_current_user_connection_owner() || WC_Connect_Jetpack::is_offline_mode(),
					'should_display_after_cxn_banner'  => WC_Connect_Options::get_option( self::SHOULD_SHOW_AFTER_CXN_BANNER ),
					'should_display_contextual_banner' => WC_Connect_Options::get_option( self::SHOULD_SHOW_CONTEXTUAL_BANNER ),
					'store_country'                    => $store_country,
					'is_wcs_shipping_plugin_active'    => $is_wcs_shipping_plugin_active,
				)
			);

			switch ( $banner_to_display ) {
				case 'before_jetpack_connection':
					wp_enqueue_script( 'wc_connect_banner' );
					add_action(
						'admin_post_register_woocommerce_services_jetpack',
						array( $this, 'register_woocommerce_services_jetpack' )
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
				case 'after_cxn_us_no_wcs_plugin':
					// Enqueue the migration modal assets specifically for this banner on the plugins page.
					$plugin_version = WC_Connect_Loader::get_wcs_version();
					// Use the public static method from WC_Connect_Loader
					$base_url = WC_Connect_Loader::get_wc_connect_base_url(); // Assuming get_wc_connect_base_url is static

					wp_register_style( 'wcst_wcshipping_migration_admin_notice', $base_url . 'woocommerce-services-wcshipping-migration-admin-notice-' . $plugin_version . '.css', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
					// Add 'wp-element' and 'wc_connect_admin' dependency for React and base script
					wp_register_script( 'wcst_wcshipping_migration_admin_notice', $base_url . 'woocommerce-services-wcshipping-migration-admin-notice-' . $plugin_version . '.js', array( 'wc_connect_admin', 'wp-element' ), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion

					// Localize script data - MATCHING the original register_wcshipping_migration_modal
					// Note: The modal primarily uses data-args, localization is minimal here.
					wp_localize_script(
						'wcst_wcshipping_migration_admin_notice',
						'wcsPluginData', // Ensure this matches the expected object name in the script
						array(
							'assetPath'       => $base_url,
							'adminPluginPath' => admin_url( 'plugins.php' ),
						)
					);

					// Enqueue scripts/styles needed for the banner and modal
					wp_enqueue_script( 'wc_connect_admin' ); // Ensure base script is loaded first
					wp_enqueue_script( 'wcst_wcshipping_migration_admin_notice' );
					wp_enqueue_style( 'wcst_wcshipping_migration_admin_notice' );
					wp_enqueue_style( 'wc_connect_banner' );

					// Add the action to render the notice and container div
					add_action(
						'admin_notices',
						function () use ( $banner_to_display ) {
							// Instantiate settings classes HERE, inside the closure, using stored dependencies
							$account_settings  = new WC_Connect_Account_Settings(
								$this->service_settings_store,
								$this->payment_methods_store
							);
							$packages_settings = new WC_Connect_Package_Settings(
								$this->service_settings_store,
								$this->service_schemas_store
							);

							// Prepare the data for the data-args attribute by calling get()
							$container_data_args    = array(
								'nonce'            => wp_create_nonce( 'wp_rest' ),
								'baseURL'          => get_rest_url(),
								'accountSettings'  => $account_settings->get(),  // Get REAL data
								'packagesSettings' => $packages_settings->get(), // Get REAL data
							);
							$encoded_container_args = wp_json_encode( $container_data_args );

							// Echo the container div needed for the modal React component BEFORE the banner.
							// Add 'display: none;' initially; the script should manage visibility.
							printf(
								'<div id="wcst_wcshipping_migration_admin_notice_feature_announcement" data-args="%s" style="display: none;"></div>',
								esc_attr( $encoded_container_args ) // Use the REAL encoded data
							);

							// Now show the banner itself
							$this->show_contextual_after_connection_banner( $banner_to_display );
						}
					);
					break; // End case 'after_cxn_us_no_wcs_plugin'

				case 'after_cxn_us_with_wcs_plugin':
				case 'after_cxn_non_us':
					wp_enqueue_style( 'wc_connect_banner' );
					// Using a closure to correctly pass the argument to the new handler method.
					add_action(
						'admin_notices',
						function () use ( $banner_to_display ) {
							$this->show_contextual_after_connection_banner( $banner_to_display );
						}
					);
					break;
			}

			add_action( 'wp_ajax_wc_connect_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );
		}

		public function show_banner_before_connection() {
			if ( get_option( 'wcs_nux_any_banner_shown', false ) ) {
				return;
			}

			if ( ! $this->should_display_nux_notice_for_current_store_locale() ) {
				return;
			}

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

			$country = WC()->countries->get_base_country();
			/* translators: %s: list of features, potentially comma separated */
			$description_base = __( "WooCommerce Tax is almost ready to go! Once you connect your site to WordPress.com you'll have access to %s.", 'woocommerce-services' );
			$feature_list     = $this->get_feature_list_for_country( $country );
			$banner_content   = array(
				'title'             => __( 'Connect your site to activate WooCommerce Tax', 'woocommerce-services' ),
				'description'       => sprintf( $description_base, $feature_list ),
				'button_text'       => __( 'Connect', 'woocommerce-services' ),
				'image_url'         => plugins_url( 'images/wcs-notice.png', __DIR__ ),
				'should_show_terms' => true,
			);

			update_option( 'wcs_nux_any_banner_shown', true );

			$this->show_nux_banner( $banner_content );
		}

		public function show_banner_after_connection() {
			if ( get_option( 'wcs_nux_any_banner_shown', false ) ) {
				return;
			}

			if ( ! $this->should_display_nux_notice_for_current_store_locale() ) {
				return;
			}

			if ( ! $this->should_display_nux_notice_on_screen( get_current_screen() ) ) {
				return;
			}

			// Did the user just dismiss?
			if ( isset( $_GET['wcs-nux-notice'] ) && 'dismiss' === $_GET['wcs-nux-notice'] ) {
				// Delete the flag for this banner
				WC_Connect_Options::delete_option( self::SHOULD_SHOW_AFTER_CXN_BANNER );
				// Set the flag for the next contextual banner
				WC_Connect_Options::update_option( self::SHOULD_SHOW_CONTEXTUAL_BANNER, true );
				delete_option( 'wcs_nux_any_banner_shown' );
				wp_safe_redirect( remove_query_arg( 'wcs-nux-notice' ) );
				exit;
			}

			// By going through the connection process, the user has accepted our TOS
			WC_Connect_Options::update_option( 'tos_accepted', true );

			$this->tracks->opted_in( 'connection_banner' );

			$country = WC()->countries->get_base_country();
			/* translators: %s: list of features, potentially comma separated */
			$description_base = __( 'You can now enjoy %s.', 'woocommerce-services' );
			$feature_list     = $this->get_feature_list_for_country( $country );

			update_option( 'wcs_nux_any_banner_shown', true );

			$this->show_nux_banner(
				array(
					'title'             => __( 'Setup complete.', 'woocommerce-services' ),
					'description'       => esc_html( sprintf( $description_base, $feature_list ) ),
					'button_text'       => __( 'Got it, thanks!', 'woocommerce-services' ),
					'button_link'       => add_query_arg(
						array(
							'wcs-nux-notice' => 'dismiss',
						)
					),
					'image_url'         => plugins_url(
						'images/wcs-notice.png',
						__DIR__
					),
					'should_show_terms' => false,
				)
			);
		}

		public function show_contextual_after_connection_banner( $banner_type ) {
			if ( get_option( 'wcs_nux_any_banner_shown', false ) ) {
				return;
			}

			$screen = get_current_screen();

			// This specific banner should only appear on the plugins page.
			if ( ! $screen || 'plugins' !== $screen->base ) {
				return;
			}

			// Still respect the store locale check.
			if ( ! $this->should_display_nux_notice_for_current_store_locale() ) {
				return;
			}

			// Did the user just dismiss?
			if ( isset( $_GET['wcs-nux-notice'] ) && 'dismiss' === $_GET['wcs-nux-notice'] ) {
				// Delete the flag for this contextual banner
				WC_Connect_Options::delete_option( self::SHOULD_SHOW_CONTEXTUAL_BANNER );
				delete_option( 'wcs_nux_any_banner_shown' );
				wp_safe_redirect( remove_query_arg( 'wcs-nux-notice' ) );
				exit;
			}

			// By going through the connection process, the user has accepted our TOS
			WC_Connect_Options::update_option( 'tos_accepted', true );

			// Using a generic tracks event, can be made more specific if needed.
			$this->tracks->opted_in( 'contextual_connection_banner_viewed' );

			update_option( 'wcs_nux_any_banner_shown', true );

			$banner_title       = '';
			$banner_description = '';
			$banner_button_text = '';
			$banner_button_link = null;

			update_option( 'wcshipping_migration_state', '0' );
			switch ( $banner_type ) {
				case 'after_cxn_us_no_wcs_plugin':
					$banner_title       = __( 'WooCommerce Shipping & Tax has been renamed to WooCommerce Tax', 'woocommerce-services' );
					$banner_description = __( 'Your tax functionality will continue to work as expected. The shipping functionality in this plugin will be discontinued on September 1, 2025. Please migrate to the new WooCommerce Shipping extension to get discounted labels for UPS, USPS, DHL Express— and more coming soon!', 'woocommerce-services' );
					$banner_button_text = __( 'Try WooCommerce Shipping ', 'woocommerce-services' );
					// Ensure this line uses the special trigger value:
					$banner_button_link = '#trigger-migration-modal';
					break;
				case 'after_cxn_us_with_wcs_plugin':
					$banner_title       = __( 'WooCommerce Shipping & Tax has been renamed to WooCommerce Tax', 'woocommerce-services' );
					$banner_description = __( 'Your tax functionality will continue to work as expected. Use WooCommerce Shipping to access deeply discounted UPS, USPS, and DHL shipping labels, reliable shipments, and on-time delivery options.', 'woocommerce-services' );
					$banner_button_text = __( 'Ship with UPS on WooCommerce', 'woocommerce-services' );
					$banner_button_link = 'https://woocommerce.com/document/woocommerce-shipping/#creating-shipping-labels';
					break;
				case 'after_cxn_non_us':
					$banner_title       = __( 'WooCommerce Shipping & Tax has been renamed to WooCommerce Tax', 'woocommerce-services' );
					$banner_description = __( 'Your tax functionality will continue to work as expected. No action is required.', 'woocommerce-services' );
					$banner_button_text = __( 'Close', 'woocommerce-services' );
					$banner_button_link = add_query_arg(
						array(
							'wcs-nux-notice' => 'dismiss',
						)
					);
					break;
				default:
					// Fallback for an unknown banner type, though this shouldn't be reached with current logic.
					return;
			}

			$this->show_nux_banner(
				array(
					'title'             => $banner_title,
					'description'       => esc_html( $banner_description ),
					'button_text'       => $banner_button_text,
					'button_link'       => $banner_button_link,
					'image_url'         => plugins_url(
						'images/wcs-notice.png',
						__DIR__
					),
					'should_show_terms' => false,
				)
			);
		}

		public function show_tos_banner() {
			if ( get_option( 'wcs_nux_any_banner_shown', false ) ) {
				return;
			}

			if ( ! $this->should_display_nux_notice_for_current_store_locale() ) {
				return;
			}

			if ( ! $this->should_display_nux_notice_on_screen( get_current_screen() ) ) {
				return;
			}

			if ( isset( $_GET['wcs-nux-tos'] ) && 'accept' === $_GET['wcs-nux-tos'] ) {
				WC_Connect_Options::update_option( 'tos_accepted', true );
				// Signal that the contextual banner can now be shown
				WC_Connect_Options::update_option( self::SHOULD_SHOW_CONTEXTUAL_BANNER, true );

				$this->tracks->opted_in( 'tos_banner' );

				delete_option( 'wcs_nux_any_banner_shown' );

				wp_safe_redirect( remove_query_arg( 'wcs-nux-tos' ) );
				exit;
			}

			$country = WC()->countries->get_base_country();
			/* translators: %s: list of features, potentially comma separated */
			$description_base = __( "WooCommerce Tax is almost ready to go! Once you connect your site to WordPress.com you'll have access to %s.", 'woocommerce-services' );
			$feature_list     = $this->get_feature_list_for_country( $country );

			update_option( 'wcs_nux_any_banner_shown', true );

			$this->show_nux_banner(
				array(
					'title'             => __( 'Connect your site to activate WooCommerce Tax', 'woocommerce-services' ),
					'description'       => esc_html( sprintf( $description_base, $feature_list ) ),
					'button_text'       => __( 'Connect', 'woocommerce-services' ),
					'button_link'       => add_query_arg(
						array(
							'wcs-nux-tos' => 'accept',
						)
					),
					'image_url'         => plugins_url(
						'images/wcs-notice.png',
						__DIR__
					),
					'should_show_terms' => true,
				)
			);
		}

		public function show_nux_banner( $content ) {
			if ( isset( $content['dismissible_id'] ) && $this->is_notice_dismissed( sanitize_key( $content['dismissible_id'] ) ) ) {
				return;
			}

			?>
			<div class="notice wcs-nux__notice <?php echo isset( $content['dismissible_id'] ) ? 'is-dismissible' : ''; ?>">
				<div class="wcs-nux__notice-logo <?php echo isset( $content['compact_logo'] ) && $content['compact_logo'] ? 'is-compact' : ''; ?>">
					<img class="wcs-nux__notice-logo-graphic" src="<?php echo esc_url( $content['image_url'] ); ?>">
				</div>
				<div class="wcs-nux__notice-content">
					<h1 class="wcs-nux__notice-content-title">
						<?php echo esc_html( $content['title'] ); ?>
					</h1>
					<p class="wcs-nux__notice-content-text">
						<?php echo esc_html( $content['description'] ); ?>
					</p>
					<?php if ( isset( $content['should_show_terms'] ) && $content['should_show_terms'] ) : ?>
						<p class="wcs-nux__notice-content-tos">
							<?php
							/* translators: %1$s example values include "Install Jetpack and CONNECT >", "Activate Jetpack and CONNECT >", "CONNECT >" */
							printf(
								wp_kses(
									__( 'By clicking "%1$s", you agree to our <a href="%2$s">Terms of Service</a> and have read our <a href="%3$s">Privacy Policy</a>.', 'woocommerce-services' ),
									array(
										'a' => array(
											'href' => array(),
										),
									)
								),
								esc_html( $content['button_text'] ),
								'https://wordpress.com/tos/',
								'https://automattic.com/privacy/'
							);
							?>
						</p>
					<?php endif; ?>
					<?php if ( isset( $content['button_link'] ) ) : ?>
						<?php // Check for the special modal trigger value ?>
						<?php if ( '#trigger-migration-modal' === $content['button_link'] ) : ?>
							<button
								type="button"
								id="wcst-wcshipping-migration-notice__click" <?php // Ensure this ID matches what the JS expects ?>
								class="wcs-nux__notice-content-button button button-primary"
							>
								<?php echo esc_html( $content['button_text'] ); ?>
							</button>
						<?php else : ?>
							<a
								class="wcs-nux__notice-content-button button button-primary"
								href="<?php echo esc_url( $content['button_link'] ); ?>"
							>
								<?php echo esc_html( $content['button_text'] ); ?>
							</a>
						<?php endif; ?>
					<?php else : ?>
						<form action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="register_woocommerce_services_jetpack"/>
							<input type="hidden" name="redirect_url"
									value="<?php echo esc_url( $this->get_jetpack_redirect_url() ); ?>"/>
							<?php wp_nonce_field( 'wcs_nux_notice' ); ?>
							<button
								type="submit"
								class="woocommerce-services__connect-jetpack wcs-nux__notice-content-button button button-primary"
							>
								<?php echo esc_html( $content['button_text'] ); ?>
							</button>
						</form>
					<?php endif; ?>
				</div>
			</div>
			<?php
			if ( isset( $content['dismissible_id'] ) ) :
				// Add handler for dismissing banner. Only supports a single banner at a time
				wp_enqueue_script( 'wp-util' );
				?>
				<script>
					(
						function ($) {
							$('.wcs-nux__notice').on('click', '.notice-dismiss', function () {
								wp.ajax.post({
									action: 'wc_connect_dismiss_notice',
									dismissible_id: "<?php echo esc_js( $content['dismissible_id'] ); ?>",
									nonce: "<?php echo esc_js( wp_create_nonce( 'wc_connect_dismiss_notice' ) ); ?>"
								})
							})
						}
					)(jQuery)
				</script>
				<?php
			endif;
		}

		/**
		 * Connects the site to Jetpack.
		 */
		public function register_woocommerce_services_jetpack() {
			check_admin_referer( 'wcs_nux_notice' );

			$redirect_url = '';
			if ( isset( $_POST['redirect_url'] ) ) {
				$redirect_url = esc_url_raw( wp_unslash( $_POST['redirect_url'] ) );
			}

			// Make sure we always display the after-connection banner
			// after the before_connection button is clicked
			WC_Connect_Options::update_option( self::SHOULD_SHOW_AFTER_CXN_BANNER, true );
			// Ensure the contextual banner flag is not set prematurely
			WC_Connect_Options::delete_option( self::SHOULD_SHOW_CONTEXTUAL_BANNER );

			WC_Connect_Jetpack::connect_site( $redirect_url );
		}
	}
}
