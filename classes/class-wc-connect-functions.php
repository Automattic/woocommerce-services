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
				( ! empty( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'jetpack/v4/' ) ) || // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
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
		 * Check if current page is a cart page or has woocommerce cart block.
		 *
		 * @return bool
		 */
		public static function is_cart() {
			if ( is_cart() || self::has_cart_block() ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if current page is a checkout page or has woocommerce checkout block.
		 *
		 * @return bool
		 */
		public static function is_checkout() {
			if ( is_checkout() || self::has_checkout_block() ) {
				return true;
			}

			return false;
		}

		/**
		 * Check if current page has woocommerce cart block.
		 *
		 * @return bool
		 */
		public static function has_cart_block() {
			// To support WP < 5.0.0, we need to check if `has_block` exists first as has_block only being introduced on WP 5.0.0.
			if ( function_exists( 'has_block' ) ) {
				return has_block( 'woocommerce/cart' );
			}

			return false;
		}

		/**
		 * Check if current page has woocommerce checkout block.
		 *
		 * @return bool
		 */
		public static function has_checkout_block() {
			// To support WP < 5.0.0, we need to check if `has_block` exists first as has_block only being introduced on WP 5.0.0.
			if ( function_exists( 'has_block' ) ) {
				return has_block( 'woocommerce/checkout' );
			}

			return false;
		}

		/**
		 * Check if current page has woocommerce cart or checkout block.
		 *
		 * @return bool
		 */
		public static function has_cart_or_checkout_block() {
			if ( self::has_checkout_block() || self::has_cart_block() ) {
				return true;
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

			if ( empty( $rates ) ) {
				return false;
			}

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

			echo $header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

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
			$backup_dir = $upload_dir['basedir'] . '/woocommerce_uploads/taxes';

			// Create the protected backup directory if it doesn't exist.
			if ( ! file_exists( $backup_dir ) ) {
				if ( ! wp_mkdir_p( $backup_dir ) ) {
					// Directory could not be created; fall back to the uploads root.
					$backup_dir = $upload_dir['basedir'];
				} else {
					self::protect_backup_directory( $backup_dir );
				}
			} elseif ( ! file_exists( $backup_dir . '/.htaccess' ) ) {
				// Re-create protection files if they were removed.
				self::protect_backup_directory( $backup_dir );
			}

			// Build filename with wp_hash() suffix to prevent URL guessing (same pattern as WC log files).
			$base_name   = 'taxjar-wc_tax_rates-' . gmdate( 'Y-m-d' ) . '-' . time();
			$hash_suffix = wp_hash( $base_name );
			$backed_up   = file_put_contents( $backup_dir . '/' . $base_name . '-' . $hash_suffix . '.csv', $csv ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

			return (bool) $backed_up;
		}

		/**
		 * Creates protection files in the backup directory to prevent direct HTTP access.
		 *
		 * Follows the same pattern used by WooCommerce's ReportCSVExporter::maybe_create_directory().
		 *
		 * @param string $dir The directory to protect.
		 */
		private static function protect_backup_directory( $dir ) {
			$files = array(
				array(
					'base'    => $dir,
					'file'    => '.htaccess',
					'content' => 'DirectoryIndex index.php index.html' . PHP_EOL . 'deny from all',
				),
				array(
					'base'    => $dir,
					'file'    => 'index.html',
					'content' => '',
				),
				array(
					'base'    => $dir,
					'file'    => 'index.php',
					'content' => '<?php' . PHP_EOL . '// Silence is golden.',
				),
			);

			foreach ( $files as $file ) {
				if ( ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'wb' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_read_fopen
					if ( $file_handle ) {
						fwrite( $file_handle, $file['content'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
						fclose( $file_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
					}
				}
			}
		}

		/**
		 * Search the uploads directory and return all backed up
		 * tax rate files.
		 *
		 * @return array|false
		 */
		public static function get_backed_up_tax_rate_files() {
			$upload_dir = wp_upload_dir();
			$backup_dir = $upload_dir['basedir'] . '/woocommerce_uploads/taxes';

			// Attempt to migrate legacy files from the public uploads root into the protected directory.
			if ( ! get_option( 'wcs_tax_backup_files_migrated' ) ) {
				$old_files = glob( $upload_dir['basedir'] . '/taxjar-wc_tax_rates-*.csv' );

				if ( ! empty( $old_files ) ) {
					$dir_ready = file_exists( $backup_dir );

					if ( ! $dir_ready ) {
						$dir_ready = wp_mkdir_p( $backup_dir );
						if ( $dir_ready ) {
							self::protect_backup_directory( $backup_dir );
						}
					}

					if ( $dir_ready ) {
						foreach ( $old_files as $old_file ) {
							$old_basename = pathinfo( $old_file, PATHINFO_FILENAME );

							// Add wp_hash() suffix if the file doesn't already have one (32-char hex at the end).
							if ( ! preg_match( '/-[0-9a-f]{32}$/', $old_basename ) ) {
								$hash_suffix  = wp_hash( $old_basename );
								$new_filename = $old_basename . '-' . $hash_suffix . '.csv';
							} else {
								$new_filename = basename( $old_file );
							}

							$dest = $backup_dir . '/' . $new_filename;
							if ( ! file_exists( $dest ) ) {
								rename( $old_file, $dest ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename
							} else {
								wp_delete_file( $old_file );
							}
						}
					}
					// If $dir_ready is false, legacy files remain in place and are picked up below.
				}

				// Mark migration complete only when no old files remain, so it retries if rename() failed.
				// Check for false explicitly: glob() returns false on filesystem error (not just an empty
				// array), and empty(false) would be true, prematurely marking migration as complete and
				// leaving old publicly-accessible files in the uploads root permanently.
				$remaining_old_files = glob( $upload_dir['basedir'] . '/taxjar-wc_tax_rates-*.csv' );
				if ( false !== $remaining_old_files && empty( $remaining_old_files ) ) {
					update_option( 'wcs_tax_backup_files_migrated', true, false );
				}
			}

			// Collect files from the protected directory and, if migration was not possible, the uploads root.
			$found_files = array_merge(
				glob( $backup_dir . '/taxjar-wc_tax_rates-*.csv' ) ?: array(),
				glob( $upload_dir['basedir'] . '/taxjar-wc_tax_rates-*.csv' ) ?: array()
			);

			if ( empty( $found_files ) ) {
				return false;
			}

			$files = array();
			foreach ( $found_files as $file ) {
				$files[] = basename( $file );
			}

			return $files;
		}
	}
}
