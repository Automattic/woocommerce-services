<?php

class WC_Connect_TaxJar_Integration {

	/**
	 * @var WC_Connect_API_Client
	 */
	public $api_client;

	/**
	 * @var WC_Taxjar_Integration
	 */
	protected $taxjar_integration;

	const TAXJAR_URL = 'https://api.taxjar.com';
	const PROXY_PATH = 'taxjar';

	public function __construct( WC_Connect_API_Client $api_client ) {
		$this->api_client = $api_client;

		add_filter( 'option_woocommerce_taxjar-integration_settings', array( $this, 'override_taxjar_settings' ) );
		add_filter( 'pre_http_request', array( $this, 'proxy_taxjar_requests' ), 10, 3 );
	}

	public function override_taxjar_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = array_merge( $settings, array(
			'api_token'  => '[Managed by WooCommerce Services]',
			'enabled'    => 'yes',
			'store_city' => get_option( 'woocommerce_store_city' ),
			'store_zip'  => get_option( 'woocommerce_store_postcode' ),
		) );

		return $settings;
	}

	public function proxy_taxjar_requests( $pre, $r, $url ) {
		// TODO: get verification requests working through proxy
		if ( 'https://api.taxjar.com/v2/verify' === $url ) {
			return array(
				'response' => array(
					'code' => 200,
				),
				'body' => '',
			);
		}

		// Proxy all TaxJar requests through the WCS server
		if ( strpos( $url, self::TAXJAR_URL ) === 0 ) {
			$taxjar_url  = trailingslashit( self::TAXJAR_URL );
			$wcs_path    = trailingslashit( self::PROXY_PATH );
			$proxy_path  = str_replace( $taxjar_url, $wcs_path, $url );

			return $this->api_client->proxy_request( $proxy_path, $r );
		}

		return $pre;
	}
}