<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Debug_Tools' ) ) {

	class WC_Connect_Debug_Tools {

		function __construct( WC_Connect_API_Client $api_client ) {
			$this->api_client = $api_client;

			add_filter( 'woocommerce_debug_tools', array( $this, 'woocommerce_debug_tools' ) );
		}

		function woocommerce_debug_tools( $tools ) {
			$tools['test_wcc_connection'] = array(
				'name'     => __( 'Test your WooCommerce Shipping & Tax connection', 'woocommerce-services' ),
				'button'   => __( 'Test Connection', 'woocommerce-services' ),
				'desc'     => __( 'This will test your WooCommerce Shipping & Tax connection to ensure everything is working correctly', 'woocommerce-services' ),
				'callback' => array( $this, 'test_connection' ),
			);

			/**
			 * Only show this tool for stores not based in California
			 */
			if ( 'CA' !== WC()->countries->get_base_state() ) {
				$tools['delete_ca_taxes'] = array(
					'name'     => __( 'Delete California tax rates', 'woocommerce-services' ),
					'button'   => __( 'Delete CA tax rates', 'woocommerce-services' ),
					'desc'     => sprintf( '<strong class="red">%1$s</strong> %2$s %3$s %4$s <a href="https://woocommerce.com/document/woocommerce-shipping-and-tax/woocommerce-tax/#jan-2022-ca-notice" target="_blank">%5$s</a>', __( 'Note:', 'woocommerce-services' ), __( 'This option will delete ALL of your "CA" tax rates where the tax name ends with " Tax" (case insensitive).', 'woocommerce-services' ), '<br>', __( 'A backup CSV of all existing tax rates will be made before the deletion process runs.', 'woocommerce-services' ), __( 'Additional information.', 'woocommerce-services' ) ),
					'callback' => array( $this, 'delete_california_tax_rates' ),
				);
			}

			/**
			 * Only show when object cache is disabled - the tool doesn't work when object cache is enabled.
			 */
			if ( ! wp_using_ext_object_cache() ) {
				$tools['delete_cached_tax_server_responses'] = array(
					'name'     => __( 'Delete WooCommerce Tax cached tax rate responses', 'woocommerce-services' ),
					'button'   => __( 'Delete cached Tax transients', 'woocommerce-services' ),
					'desc'     => __( 'Deletes the all the transients in your database that represent cached Tax Rates responses', 'woocommerce-services' ),
					'callback' => array( $this, 'delete_cached_tax_server_responses' ),
				);
			}

			return $tools;
		}

		function test_connection() {
			$test_request = $this->api_client->auth_test();
			if ( $test_request && ! is_wp_error( $test_request ) && $test_request->authorized ) {
				echo '<div class="updated inline"><p>' . esc_html__( 'Your site is successfully communicating to the WooCommerce Shipping & Tax API.', 'woocommerce-services' ) . '</p></div>';
			} else {
				echo '<div class="error inline"><p>'
				. esc_html__( 'ERROR: Your site has a problem connecting to the WooCommerce Shipping & Tax API. Please make sure your Jetpack connection is working.', 'woocommerce-services' )
				. '</p></div>';
			}
		}

		/**
		 * Back up all existing tax rates from the database in a CSV file.
		 * Then, if successfully backed up, loop through the tax rates
		 * in the database and delete rates where:
		 * tax_rate_country = 'US' and
		 * tax_rate_state = 'CA' and
		 * tax_rate_name LIKE '% Tax'
		 *
		 * @return void
		 */
		function delete_california_tax_rates() {
			$backed_up = WC_Connect_Functions::backup_existing_tax_rates();

			if ( ! $backed_up ) {
				echo '<div class="error inline"><p>';
				echo esc_html__( 'ERROR: The "CA" tax rate deletion process was cancelled because the existing tax rates could not be backed up.', 'woocommerce-services' );
				echo '</p></div>';

				return;
			}

			global $wpdb;

			$found_ca_rates = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
			        WHERE tax_rate_country = %s AND tax_rate_state = %s AND tax_rate_name LIKE '% Tax'
			        ",
					'US',
					'CA'
				),
				ARRAY_A
			);

			/**
			 * If no rates were found, output a message and return
			 */
			if ( empty( $found_ca_rates ) ) {
				echo '<div class="updated inline"><p>';
				echo esc_html__( 'No "CA" tax rates were found.', 'woocommerce-services' );
				echo '</p></div>';

				return;
			}

			$deleted_count = 0;
			foreach ( $found_ca_rates as $rate ) {
				if ( empty( $rate['tax_rate_id'] ) ) {
					continue;
				}

				WC_Tax::_delete_tax_rate( $rate['tax_rate_id'] );
				$deleted_count ++;
			}

			echo '<div class="updated inline"><p>';
			echo sprintf( esc_html__( 'Successfully deleted %1$d rows from the database.', 'woocommerce-services' ), intval( $deleted_count ) );
			echo '</p></div>';
		}

		/**
		 * Deletes the all the transients in the database that represent cached Tax Rates responses.
		 *
		 * @return void
		 */
		function delete_cached_tax_server_responses() {
			global $wpdb;

			$deleted_count = absint(
				$wpdb->query(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE '%tj\_tax\_%';"
				)
			);

			echo '<div class="updated inline"><p>';
			echo sprintf( esc_html__( 'Successfully deleted %1$d transients from the database.', 'woocommerce-services' ), intval( $deleted_count ) );
			echo '</p></div>';
		}
	}
}
