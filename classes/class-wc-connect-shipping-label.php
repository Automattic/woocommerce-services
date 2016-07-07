<?php

if ( ! class_exists( 'WC_Connect_Shipping_Label' ) ) {

	class WC_Connect_Shipping_Label {

		private $settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->settings_store = $settings_store;
		}

		protected function get_form_data( WC_Order $order ) {
			$form_data = array();
			$contents = array();

			foreach( $order->get_items() as $item ) {
				$product = $order->get_product_from_item( $item );
				$height = 0;
				$length = 0;
				$weight = $product->get_weight();
				$width = 0;

				if ( $product->has_dimensions() ) {
					$height = $product->get_height();
					$length = $product->get_length();
					$width  = $product->get_width();
				}

				$contents[] = array(
					'height' => $height,
					'product_id' => $item['product_id'],
					'length' => $length,
					'quantity' => $item['qty'],
					'weight' => $weight,
					'width' => $width
				);
			}
			$form_data[ 'cart' ] = $contents;

			$dest_address = $order->get_address( 'shipping' );
			$form_data[ 'dest_name' ] = trim( $dest_address[ 'first_name' ] . ' ' . $dest_address[ 'last_name' ] );
			$form_data[ 'dest_company' ] = $dest_address[ 'company' ];
			$form_data[ 'dest_address_1' ] = $dest_address[ 'address_1' ];
			$form_data[ 'dest_address_2' ] = $dest_address[ 'address_2' ];
			$form_data[ 'dest_city' ] = $dest_address[ 'city' ];
			$form_data[ 'dest_state' ] = $dest_address[ 'state' ];
			$form_data[ 'dest_postcode' ] = $dest_address[ 'postcode' ];
			$form_data[ 'dest_country' ] = $dest_address[ 'country' ];

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

		protected function get_form_layout() {
			$layout = array();

			$address_fields = array(
				array(
					'key' => 'name',
					'validation_hint' => __( 'Required.', 'woocommerce' ),
				),
				array(
					'key' => 'company',
				),
				array(
					'key' => 'address_1',
					'validation_hint' => __( 'Required.', 'woocommerce' ),
				),
				array(
					'key' => 'address_2',
				),
				array(
					'key' => 'city',
					'validation_hint' => __( 'Required.', 'woocommerce' ),
				),
				array(
					'key' => 'state',
					'type' => 'state',
					'validation_hint' => __( 'Required.', 'woocommerce' ),
				),
				array(
					'key' => 'postcode',
					'validation_hint' => __( 'Required.', 'woocommerce' ),
				),
				array(
					'key' => 'country',
					'validation_hint' => __( 'Required.', 'woocommerce' ),
					'type' => 'country',
				),
			);
			$address_summary = '{name}\\n{address_1} {address_2}\\n{city}, {postcode} {state}, {country}';

			$orig_address_fields = $address_fields;
			$dest_address_fields = $address_fields;
			foreach( $address_fields as $index => $_ ) {
				$orig_address_fields[ $index ][ 'key' ] = 'orig_' . $address_fields[ $index ][ 'key' ];
				$dest_address_fields[ $index ][ 'key' ] = 'dest_' . $address_fields[ $index ][ 'key' ];
				if ( 'state' === $address_fields[ $index ][ 'key' ] ) {
					$orig_address_fields[ $index ][ 'country_field' ] = 'orig_country';
					$dest_address_fields[ $index ][ 'country_field' ] = 'dest_country';
				}
			}

			$layout[] = array(
				'type' => 'step',
				'tab_title' => __( 'Origin', 'woocommerce' ),
				'title' => __( 'Generate shipping label: Origin address', 'woocommerce' ),
				'description' => __( 'Confirm the address of the recipient is correct to ensure the package will get to the correct destination', 'woocommerce' ),
				'items' => $orig_address_fields,
				'bypass_suggestion_flag' => 'orig_bypass_suggestion',
				'summary' => str_replace( '{', '{orig_', $address_summary ),
				'sugggestion_hint' => __( 'To ensure accurate delivery, we have slightly modified the address you entered. Select the address you wish to use before continuing to the next step.', 'woocommerce' ),
			);

			$layout[] = array(
				'type' => 'step',
				'tab_title' =>  __( 'Destination', 'woocommerce' ),
				'title' => __( 'Generate shipping label: Address verification', 'woocommerce' ),
				'description' => __( 'Confirm the address of the recipient is correct to ensure the package will get to the correct destination', 'woocommerce' ),
				'items' => $dest_address_fields,
				'bypass_suggestion_flag' => 'dest_bypass_suggestion',
				'summary' => str_replace( '{', '{dest_', $address_summary ),
				'sugggestion_hint' => __( 'To ensure accurate delivery, we have slightly modified the address you entered. Select the address you wish to use before continuing to the next step.', 'woocommerce' ),
			);

			$layout[] = array(
				'type' => 'step',
				'tab_title' => __( 'Packages', 'woocommerce' ),
				'title' => 'TODO: Not implemented yet',
				'items' => array(
					array(
						'key' => 'cart',
						'type' => 'shopping_cart',
					),
				),
				'summary' => '{cart}',
			);

			$layout[] = array(
				'type' => 'step',
				'tab_title' => __( 'Rates', 'woocommerce' ),
				'title' => 'TODO: Not implemented yet',
				'items' => array(
					array(
						'key' => 'rate',
						'type' => 'radios',
						'titleMap' => array(
							'media' => 'Media Mail ($1.00)',
							'pri_1' => 'Priority 1-day ($9.99)',
							'todo' => 'TODO: These are NOT real rates',
						),
					),
				),
				'summary' => '{rate}',
			);

			$layout[] = array(
				'type' => 'summary',
				'tab_title' => __( 'Buy & Print', 'woocommerce' ),
				'title' => 'TODO: Not implemented yet',
				'items' => array(),
				'action_label' => __( 'Print', 'woocommerce' ),
				'confirmation_flag' => 'confirm',
			);

			return $layout;
		}

		protected function get_form_schema() {
			$properties = array();

			$address_fields = array(
				'name' => array(
					'type' => 'string',
					'title' => __( 'Name', 'woocommerce' ),
					'minLength' => 1,
				),
				'company' => array(
					'type' => 'string',
					'title' => __( 'Company', 'woocommerce' ),
				),
				'address_1' => array(
					'type' => 'string',
					'title' => __( 'Street address', 'woocommerce' ),
					'minLength' => 1,
				),
				'address_2' => array(
					'type' => 'string'
				),
				'city' => array(
					'type' => 'string',
					'title' => __( 'City', 'woocommerce' ),
					'minLength' => 1,
				),
				'state' => array(
					'type' => 'string',
					'title' => __( 'State', 'woocommerce' ),
				),
				'postcode' => array(
					'type' => 'string',
					'title' => __( 'Postal code', 'woocommerce' ),
					'minLength' => 1,
				),
				'country' => array(
					'type' => 'string',
					'title' => __( 'Country', 'woocommerce' ),
					'enum' => array_keys( WC()->countries->get_countries() ),
					'default' => 'US',
				),
			);
			$required_address_fields = array( 'name', 'address_1', 'city', 'postcode', 'country' );

			foreach( $address_fields as $key => $value ) {
				$properties[ 'orig_' . $key ] = $value;
				$properties[ 'dest_' . $key ] = $value;
			}

			$required_fields = array();

			foreach( $required_address_fields as $field_name ) {
				$required_fields[] = 'orig_' . $field_name;
				$required_fields[] = 'dest_' . $field_name;
			}

			$itemDefinition = array(
				'type' => 'object',
				'required' => array(),
				'properties' => array(
					'height' => array(
						'type' => 'number',
						'title' => __( 'Height', 'woocommerce' ),
					),
					'product_id' => array(
						'type' => 'string',
						'title' => __( 'Product ID', 'woocommerce' ),
					),
					'length' => array(
						'type' => 'number',
						'title' => __( 'Length', 'woocommerce' ),
					),
					'quantity' => array(
						'type' => 'number',
						'title' => __( 'Quantity', 'woocommerce' ),
					),
					'weight' => array(
						'type' => 'number',
						'title' => __( 'Weight', 'woocommerce' ),
					),
					'width' => array(
						'type' => 'number',
						'title' => __( 'Width', 'woocommerce' ),
					),
				),
			);

			$properties[ 'cart' ] = array(
				'type' => 'array',
				'title' => __( 'Items to send', 'woocommerce' ),
				'items' => $itemDefinition,
			);
			$required_fields[] = 'cart';

			$properties[ 'rate' ] = array(
				'type' => 'string',
				'title' => __( 'Shipping rate', 'woocommerce' ),
				'enum' => array( 'pri_1', 'media', 'todo' ),
			);
			$required_fields[] = 'rate';

			return array(
				'required' => $required_fields,
				'properties' => $properties,
			);
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

			$admin_array = array(
				'storeOptions' => $this->settings_store->get_shared_settings(),
				'formSchema'   => $this->get_form_schema(),
				'formLayout'   => $this->get_form_layout(),
				'formData'     => $this->get_form_data( $theorder ),
				'callbackURL'  => get_rest_url( null, '/wc/v1/connect/shipping-label' ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'submitMethod' => 'POST',
				'rootView'     => 'shipping-label',
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );

			?>
			<div id="wc-connect-admin-container"><span class="form-troubles" style="opacity: 0"><?php printf( __( 'Settings not loading? Visit the WooCommerce Connect <a href="%s">debug page</a> to get some troubleshooting steps.', 'woocommerce' ), $debug_page_uri ); ?></span></div>
			<?php
		}

	}
}
