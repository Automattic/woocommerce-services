<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Rates_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Rates_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)/rates';

	/**
	 * Prefix to add in package name for making requests with multiple rates.
	 */
	public $SPECIAL_RATE_PREFIX = '_wcs_rate_type_';

	/**
	 * Array of extra options to collect rates for.
	 */
	protected $extra_rates = array(
		'signature_required'       => array(
			'signature' => 'yes',
		),
		'adult_signature_required' => array(
			'signature' => 'adult',
		),
	);

	private function has_customs_data( $package ) {
		return isset( $package['contents_type'] );
	}

	/**
	 * Whether an ID is a product on one of the order's line items, as product or variation.
	 *
	 * @param string   $product_id Product ID from the request.
	 * @param WC_Order $order      Order the rates are for.
	 *
	 * @return bool
	 */
	private function is_product_on_order( $product_id, WC_Order $order ) {
		if ( ! ctype_digit( $product_id ) || 0 === (int) $product_id || ! wc_get_product( (int) $product_id ) ) {
			return false;
		}

		return false !== WC_Connect_Utils::get_line_item_from_order( (int) $product_id, $order );
	}

	/**
	 *
	 * @param WP_REST_Request $request - See WC_Connect_API_Client::get_label_rates()
	 * @return array|WP_Error
	 */
	public function post( $request ) {
		$order = $this->get_order_for_label_request( $request['order_id'] );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		$payload = $request->get_json_params();

		// Check the body before anything is saved, so a bad request changes nothing.
		if ( ! is_array( $payload )
			|| ! isset( $payload['origin'], $payload['destination'], $payload['packages'] )
			|| ! is_array( $payload['origin'] )
			|| ! is_array( $payload['destination'] )
			|| ! is_array( $payload['packages'] ) ) {
			return new WP_Error( 'bad_request', __( 'Bad request', 'woocommerce-services' ), array( 'status' => 400 ) );
		}

		$payload['payment_method_id'] = $this->settings_store->get_selected_payment_method_id();
		$order_id                     = $order->get_id();

		// This is the earliest point in the printing label flow where we are sure that
		// the merchant wants to ship from this exact address (normalized or otherwise)
		$this->settings_store->update_origin_address( $payload['origin'] );
		$this->settings_store->update_destination_address( $order_id, $payload['destination'] );

		// Update the customs information on all this order's products
		$updated_product_ids = array();
		foreach ( $payload['packages'] as $package_id => $package ) {
			if ( ! $this->has_customs_data( $package ) ) {
				break;
			}
			foreach ( $package['items'] as $index => $item ) {
				$product_id = isset( $item['product_id'] ) && is_scalar( $item['product_id'] ) ? (string) $item['product_id'] : '';

				if ( ! isset( $updated_product_ids[ $product_id ] ) ) {
					$updated_product_ids[ $product_id ] = true;

					// Only products on this order get customs info; skip any other ID.
					if ( ! $this->is_product_on_order( $product_id, $order ) ) {
						$this->logger->log(
							sprintf( 'Skipped customs info for ID %s: not a product on order %d.', wp_json_encode( $product_id ), $order_id ),
							__CLASS__
						);
						continue;
					}

					update_post_meta(
						(int) $product_id,
						'wc_connect_customs_info',
						array(
							'description'      => $item['description'],
							'hs_tariff_number' => $item['hs_tariff_number'],
							'origin_country'   => $item['origin_country'],
						)
					);
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

	/**
	 * Get standard rates along with rates for special options
	 * that are defined in $this->extra_rates
	 *
	 * @param stdClass $payload Request payload.
	 * @return WPError|stdClass
	 */
	public function get_all_rates( $payload ) {
		$signature_packages     = [];
		$original_package_names = [];

		// Add extra package requests with special options set.
		foreach ( $this->extra_rates as $rate_name => $rate_option ) {
			foreach ( $rate_option as $option_name => $option_value ) {
				foreach ( $payload['packages'] as $package_id => $package ) {
					$original_package_names[]    = $package['id'];
					$new_package                 = $package;
					$new_package[ $option_name ] = $option_value;

					$new_package['id']   .= $this->SPECIAL_RATE_PREFIX . $rate_name;
					$signature_packages[] = $new_package;
				}
			}
		}
		$payload['packages'] = array_merge( $payload['packages'], $signature_packages );

		$response = $this->request_rates( $payload );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		if ( property_exists( $response, 'rates' ) ) {
			return $this->merge_all_rates( $response->rates, $original_package_names );
		}
		return new stdClass();
	}

	/**
	 * Merge default rates together with extra rates.
	 *
	 * get_all_rates requests extra rate options as separate
	 * packages. This function groups these separate packages
	 * under the original the package name for easier parsing
	 * on the frontend.
	 *
	 * @param stdClass $rates                  Rate response for server.
	 * @param array    $original_package_names Package names.
	 *
	 * @return array Rates
	 */
	public function merge_all_rates( $rates, $original_package_names ) {
		$parsed_rates = [];

		foreach ( $original_package_names as $name ) {
			// Add a 'default' entry for the rate with no special options.
			$parsed_rates[ $name ] = array(
				'default' => $rates->{ $name },
			);

			// Get package for each extra rate to group them under the original package name.
			foreach ( $this->extra_rates as $extra_rate_name => $option ) {
				$extra_rate_package_name = $name . $this->SPECIAL_RATE_PREFIX . $extra_rate_name;
				if ( isset( $rates->{ $extra_rate_package_name } ) ) {
					$parsed_rates[ $name ][ $extra_rate_name ] = $rates->{ $extra_rate_package_name };
				}
			}
		}
		return $parsed_rates;
	}

	/**
	 * Make rate request.
	 *
	 * @param stdClass $payload Request payload.
	 * @return WPError|stdClass
	 */
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
