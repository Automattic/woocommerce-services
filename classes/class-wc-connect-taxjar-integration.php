<?php

class WC_Connect_TaxJar_Integration {

	public function __construct( ) {
		add_filter( 'option_woocommerce_taxjar-integration_settings', array( $this, 'override_taxjar_settings' ) );
		add_filter( 'pre_http_request', array( $this, 'prevent_taxjar_status_check' ), 10, 3 );
	}

	public function override_taxjar_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = array_merge( $settings, array(
			'api_token'  => '[Managed by WooCommerce Services]',
			'enabled'    => 'yes',
			'store_city' => 'Park City', // TODO: get from new WC address data
			'store_zip'  => '84060',     // TODO: get from new WC address data
		) );

		return $settings;
	}

	public function prevent_taxjar_status_check( $pre, $r, $url ) {
		if ( 'https://api.taxjar.com/v2/verify' === $url ) {
			return array(
				'response' => array(
					'code' => 200,
				),
				'body' => '',
			);
		}

		return $pre;
	}
}