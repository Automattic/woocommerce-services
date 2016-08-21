<?php

if ( ! class_exists( 'WC_Connect_Shipping_Label' ) ) {

	class WC_Connect_Shipping_Label {

		private $settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
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
						'height' => $height,
						'length' => $length,
						'weight' => $weight,
						'width' => $width,
						'items' => array(
							array(
								'height' => $height,
								'product_id' => $item[ 'product_id' ],
								'length' => $length,
								'quantity' => 1,
								'weight' => $weight,
								'width' => $width,
								'name' => $name,
							),
						),
					);
				}
			}
			return $packages;
		}

		protected function get_packaging_data( WC_Order $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method = reset( $shipping_methods );
			if ( ! $shipping_method || ! isset( $shipping_method[ 'wc_connect_packages' ] ) ) {
				return array( FALSE, $this->get_items_as_individual_packages( $order ) );
			}

			$packages = json_decode( $shipping_method[ 'wc_connect_packages' ], true );

			foreach( $packages as $package_index => $package ) {
				foreach( $package[ 'items' ] as $item_index => $item ) {
					$product = $order->get_product_from_item( $item );
					if ( ! $product ) {
						continue;
					}
					$packages[ $package_index ][ 'items' ][ $item_index ][ 'name' ] = html_entity_decode( $product->get_formatted_name() );
				}
			}

			return array( TRUE, $packages );
		}

		protected function get_selected_rates( WC_Order $order ) {
			$shipping_methods = $order->get_shipping_methods();
			$shipping_method = reset( $shipping_methods );
			if ( ! $shipping_method || ! isset( $shipping_method[ 'wc_connect_packages' ] ) ) {
				return array();
			}

			$packages = json_decode( $shipping_method[ 'wc_connect_packages' ], true );
			$rates = array();

			foreach( $packages as $package ) {
				if ( ! $package[ 'service_id' ] ) {
					return array();
				}
				$rates[] = $package[ 'service_id' ];
			}

			return $rates;
		}

		protected function get_form_data( WC_Order $order ) {
			$form_data = array();

			list( $form_data[ 'is_packed' ], $form_data[ 'packages' ] ) = $this->get_packaging_data( $order );

			$form_data[ 'rates' ] = $this->get_selected_rates( $order );

			$form_data[ 'origin' ] = $this->settings_store->get_origin_address();

			$dest_address = $order->get_address( 'shipping' );
			$form_data[ 'destination' ] = array(
				'name' => trim( $dest_address[ 'first_name' ] . ' ' . $dest_address[ 'last_name' ] ),
				'company' => $dest_address[ 'company' ],
				'address' => $dest_address[ 'address_1' ],
				'address_2' => $dest_address[ 'address_2' ],
				'city' => $dest_address[ 'city' ],
				'state' => $dest_address[ 'state' ],
				'postcode' => $dest_address[ 'postcode' ],
				'country' => $dest_address[ 'country' ] ? $dest_address[ 'country' ] : 'US',
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

		public function meta_box( $post ) {
			global $theorder;

			if ( ! is_object( $theorder ) ) {
				$theorder = wc_get_order( $post->ID );
			}

			$debug_page_uri = esc_url( add_query_arg(
				array(
					'page' => 'wc-status',
					'tab' => 'connect'
				),
				admin_url( 'admin.php' )
			) );

			$store_options = $this->settings_store->get_shared_settings();
			$store_options[ 'countriesData' ] = $this->get_states_map();
			$root_view = 'wc-connect-create-shipping-label';
			$admin_array = array(
				'storeOptions' => $store_options,
				'formData'     => $this->get_form_data( $theorder ),
				'callbackURL'  => get_rest_url( null, '/wc/v1/connect/shipping-label' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'submitMethod' => 'POST',
				'rootView'     => $root_view,
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
