<?php

class WC_Connect_TaxJar_Integration {

	/**
	 * @var WC_Connect_API_Client
	 */
	public $api_client;

	const TAXJAR_URL = 'https://api.taxjar.com';

	public function __construct( WC_Connect_API_Client $api_client ) {
		$this->api_client = $api_client;
	}

	public function init() {
		// Only enable WCS TaxJar integration if the official TaxJar plugin isn't active.
		if ( class_exists( 'WC_Taxjar' ) ) {
			return;
		}

		// TODO: check if WCS Taxes are enabled before configuring core tax settings
		$this->configure_tax_settings();

		// TODO: check if WCS Taxes are enabled before backup existing tax rates
		$this->backup_existing_tax_rates();
	}

	/**
	 * Configure WooCommerce core tax settings for TaxJar integration.
	 *
	 * Ported from TaxJar's plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/82bf7c58/includes/class-wc-taxjar-integration.php#L66-L91
	 */
	public function configure_tax_settings() {
		// If TaxJar is enabled and a user disables taxes we renable them
		update_option( 'woocommerce_calc_taxes', 'yes' );

		// Users can set either billing or shipping address for tax rates but not shop
		update_option( 'woocommerce_tax_based_on', 'shipping' );

		// Rate calculations assume tax not included
		update_option( 'woocommerce_prices_include_tax', 'no' );

		// Don't ever set a default customer address
		update_option( 'woocommerce_default_customer_address', '' );

		// Use no special handling on shipping taxes, our API handles that
		update_option( 'woocommerce_shipping_tax_class', '' );

		// API handles rounding precision
		update_option( 'woocommerce_tax_round_at_subtotal', 'no' );

		// Rates are calculated in the cart assuming tax not included
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		// TaxJar returns one total amount, not line item amounts
		update_option( 'woocommerce_tax_display_cart', 'excl' );

		// TaxJar returns one total amount, not line item amounts
		update_option( 'woocommerce_tax_total_display', 'single' );
	}

	/**
	 * Exports existing tax rates to a CSV and clears the table.
	 *
	 * Ported from TaxJar's plugin.
	 * See: https://github.com/taxjar/taxjar-woocommerce-plugin/blob/42cd4cd0/taxjar-woocommerce.php#L75
	 */
	public function backup_existing_tax_rates() {
		global $wpdb;

		// Export Tax Rates
		$rates = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}woocommerce_tax_rates
			ORDER BY tax_rate_order
			LIMIT %d, %d
			",
			0,
			10000
		) );

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

			if ( $rate->tax_rate_country ) {
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

		$csv = ob_get_contents();
		ob_end_clean();
		$upload_dir = wp_upload_dir();
		file_put_contents( $upload_dir['basedir'] . '/taxjar-wc_tax_rates-' . date( 'm-d-Y' ) . '-' . time() . '.csv', $csv );

		// Delete all tax rates
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'woocommerce_tax_rates' );
		$wpdb->query( 'TRUNCATE ' . $wpdb->prefix . 'woocommerce_tax_rate_locations' );
	}
}