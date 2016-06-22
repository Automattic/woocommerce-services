<?php

if ( ! class_exists( 'WC_Connect_Shipping_Label' ) ) {

	class WC_Connect_Shipping_Label {

		private $settings_store;

		public function __construct( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->settings_store = $settings_store;
		}

		protected function get_form_data( WC_Order $order ) {
			$formData = [];
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
			$formData[ 'cart' ] = $contents;

			foreach( $order->get_address( 'shipping' ) as $key => $value ) {
				$formData[ 'orig_' . $key ] = '';
				$formData[ 'dest_' . $key ] = $value;
			}

			return $formData;
		}

		protected function get_form_layout() {
			$layout = [];

			$address_fields = array(
				array(
					'key' => 'first_name',
				),
				array(
					'key' => 'last_name',
				),
				array(
					'key' => 'company',
				),
				array(
					'key' => 'address_1',
				),
				array(
					'key' => 'address_2',
				),
				array(
					'key' => 'city',
				),
				array(
					'key' => 'state',
				),
				array(
					'key' => 'postcode',
				),
				array(
					'key' => 'country',
				),
			);
			$address_summary = '{first_name} {last_name}\\n{address_1} {address_2}\\n{city}, {postcode} {state}, {country}';

			$orig_address_fields = $address_fields;
			$dest_address_fields = $address_fields;
			foreach( $address_fields as $index => $_ ) {
				$orig_address_fields[ $index ][ 'key' ] = 'orig_' . $address_fields[ $index ][ 'key' ];
				$dest_address_fields[ $index ][ 'key' ] = 'dest_' . $address_fields[ $index ][ 'key' ];
			}

			$layout[] = array(
				'type' => 'step',
				'tab_title' => 'Origin',
				'title' => 'Generate shipping label: Address verification (origin)',
				'description' => 'asdfghjkjhgfdsaASDFGHJJHGFDSA',
				'items' => $orig_address_fields,
				'bypass_suggestion_flag' => 'orig_bypass_suggestion',
				'summary' => str_replace( '{', '{orig_', $address_summary ),
				'sugggestion_hint' => 'vnkdjfitysncgkgse,jhxccvnaluerghsldjfvbzdj',
			);

			$layout[] = array(
				'type' => 'step',
				'tab_title' => 'Destination',
				'title' => 'Generate shipping label: Address verification',
				'description' => 'asdfghjkjhgfdsaASDFGHJJHGFDSA',
				'items' => $dest_address_fields,
				'bypass_suggestion_flag' => 'dest_bypass_suggestion',
				'summary' => str_replace( '{', '{dest_', $address_summary ),
				'sugggestion_hint' => 'qwertyuiopqwertyuiopqwerty uiopqwertyuiopqwertyuiop',
			);

			$layout[] = array(
				'type' => 'step',
				'tab_title' => 'Packages',
				'title' => 'Shopping cart contents',
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
				'tab_title' => 'Rates',
				'title' => 'Select a shipping service',
				'items' => array(
					array(
						'key' => 'rate',
						'type' => 'radios',
						'titleMap' => array(
							'media' => 'Media Mail ($1.00)',
							'pri_1' => 'Priority 1-day ($9.99)',
						),
					),
				),
				'summary' => '{rate}',
			);

			$layout[] = array(
				'type' => 'summary',
				'tab_title' => 'Preview',
				'title' => 'dfhdfghdfgh',
				'action_label' => 'Print',
				'confirmation_flag' => 'confirm',
			);

			return $layout;
		}

		protected function get_form_schema() {
			$properties = [];

			$address_fields = array(
				'first_name' => array(
					'type' => 'string',
					'title' => 'First name',
				),
				'last_name' => array(
					'type' => 'string',
					'title' => 'Last name',
				),
				'company' => array(
					'type' => 'string',
					'title' => 'Company',
				),
				'address_1' => array(
					'type' => 'string',
					'title' => 'Address',
				),
				'address_2' => array(
					'type' => 'string',
					'title' => 'Address (cont.)',
				),
				'city' => array(
					'type' => 'string',
					'title' => 'City',
				),
				'state' => array(
					'type' => 'string',
					'title' => 'State',
				),
				'postcode' => array(
					'type' => 'string',
					'title' => 'Postal code',
				),
				'country' => array(
					'type' => 'string',
					'title' => 'Country',
				),
			);
			$required_address_fields = ['first_name', 'address_1', 'city', 'state', 'postcode', 'country'];

			foreach( $address_fields as $key => $value ) {
				$properties[ 'orig_' . $key ] = $value;
				$properties[ 'dest_' . $key ] = $value;
			}

			$required_fields = [];

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
						'title' => 'Height',
					),
					'product_id' => array(
						'type' => 'string',
						'title' => 'Product ID',
					),
					'length' => array(
						'type' => 'number',
						'title' => 'Length',
					),
					'quantity' => array(
						'type' => 'number',
						'title' => 'Quantity',
					),
					'weight' => array(
						'type' => 'number',
						'title' => 'Weight',
					),
					'width' => array(
						'type' => 'number',
						'title' => 'Width',
					),
				),
			);

			$properties[ 'cart' ] = array(
				'type' => 'array',
				'title' => 'Items to send',
				'items' => $itemDefinition,
			);
			$required_fields[] = 'cart';

			$properties[ 'rate' ] = array(
				'type' => 'string',
				'title' => 'Shipping rate',
				'enum' => array( 'pri_1', 'media' ),
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

			$order = $theorder;

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
				'formData'     => $this->get_form_data( $order ),
				'callbackURL'  => get_rest_url( null, "/wc/v1/connect/shipping-label" ),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
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
