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

		public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store ) {
			$this->api_client = $api_client;
			$this->settings_store = $settings_store;
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
					$packages[] = array(
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

		protected function get_packages( WC_Order $order ) {
			$packages = $this->get_packaging_metadata( $order );
			if ( ! $packages ) {
				return $this->get_items_as_individual_packages( $order );
			}

			foreach( $packages as $package_index => $package ) {
				foreach( $package[ 'items' ] as $item_index => $item ) {
					$product = $order->get_product_from_item( $item );
					if ( ! $product ) {
						continue;
					}
					$product_data = $packages[ $package_index ][ 'items' ][ $item_index ];
					$product_data[ 'name' ] = $this->get_name( $product );
					$product_data[ 'url' ] = admin_url( 'post.php?post=' . $product->id . '&action=edit' );
					if ( isset( $product->variation_id ) ) {
						$product_data[ 'attributes' ] = $product->get_formatted_variation_attributes( true );
					}
					$packages[ $package_index ][ 'items' ][ $item_index ] = $product_data;
				}
			}

			return $packages;
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
				if ( ! $package[ 'service_id' ] ) {
					return array();
				}
				$id = isset( $package[ 'id' ] ) ? $package[ 'id' ] : "box_{$idx}";
				$rates[ $id ] = $package[ 'service_id' ];
			}

			return $rates;
		}

		protected function format_address_for_server( $address ) {
			// Combine first and last name
			if ( ! isset( $address[ 'name' ] ) ) {
				$first_name = isset( $address[ 'first_name' ] ) ? trim( $address[ 'first_name' ] ) : '';
				$last_name  = isset( $address[ 'last_name' ] ) ? trim( $address[ 'last_name' ] ) : '';

				$address[ 'name' ] = $first_name . ' ' . $last_name;
			}

			// Rename address_1 to address
			$address[ 'address' ] = $address[ 'address_1' ];

			// Remove now defunct keys
			unset( $address[ 'first_name' ], $address[ 'last_name' ], $address[ 'address_1' ] );

			return $address;
		}

		protected function get_origin_address() {
			$origin = $this->format_address_for_server( $this->settings_store->get_origin_address() );

			return $origin;
		}

		protected function get_destination_address( WC_Order $order ) {
			$order_address = $order->get_address( 'shipping' );
			$destination   = $this->format_address_for_server( $order_address );

			return $destination;
		}

		protected function get_form_data( WC_Order $order ) {
			$packages        = $this->get_packages( $order );
			$is_packed       = ( false !== $this->get_packaging_metadata( $order ) );
			$origin          = $this->get_origin_address();
			$selected_rates  = $this->get_selected_rates( $order );
			$destination     = $this->get_destination_address( $order );

			$form_data = compact( 'is_packed', 'packages', 'origin', 'destination' );

			$form_data[ 'rates' ] = array(
				'selected'  => $selected_rates,
			);

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

		public function meta_box( $post ) {
			$order = wc_get_order( $post );

			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab' => 'connect'
				),
				admin_url( 'admin.php' )
			) );

			$store_options = $this->settings_store->get_store_options();
			$store_options[ 'countriesData' ] = $this->get_states_map();
			$root_view = 'wc-connect-create-shipping-label';
			$admin_array = array(
				'storeOptions'            => $store_options,
				'formData'                => $this->get_form_data( $order ),
				'purchaseURL'             => get_rest_url( null, '/wc/v1/connect/shipping-label' ),
				'addressNormalizationURL' => get_rest_url( null, '/wc/v1/connect/normalize-address' ),
				'getRatesURL'             => get_rest_url( null, '/wc/v1/connect/shipping-rates' ),
				'nonce'                   => wp_create_nonce( 'wp_rest' ),
				'rootView'                => $root_view,
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			?>
			<div class="wc-connect-admin-container" id="<?php echo esc_attr( $root_view ) ?>"><span class="form-troubles" style="opacity: 0"><?php printf( __( 'Shipping labels not loading? Visit the <a href="%s">status page</a> for troubleshooting steps.', 'woocommerce' ), $debug_page_uri ); ?></span></div>
			<?php
		}

	}
}
