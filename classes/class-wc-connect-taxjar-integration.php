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
		// TODO: check if TaxJar plugin is enabled, abort if so
		// TODO: backup existing tax rates if enabled
	}
}