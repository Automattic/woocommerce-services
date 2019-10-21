<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Rates_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Rates_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)/rates';

	private function has_customs_data( $package ) {
		return isset( $package['contents_type'] );
	}

	/**
	 *
	 * @param WP_REST_Request $request - See WC_Connect_API_Client::get_label_rates()
	 * @return array|WP_Error
	 */
	public function post( $request ) {
		$payload = $request->get_json_params();
		$payload[ 'payment_method_id' ] = $this->settings_store->get_selected_payment_method_id();
		$order_id = $request[ 'order_id' ];

		// This is the earliest point in the printing label flow where we are sure that
		// the merchant wants to ship from this exact address (normalized or otherwise)
		$this->settings_store->update_origin_address( $payload[ 'origin' ] );
		$this->settings_store->update_destination_address( $order_id, $payload[ 'destination' ] );

		// Update the customs information on all this order's products
		$updated_product_ids = array();
		foreach ( $payload[ 'packages' ] as $package_id => $package ) {
			if ( ! $this->has_customs_data( $package ) ) {
				break;
			}
			foreach ( $package[ 'items' ] as $index => $item ) {
				if ( ! isset( $updated_product_ids[ $item[ 'product_id' ] ] ) ) {
					$updated_product_ids[ $item[ 'product_id' ] ] = true;
					update_post_meta( $item[ 'product_id' ], 'wc_connect_customs_info', array(
						'description' => $item[ 'description' ],
						'hs_tariff_number' => $item[ 'hs_tariff_number' ],
						'origin_country' => $item[ 'origin_country' ],
					) );
				}
			}
		}

		$response = $this->get_all_rates( $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'success' => true,
			'rates'   => $response,
		);
	}

	public function get_all_rates( $payload ) {
		$signature_packages = [];

		// Create duplicate packages with signature required enabled.
		foreach ( $payload['packages'] as $package_id => $package ) {
			$new_package = $package;
			$new_package['signature'] = 'yes';
			$signature_packages[] = $new_package;
		}
		$payload['packages'] = array_merge( $payload['packages'], $signature_packages );

		$response = $this->request_rates( $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( property_exists( $response, 'rates' ) ) {
			return $this->merge_all_rates( $response->rates );
		}
		return new stdClass();
	}

	// Merge signature rates with non-signature rates.
	public function merge_all_rates( $rates ) {

		// Group rates objects together under their service for with/without signature required.
		foreach ( $rates as $package_id => $package ) {
			$merged_rates = new stdClass();
			foreach( $package->rates as $rate_key => $rate ) {
				if ( ! property_exists( $merged_rates, $rate->service_id ) ) {
					$merged_rates->{ $rate->service_id } = new stdClass();
				}
				if ( 'yes' === $rate->signature ) {
					$merged_rates->{ $rate->service_id }->signature_required = $rate;
				} else {
					$merged_rates->{ $rate->service_id }->no_signature = $rate;
				}
			}
			$rates->$package_id->rates = $merged_rates;
		}

		/**
		 * Remove rate if cost is the same for with/without signature, that means that
		 * signature_required is not a valid service.
		 */
		foreach ( $rates as $package_id => $package ) {
			foreach( $package->rates as $service_id => $service_rates ) {
				if ( property_exists( $service_rates, 'signature_required') && property_exists( $service_rates, 'no_signature') ) {
					if ( $service_rates->signature_required->rate === $service_rates->no_signature->rate ) {
						unset( $rates->$package_id->rates->$service_id->signature_required );
					}
				}
			}
		}
		return $rates;
	}

	public function request_rates( $payload ) {
		$response = $this->api_client->get_label_rates( $payload );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}
		return $response;
	}
}
