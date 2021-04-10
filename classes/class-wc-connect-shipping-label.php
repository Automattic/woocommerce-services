<?php

if ( ! class_exists( 'WC_Connect_Shipping_Label' ) ) {

	class WC_Connect_Shipping_Label {

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $settings_store;

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;

		/**
		 * @var WC_Connect_Account_Settings
		 */
		protected $account_settings;

		/**
		 * @var WC_Connect_Package_Settings
		 */
		protected $package_settings;

		/**
		 * @var WC_Connect_Continents
		 */
		protected $continents;

		/**
		 * @var array Supported countries by USPS, see: https://webpmt.usps.gov/pmt010.cfm
		 */
		private $supported_countries = array( 'US', 'AS', 'PR', 'VI', 'GU', 'MP', 'UM', 'FM', 'MH' );

		/**
		 * @var array Supported currencies
		 */
		private $supported_currencies = array( 'USD' );

		private $show_metabox = null;

		public function __construct(
			WC_Connect_API_Client $api_client,
			WC_Connect_Service_Settings_Store $settings_store,
			WC_Connect_Service_Schemas_Store $service_schemas_store,
			WC_Connect_Payment_Methods_Store $payment_methods_store
		) {
			$this->api_client            = $api_client;
			$this->settings_store        = $settings_store;
			$this->service_schemas_store = $service_schemas_store;
			$this->account_settings      = new WC_Connect_Account_Settings(
				$settings_store,
				$payment_methods_store
			);
			$this->package_settings      = new WC_Connect_Package_Settings(
				$settings_store,
				$service_schemas_store
			);
			$this->continents            = new WC_Connect_Continents();
		}

		public function get_item_data( WC_Order $order, $item ) {
			$product = WC_Connect_Compatibility::instance()->get_item_product( $order, $item );
			if ( ! $product || ! $product->needs_shipping() ) {
				return null;
			}
			$height = 0;
			$length = 0;
			$weight = $product->get_weight();
			$width  = 0;

			if ( $product->has_dimensions() ) {
				$height = $product->get_height();
				$length = $product->get_length();
				$width  = $product->get_width();
			}

			$product_data = array(
				'height'     => (float) $height,
				'product_id' => $product->get_id(),
				'length'     => (float) $length,
				'quantity'   => 1,
				'weight'     => (float) $weight,
				'width'      => (float) $width,
				'name'       => $this->get_name( $product ),
				'url'        => get_edit_post_link( WC_Connect_Compatibility::instance()->get_parent_product_id( $product ), null ),
			);

			if ( $product->is_type( 'variation' ) ) {
				$product_data['attributes'] = WC_Connect_Compatibility::instance()->get_formatted_variation( $product, true );
			}

			return $product_data;
		}

		protected function get_packaging_from_shipping_method( $shipping_method ) {
			if ( ! $shipping_method || ! isset( $shipping_method['wc_connect_packages'] ) ) {
				return array();
			}

			$packages_data = $shipping_method['wc_connect_packages'];
			if ( ! $packages_data ) {
				return array();
			}

			// WC3 retrieves metadata as non-scalar values.
			if ( is_array( $packages_data ) ) {
				return $packages_data;
			}

			// WC2.6 stores non-scalar values as string, but doesn't deserialize it on retrieval.
			$packages = maybe_unserialize( $packages_data );
			if ( is_array( $packages ) ) {
				return $packages;
			}

			// legacy WCS stored the labels as JSON.
			$packages = json_decode( $packages_data, true );
			if ( $packages ) {
				return $packages;
			}

			$packages_data = $this->settings_store->try_recover_invalid_json_string( 'box_id', $packages_data );
			$packages      = json_decode( $packages_data, true );
			if ( $packages ) {
				return $packages;
			}

			return array();
		}

		protected function get_packaging_metadata( WC_Order $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method  = reset( $shipping_methods );
			$packaging        = $this->get_packaging_from_shipping_method( $shipping_method );

			if ( is_array( $packaging ) ) {
				return array_filter( $packaging );
			}

			return array();
		}

		protected function get_name( WC_Product $product ) {
			if ( $product->get_sku() ) {
				$identifier = $product->get_sku();
			} else {
				$identifier = '#' . WC_Connect_Compatibility::instance()->get_product_id( $product );

			}
			return sprintf( '%s - %s', $identifier, $product->get_title() );
		}

		public function get_selected_packages( WC_Order $order ) {
			$packages = $this->get_packaging_metadata( $order );
			if ( ! $packages ) {
				$items  = $this->get_all_items( $order );
				$weight = array_sum( wp_list_pluck( $items, 'weight' ) );

				$packages = array(
					'default_box' => array(
						'id'     => 'default_box',
						'box_id' => 'not_selected',
						'height' => 0,
						'length' => 0,
						'weight' => $weight,
						'width'  => 0,
						'items'  => $items,
					),
				);
			}

			$formatted_packages = array();

			foreach ( $packages as $package_obj ) {
				$package                           = (array) $package_obj;
				$package_id                        = $package['id'];
				$formatted_packages[ $package_id ] = $package;

				foreach ( $package['items'] as $item_index => $item ) {
					$product_data = (array) $item;
					$product      = WC_Connect_Compatibility::instance()->get_item_product( $order, $product_data );

					if ( $product ) {
						$product_data['name'] = $this->get_name( $product );
						$product_data['url']  = get_edit_post_link( WC_Connect_Compatibility::instance()->get_parent_product_id( $product ), null );
						if ( $product->is_type( 'variation' ) ) {
							$formatted                  = WC_Connect_Compatibility::instance()->get_formatted_variation( $product, true );
							$product_data['attributes'] = $formatted;
						}
						$customs_info = get_post_meta( $product_data['product_id'], 'wc_connect_customs_info', true );
						if ( $customs_info ) {
							$product_data = array_merge( $product_data, $customs_info );
						}
					} else {
						$product_data['name'] = WC_Connect_Compatibility::instance()->get_product_name_from_order( $product_data['product_id'], $order );
					}
					$product_data['value'] = WC_Connect_Compatibility::instance()->get_product_price_from_order( $product_data['product_id'], $order );
					if ( ! isset( $product_data['value'] ) ) {
						$product_data['value'] = 0;
					}

					$formatted_packages[ $package_id ]['items'][ $item_index ] = $product_data;
				}
			}

			return $formatted_packages;
		}

		public function get_all_items( WC_Order $order ) {
			if ( $this->get_packaging_metadata( $order ) ) {
				return array();
			}

			$items = array();
			foreach ( $order->get_items() as $item ) {
				$item_data = $this->get_item_data( $order, $item );
				if ( null === $item_data ) {
					continue;
				}

				for ( $i = 0; $i < $item['qty']; $i++ ) {
					$items[] = $item_data;
				}
			}

			return $items;
		}

		public function get_selected_rates( WC_Order $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method  = reset( $shipping_methods );
			$packages         = $this->get_packaging_from_shipping_method( $shipping_method );
			$rates            = array();

			foreach ( $packages as $idx => $package_obj ) {
				$package = (array) $package_obj;
				// Abort if the package data is malformed
				if ( ! isset( $package['id'] ) || ! isset( $package['service_id'] ) ) {
					return array();
				}

				$rates[ $package['id'] ] = $package['service_id'];
			}

			return $rates;
		}

		protected function format_address_for_api( $address ) {
			// Combine first and last name.
			if ( ! isset( $address['name'] ) ) {
				$first_name = isset( $address['first_name'] ) ? trim( $address['first_name'] ) : '';
				$last_name  = isset( $address['last_name'] ) ? trim( $address['last_name'] ) : '';

				$address['name'] = $first_name . ' ' . $last_name;
			}

			// Rename address_1 to address.
			if ( ! isset( $address['address'] ) && isset( $address['address_1'] ) ) {
				$address['address'] = $address['address_1'];
			}

			// Remove now defunct keys.
			unset( $address['first_name'], $address['last_name'], $address['address_1'] );

			return $address;
		}

		protected function get_origin_address() {
			$origin = $this->format_address_for_api( $this->settings_store->get_origin_address() );

			return $origin;
		}

		protected function get_destination_address( WC_Order $order ) {
			$order_address = $order->get_address( 'shipping' );
			$destination   = $this->format_address_for_api( $order_address );

			return $destination;
		}

		protected function get_form_data( WC_Order $order ) {
			$order_id          = WC_Connect_Compatibility::instance()->get_order_id( $order );
			$selected_packages = $this->get_selected_packages( $order );
			$is_packed         = ( false !== $this->get_packaging_metadata( $order ) );
			$origin            = $this->get_origin_address();
			$selected_rates    = $this->get_selected_rates( $order );
			$destination       = $this->get_destination_address( $order );

			if ( ! $destination['country'] ) {
				$destination['country'] = $origin['country'];
			}

			$origin_normalized      = (bool) WC_Connect_Options::get_option( 'origin_address', false );
			$destination_normalized = (bool) get_post_meta( $order_id, '_wc_connect_destination_normalized', true );

			$form_data = compact( 'is_packed', 'selected_packages', 'origin', 'destination', 'origin_normalized', 'destination_normalized' );

			$form_data['rates'] = array(
				'selected' => (object) $selected_rates,
			);

			$form_data['order_id'] = $order_id;

			return $form_data;
		}

		/**
		 * Check whether the given order is eligible for shipping label creation - the order has at least one product that is:
		 * - Shippable.
		 * - Non-refunded.
		 *
		 * @param WC_Order $order The order to check for shipping label creation eligibility.
		 * @return bool Whether the given order is eligible for shipping label creation.
		 */
		public function is_order_eligible_for_shipping_label_creation( WC_Order $order ) {
			// Set up a dictionary from product ID to quantity in the order, which will be updated by refunds and existing labels later.
			$quantities_by_product_id = array();
			foreach ( $order->get_items() as $item ) {
				$product = WC_Connect_Compatibility::instance()->get_item_product( $order, $item );
				if ( $product && $product->needs_shipping() ) {
					$product_id                              = WC_Connect_Compatibility::instance()->get_product_id( $product );
					$current_quantity                        = array_key_exists( $product_id, $quantities_by_product_id ) ? $quantities_by_product_id[ $product_id ] : 0;
					$quantities_by_product_id[ $product_id ] = $current_quantity + $item->get_quantity();
				}
			}

			// A shipping label cannot be created without a shippable product.
			if ( empty( $quantities_by_product_id ) ) {
				return false;
			}

			// Update the quantity for each refunded product ID in the order.
			foreach ( $order->get_refunds() as $refund ) {
				foreach ( $refund->get_items() as $refunded_item ) {
					$product    = WC_Connect_Compatibility::instance()->get_item_product( $order, $refunded_item );
					$product_id = WC_Connect_Compatibility::instance()->get_product_id( $product );
					if ( array_key_exists( $product_id, $quantities_by_product_id ) ) {
						$current_count                           = $quantities_by_product_id[ $product_id ];
						$quantities_by_product_id[ $product_id ] = $current_count - abs( $refunded_item->get_quantity() );
					}
				}
			}

			// The order is eligible for shipping label creation when there is at least one product with positive quantity.
			foreach ( $quantities_by_product_id as $product_id => $quantity ) {
				if ( $quantity > 0 ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check whether the store is eligible for shipping label creation:
		 * - Store currency is supported.
		 * - Store country is supported.
		 *
		 * @return bool Whether the WC store is eligible for shipping label creation.
		 */
		public function is_store_eligible_for_shipping_label_creation() {
			$base_currency = get_woocommerce_currency();
			if ( ! $this->is_supported_currency( $base_currency ) ) {
				return false;
			}

			$base_location = wc_get_base_location();
			if ( ! $this->is_supported_country( $base_location['country'] ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check whether the given country code is supported for shipping labels.
		 *
		 * @param string $country_code Country code of the WC store.
		 * @return bool Whether the given country code is supported for shipping labels.
		 */
		private function is_supported_country( $country_code ) {
			return in_array( $country_code, $this->supported_countries, true );
		}

		/**
		 * Check whether the given currency code is supported for shipping labels.
		 *
		 * @param string $currency_code Currency code of the WC store.
		 * @return bool Whether the given country code is supported for shipping labels.
		 */
		private function is_supported_currency( $currency_code ) {
			return in_array( $currency_code, $this->supported_currencies, true );
		}

		public function is_dhl_express_available() {
			$dhl_express = $this->service_schemas_store->get_service_schema_by_id( 'dhlexpress' );

			return ! ! $dhl_express;
		}

		public function is_order_dhl_express_eligible() {
			if ( ! $this->is_dhl_express_available() ) {
				return false;
			}

			$order = wc_get_order();
			if ( ! $order ) {
				return false;
			}

			$origin      = $this->get_origin_address();
			$destination = $this->get_destination_address( $order );

			return $origin['country'] !== $destination['country'];
		}

		public function should_show_meta_box() {
			if ( null === $this->show_metabox ) {
				$this->show_metabox = $this->calculate_should_show_meta_box();
			}

			return $this->show_metabox;
		}

		private function calculate_should_show_meta_box() {
			$order = wc_get_order();

			if ( ! $order ) {
				return false;
			}

			// If the order already has purchased labels, show the meta-box no matter what
			if ( get_post_meta( WC_Connect_Compatibility::instance()->get_order_id( $order ), 'wc_connect_labels', true ) ) {
				return true;
			}

			// Restrict showing the metabox to supported store countries and currencies.
			if ( ! $this->is_store_eligible_for_shipping_label_creation() ) {
				return false;
			}

			// If the order was created using WCS checkout rates, show the meta-box regardless of the products' state.
			if ( $this->get_packaging_metadata( $order ) ) {
				return true;
			}

			// At this point (no packaging data), only show if there's at least one existing and shippable product.
			foreach ( $order->get_items() as $item ) {
				$product = WC_Connect_Compatibility::instance()->get_item_product( $order, $item );
				if ( $product && $product->needs_shipping() ) {
					return true;
				}
			}

			return false;
		}

		public function get_label_payload( $post_order_or_id ) {
			$order = wc_get_order( $post_order_or_id );
			if ( ! $order ) {
				return false;
			}

			$order_id = WC_Connect_Compatibility::instance()->get_order_id( $order );
			$payload  = array(
				'orderId'            => $order_id,
				'paperSize'          => $this->settings_store->get_preferred_paper_size(),
				'formData'           => $this->get_form_data( $order ),
				'labelsData'         => $this->settings_store->get_label_order_meta_data( $order_id ),
				'storeOptions'       => $this->settings_store->get_store_options(),
				// for backwards compatibility, still disable the country dropdown for calypso users with older plugin versions.
				'canChangeCountries' => true,
			);

			return $payload;
		}

		/**
		 * Filter items needing shipping callback.
		 *
		 * @since  3.0.0
		 * @param  array $item Item to check for shipping.
		 * @return bool
		 */
		public function filter_items_needing_shipping( $item ) {
			$product = $item->get_product();
			return $product && $product->needs_shipping();
		}

		/**
		 * Reduce items to sum their quantities.
		 *
		 * @param  int   $sum  Current sum.
		 * @param  array $item Item to add to sum.
		 * @return int
		 */
		protected function reducer_items_quantity( $sum, $item ) {
			return $sum + $item->get_quantity();
		}

		public function meta_box( $post, $args ) {

			$connect_order_presenter = new WC_Connect_Order_Presenter();
			$order                   = wc_get_order( $post );
			$order_id                = WC_Connect_Compatibility::instance()->get_order_id( $order );
			$items                   = array_filter( $order->get_items(), array( $this, 'filter_items_needing_shipping' ) );
			$items_count             = array_reduce( $items, array( $this, 'reducer_items_quantity' ), 0 );
			$payload                 = apply_filters(
				'wc_connect_meta_box_payload',
				array(
					'order'             => $connect_order_presenter->get_order_for_api( $order ),
					'accountSettings'   => $this->account_settings->get(),
					'packagesSettings'  => $this->package_settings->get(),
					'shippingLabelData' => $this->get_label_payload( $order_id ),
					'continents'        => $this->continents->get(),
					'context'           => $args['args']['context'],
					'items'             => $items_count,
				),
				$args,
				$order,
				$this
			);

			do_action( 'enqueue_wc_connect_script', 'wc-connect-create-shipping-label', $payload );
		}
	}
}
