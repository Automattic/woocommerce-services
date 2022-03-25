<?php

if ( ! class_exists( 'WC_Connect_Functions' ) ) {
	class WC_Connect_Functions {
		/**
		 * Checks if the potentially expensive Shipping/Tax API requests should be sent
		 * based on the context in which they are initialized.
		 *
		 * @return bool true if the request can be sent, false otherwise
		 */
		public static function should_send_cart_api_request() {
			// Allow if this is an API call to store/cart endpoint. Provides compatibility with WooCommerce Blocks.
			return self::is_store_api_call() || ! (
				// Skip for carts loaded from session in the dashboard.
				( is_admin() && did_action( 'woocommerce_cart_loaded_from_session' ) ) ||
				// Skip during Jetpack API requests.
				( false !== strpos( $_SERVER['REQUEST_URI'], 'jetpack/v4/' ) ) ||
				// Skip during REST API or XMLRPC requests.
				( defined( 'REST_REQUEST' ) || defined( 'REST_API_REQUEST' ) || defined( 'XMLRPC_REQUEST' ) ) ||
				// Skip during Jetpack REST API proxy requests.
				( isset( $_GET['rest_route'] ) && isset( $_GET['_for'] ) && ( 'jetpack' === $_GET['_for'] ) )
			);
		}

		/**
		 * Get the WC Helper authorization information to use with WC Connect Server requests( e.g. site ID, access token).
		 *
		 * @return array|WP_Error
		 */
		public static function get_wc_helper_auth_info() {
			if ( class_exists( 'WC_Helper_Options' ) && is_callable( 'WC_Helper_Options::get' ) ) {
				$helper_auth_data = WC_Helper_Options::get( 'auth' );
			}

			// It's possible for WC_Helper_Options::get() to return false, throw error if this is the case.
			if ( ! $helper_auth_data ) {
				return new WP_Error(
					'missing_wccom_auth',
					__( 'WooCommerce Helper auth is missing', 'woocommerce-services' )
				);
			}
			return $helper_auth_data;
		}

		/**
		 * Check if we are currently in Rest API request for the wc/store/cart or wc/store/checkout API call.
		 *
		 * @return bool
		 */
		public static function is_store_api_call() {
			if ( ! WC()->is_rest_api_request() && empty( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
				return false;
			}
			$rest_route = $GLOBALS['wp']->query_vars['rest_route'];

			// Use regex to check any route that has "wc/store" with any of these text : "cart", "checkout", or "batch"
			// Example : wc/store/v3/batch
			preg_match( '/wc\/store\/v[0-9]{1,}\/(batch|cart|checkout)/', $rest_route, $route_matches, PREG_OFFSET_CAPTURE );

			return ( ! empty( $route_matches ) );
		}

		/**
		 * Check if current page has woocommerce cart or checkout block.
		 *
		 * @return bool
		 */
		public static function has_cart_or_checkout_block() {
			$page = get_post();
			if ( ! $page ) {
				return false;
			}

			$blocks = parse_blocks( $page->post_content );
			if ( ! $blocks ) {
				return false;
			}

			foreach ( $blocks as $block ) {
				$block_name = $block['blockName'];
				if ( 'woocommerce/cart' === $block_name || 'woocommerce/checkout' === $block_name ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Checks whether the current user has permissions to manage shipping labels.
		 *
		 * @return boolean
		 */
		public static function user_can_manage_labels() {
			/**
			 * @since 1.25.14
			 */
			return apply_filters( 'wcship_user_can_manage_labels', current_user_can( 'manage_woocommerce' ) || current_user_can( 'wcship_manage_labels' ) );
		}

		/**
		 * Exports existing tax rates to a CSV and clears the table.
		 *
		 * Ported from TaxJar's plugin.
		 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/42cd4cd0/taxjar-woocommerce.php#L75
		 *
		 * @return boolean
		 */
		public static function backup_existing_tax_rates() {
			global $wpdb;

			// Export Tax Rates
			$rates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
			        ORDER BY tax_rate_order
			        LIMIT %d, %d
			",
					0,
					10000
				)
			);

			ob_start();
			$header =
				__( 'Country Code', 'woocommerce' ) . ',' .
				__( 'State Code', 'woocommerce' ) . ',' .
				__( 'ZIP/Postcode', 'woocommerce' ) . ',' .
				__( 'City', 'woocommerce' ) . ',' .
				__( 'Rate %', 'woocommerce' ) . ',' .
				__( 'Tax Name', 'woocommerce' ) . ',' .
				__( 'Priority', 'woocommerce' ) . ',' .
				__( 'Compound', 'woocommerce' ) . ',' .
				__( 'Shipping', 'woocommerce' ) . ',' .
				__( 'Tax Class', 'woocommerce' ) . "\n";

			echo $header;

			foreach ( $rates as $rate ) {
				if ( $rate->tax_rate_country ) {
					echo esc_attr( $rate->tax_rate_country );
				} else {
					echo '*';
				}

				echo ',';

				if ( $rate->tax_rate_state ) {
					echo esc_attr( $rate->tax_rate_state );
				} else {
					echo '*';
				}

				echo ',';

				$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='postcode' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );

				if ( $locations ) {
					echo esc_attr( implode( '; ', $locations ) );
				} else {
					echo '*';
				}

				echo ',';

				$locations = $wpdb->get_col( $wpdb->prepare( "SELECT location_code FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE location_type='city' AND tax_rate_id = %d ORDER BY location_code", $rate->tax_rate_id ) );
				if ( $locations ) {
					echo esc_attr( implode( '; ', $locations ) );
				} else {
					echo '*';
				}

				echo ',';

				if ( $rate->tax_rate ) {
					echo esc_attr( $rate->tax_rate );
				} else {
					echo '0';
				}

				echo ',';

				if ( $rate->tax_rate_name ) {
					echo esc_attr( $rate->tax_rate_name );
				} else {
					echo '*';
				}

				echo ',';

				if ( $rate->tax_rate_priority ) {
					echo esc_attr( $rate->tax_rate_priority );
				} else {
					echo '1';
				}

				echo ',';

				if ( $rate->tax_rate_compound ) {
					echo esc_attr( $rate->tax_rate_compound );
				} else {
					echo '0';
				}

				echo ',';

				if ( $rate->tax_rate_shipping ) {
					echo esc_attr( $rate->tax_rate_shipping );
				} else {
					echo '0';
				}

				echo ',';

				echo "\n";
			} // End foreach().

			$csv        = ob_get_clean();
			$upload_dir = wp_upload_dir();
			$backed_up  = file_put_contents( $upload_dir['basedir'] . '/taxjar-wc_tax_rates-' . date( 'm-d-Y' ) . '-' . time() . '.csv', $csv );

			return (bool) $backed_up;
		}
	}
}
