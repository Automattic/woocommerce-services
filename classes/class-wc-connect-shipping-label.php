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
		 * @var WC_Connect_Payment_Methods_Store
		 */
		protected $payment_methods_store;

		public function __construct(
			WC_Connect_API_Client $api_client,
			WC_Connect_Service_Settings_Store $settings_store,
			WC_Connect_Service_Schemas_Store $service_schemas_store,
			WC_Connect_Payment_Methods_Store $payment_methods_store
		) {
			$this->api_client = $api_client;
			$this->settings_store = $settings_store;
			$this->service_schemas_store = $service_schemas_store;
			$this->payment_methods_store = $payment_methods_store;
		}

		protected function get_items_as_individual_packages( $order ) {
			$packages = array();
			foreach( $order->get_items() as $item ) {
				$product = $order->get_product_from_item( $item );
				if ( ! $product || ! $product->needs_shipping() ) {
					continue;
				}
				$height = 0;
				$length = 0;
				$weight = $product->get_weight();
				$width = 0;

				if ( $product->has_dimensions() ) {
					$height = $product->get_height();
					$length = $product->get_length();
					$width  = $product->get_width();
				}
				$name = html_entity_decode( $product->get_formatted_name() );

				for ( $i = 0; $i < $item[ 'qty' ]; $i++ ) {
					$id = "weight_{$i}_individual";
					$packages[ $id ] = array(
						'id' => $id,
						'box_id' => 'individual',
						'height' => ( float ) $height,
						'length' => ( float ) $length,
						'weight' => ( float ) $weight,
						'width' => ( float ) $width,
						'items' => array(
							array(
								'height' => ( float ) $height,
								'product_id' => $item[ 'product_id' ],
								'length' => ( float ) $length,
								'quantity' => 1,
								'weight' => ( float ) $weight,
								'width' => ( float ) $width,
								'name' => $name,
							),
						),
					);
				}
			}
			return $packages;
		}

		protected function get_packaging_metadata( WC_Order $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method = reset( $shipping_methods );
			if ( ! $shipping_method || ! isset( $shipping_method[ 'wc_connect_packages' ] ) ) {
				return false;
			}

			return json_decode( $shipping_method[ 'wc_connect_packages' ], true );
		}

		protected function get_name( WC_Product $product ) {
			if ( $product->get_sku() ) {
				$identifier = $product->get_sku();
			} else {
				$identifier = '#' . ( isset( $product->variation_id ) ? $product->variation_id : $product->id );
			}
			return sprintf( '%s - %s', $identifier, $product->get_title() );
		}

		protected function get_selected_packages(WC_Order $order ) {
			$packages = $this->get_packaging_metadata( $order );
			if ( ! $packages ) {
				return $this->get_items_as_individual_packages( $order );
			}

			$formatted_packages = array();

			foreach( $packages as $package ) {
				$package_id = $package[ 'id' ];
				$formatted_packages[ $package_id ] = $package;

				foreach( $package[ 'items' ] as $item_index => $item ) {
					$product = $order->get_product_from_item( $item );
					if ( ! $product ) {
						continue;
					}
					$product_data = $package[ 'items' ][ $item_index ];
					$product_data[ 'name' ] = $this->get_name( $product );
					$product_data[ 'url' ] = admin_url( 'post.php?post=' . $product->id . '&action=edit' );
					if ( isset( $product->variation_id ) ) {
						$product_data[ 'attributes' ] = $product->get_formatted_variation_attributes( true );
					}
					$formatted_packages[ $package_id ][ 'items' ][ $item_index ] = $product_data;
				}
			}

			return $formatted_packages;
		}

		protected function get_all_packages() {
			$custom_packages = $this->settings_store->get_packages();

			$formatted_packages = array();

			foreach( $custom_packages as $package ) {
				$package_id = $package[ 'name' ];
				$formatted_packages[ $package_id ] = $package;
			}

			$service_id = 'usps'; //TODO: remove hardcoding
			$predefined_packages_schema = $this->service_schemas_store->get_predefined_packages_schema_for_service( $service_id );
			$enabled_predefined_packages = $this->settings_store->get_predefined_packages_for_service( $service_id );

			foreach ( $predefined_packages_schema as $group ) {
				foreach ( $group->definitions as $package ) {
					if ( ! $package->is_flat_rate && ! in_array( $package->id, $enabled_predefined_packages ) ) {
						continue;
					}

					$formatted_packages[ $package->id ] = $package;
				}
			}

			return ( object ) $formatted_packages;
		}

		protected function get_flat_rate_packages_groups() {
			$service_id = 'usps'; //TODO: remove hardcoding
			$predefined_packages_schema = $this->service_schemas_store->get_predefined_packages_schema_for_service( $service_id );
			$groups = array();

			foreach ( $predefined_packages_schema as $group_id => $group ) {
				$groups[ $group_id ] = $group->title;
			}

			return $groups;
		}

		protected function get_selected_rates( WC_Order $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method = reset( $shipping_methods );
			if ( ! $shipping_method || ! isset( $shipping_method[ 'wc_connect_packages' ] ) ) {
				return array();
			}

			$packages = json_decode( $shipping_method[ 'wc_connect_packages' ], true );
			$rates = array();

			foreach( $packages as $idx => $package ) {
				// Abort if the package data is malformed
				if ( ! isset( $package[ 'id' ] ) || ! isset( $package[ 'service_id' ] ) ) {
					return array();
				}

				$rates[ $package[ 'id' ] ] = $package[ 'service_id' ];
			}

			return $rates;
		}

		protected function format_address_for_api( $address ) {
			// Combine first and last name
			if ( ! isset( $address[ 'name' ] ) ) {
				$first_name = isset( $address[ 'first_name' ] ) ? trim( $address[ 'first_name' ] ) : '';
				$last_name  = isset( $address[ 'last_name' ] ) ? trim( $address[ 'last_name' ] ) : '';

				$address[ 'name' ] = $first_name . ' ' . $last_name;
			}

			// Rename address_1 to address
			if ( ! isset( $address[ 'address' ] ) && isset( $address[ 'address_1' ] ) ) {
				$address[ 'address' ] = $address[ 'address_1' ];
			}

			// Remove now defunct keys
			unset( $address[ 'first_name' ], $address[ 'last_name' ], $address[ 'address_1' ] );

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
			$selected_packages = $this->get_selected_packages( $order );
			$all_packages      = $this->get_all_packages();
			$flat_rate_groups  = $this->get_flat_rate_packages_groups();
			$is_packed         = ( false !== $this->get_packaging_metadata( $order ) );
			$origin            = $this->get_origin_address();
			$selected_rates    = $this->get_selected_rates( $order );
			$destination       = $this->get_destination_address( $order );

			if ( ! $destination[ 'country' ] ) {
				$destination[ 'country' ] = $origin[ 'country' ];
			}

			$form_data = compact( 'is_packed', 'selected_packages', 'all_packages', 'flat_rate_groups', 'origin', 'destination' );

			$form_data[ 'rates' ] = array(
				'selected'  => (object) $selected_rates,
			);

			$form_data[ 'order_id' ] = $order->id;

			return $form_data;
		}

		protected function get_states_map() {
			$result = array();
			foreach( WC()->countries->get_countries() as $code => $name ) {
				$result[ $code ] = array( 'name' => html_entity_decode( $name ) );
			}
			foreach( WC()->countries->get_states() as $country => $states ) {
				$result[ $country ][ 'states' ] = array();
				foreach ( $states as $code => $name ) {
					$result[ $country ][ 'states' ][ $code ] = html_entity_decode( $name );
				}
			}
			return $result;
		}

		public function should_show_meta_box() {
			$order = wc_get_order();

			if ( ! $order ) {
				return false;
			}

			// TODO: return true if the order has already label meta-data

			$base_location = wc_get_base_location();
			if ( 'US' !== $base_location[ 'country' ] ) {
				return false;
			}

			$dest_address = $order->get_address( 'shipping' );
			if ( $dest_address[ 'country' ] && 'US' !== $dest_address[ 'country' ] ) {
				return false;
			}

			foreach( $order->get_items() as $item ) {
				$product = $order->get_product_from_item( $item );
				if ( $product && $product->needs_shipping() ) {
					return true;
				}
			}
			return false;
		}

		public function get_paper_size() {
			$paper_size = NULL; //$this->settings_store->get_preferred_paper_size();
			if ( $paper_size ) {
				return $paper_size;
			}
			// According to https://en.wikipedia.org/wiki/Letter_(paper_size) US, Mexico, Canada and Dominican Republic
			// use "Letter" size, and pretty much all the rest of the world use A4, so those are sensible defaults
			$base_location = wc_get_base_location();
			if ( in_array( $base_location[ 'country' ], array( 'US', 'CA', 'MX', 'DO' ) ) ) {
				return 'letter';
			}
			return 'a4';
		}

		public function get_selected_payment_method() {
			// Account settings contains the payment method id
			$account_settings = $this->settings_store->get_account_settings();

			// No selected payment method case
			if ( ! isset( $account_settings[ 'selected_payment_method_id' ] ) ) {
				return null;
			}

			$selected_payment_method_id = $account_settings[ 'selected_payment_method_id' ];

			// Get all known payment methods
			$payment_methods = $this->payment_methods_store->get_payment_methods();

			// Find the selected payment method and return the card digits (e.g. "4242")
			foreach ( (array) $payment_methods as $payment_method ) {
				if ( ! property_exists( $payment_method, 'payment_method_id' ) ) {
					continue;
				}

				if ( $selected_payment_method_id != $payment_method->payment_method_id ) {
					continue;
				}

				return property_exists( $payment_method, 'card_digits' ) ? $payment_method->card_digits : null;
			}

			return null;
		}

		public function meta_box( $post ) {
			$order = wc_get_order( $post );

			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab' => 'connect'
				),
				admin_url( 'admin.php' )
			) );

			$root_view = 'wc-connect-create-shipping-label';
			$admin_array = array(
				'purchaseURL'             => get_rest_url( null, '/wc/v1/connect/label/purchase' ),
				'addressNormalizationURL' => get_rest_url( null, '/wc/v1/connect/normalize-address' ),
				'getRatesURL'             => get_rest_url( null, '/wc/v1/connect/shipping-rates' ),
				'labelStatusURL'          => get_rest_url( null, '/wc/v1/connect/label/' . $order->id . '-%d' ),
				'labelRefundURL'          => get_rest_url( null, '/wc/v1/connect/label/' . $order->id . '-%d/refund' ),
				'labelsPreviewURL'        => get_rest_url( null, '/wc/v1/connect/labels/preview' ),
				'labelsPrintURL'          => get_rest_url( null, '/wc/v1/connect/labels/print' ),
				'paperSize'               => $this->get_paper_size(),
				'nonce'                   => wp_create_nonce( 'wp_rest' ),
				'rootView'                => $root_view,
				'formData'                => $this->get_form_data( $order ),
				'paymentMethod'           => $this->get_selected_payment_method(),
			);

			$labels_data = get_post_meta( $order->id, 'wc_connect_labels', true );
			if ( $labels_data ) {
				$admin_array[ 'labelsData' ] = json_decode( $labels_data, true, WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH );
			}

			$store_options = $this->settings_store->get_store_options();
			$store_options[ 'countriesData' ] = $this->get_states_map();
			$admin_array[ 'storeOptions' ] = $store_options;

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			?>
			<div class="wcc-root" id="<?php echo esc_attr( $root_view ) ?>">
				<span class="form-troubles" style="opacity: 0">
					<?php printf( __(
						'Shipping labels not loading? Visit the <a href="%s">status page</a> for troubleshooting steps.',
						'woocommerce-services' ),
						$debug_page_uri
					); ?>
				</span>
			</div>
			<?php
		}

	}
}
