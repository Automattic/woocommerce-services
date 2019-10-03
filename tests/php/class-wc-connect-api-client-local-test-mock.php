<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_API_Client_Local_Test_Mock' ) ) {
	require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php' );

	class WC_Connect_API_Client_Local_Test_Mock extends WC_Connect_API_Client {

		/**
		 * Sends a request to the WooCommerce Services Server
		 *
		 * @param $method
		 * @param $path
		 * @param $body
		 * @return mixed|WP_Error
		 */
		protected function request( $method, $path, $body = array() ) {
			switch( $path ) {
				case '/shipping/label/rates':
					// Return two mock rates.
					return json_decode( '{"success":true,"rates":{"default_box":{"shipment_id":"shp_49e8f4fa2a3943699a4c7281a84a754a","rates":[{"rate_id":"rate_1","service_id":"Mock1","carrier_id":"usps","title":"USPS - Mock 1","rate":12.3,"retail_rate":14.35,"is_selected":false},{"rate_id":"rate_2","service_id":"Mock2","carrier_id":"usps","title":"USPS - Mock 2","rate":4.56,"retail_rate":14.35,"is_selected":false}],"errors":[]}}}' );
				case '/payment/methods':
					// Has credit Card (payment_method_id != 0).
					return json_decode( '{"payment_methods":[{"payment_method_id":123, "name":"mockcard", "card_type":"visa", "card_digits":"1234", "expiry":"2050-01-31"}]}' );
					// Does not have credit Card (payment_method_id == 0)
					//return json_decode( '{"payment_methods":[{"payment_method_id":0, "name":"mockcard", "card_type":"visa", "card_digits":"1234", "expiry":"2050-01-31"}]}' );
				default:
					// Just return empty data for other requests.
					return new stdClass;

			}
		}
	}
}
