<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/label/(?P<order_id>\d+)';

	/*
	 * @var WC_Connect_Shipping_Label
	 */
	protected $shipping_label;

	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_Service_Settings_Store $settings_store, WC_Connect_Logger $logger, WC_Connect_Shipping_Label $shipping_label ) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->shipping_label = $shipping_label;
	}

	public function register_routes() {
		parent::register_routes();

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/creation_eligibility', array(
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_creation_eligibility' ),
				'permission_callback' => array( $this, 'check_permission' ),
			),
		) );
	}

	public function get( $request ) {
		$order_id = $request['order_id'];
		$payload = $this->shipping_label->get_label_payload( $order_id );
		if ( ! $payload ) {
			return new WP_Error( 'not_found', __( 'Order not found', 'woocommerce-services' ), array( 'status' => 404 ) );
		}
		$payload[ 'success' ] = true;
		return new WP_REST_Response( $payload, 200 );
	}

	public function post( $request ) {
		$settings = $request->get_json_params();
		$order_id = $request[ 'order_id' ];
		$settings[ 'order_id' ] = $order_id;

		if ( empty( $settings[ 'payment_method_id' ] ) || ! $this->settings_store->can_user_manage_payment_methods() ) {
			$settings[ 'payment_method_id' ] = $this->settings_store->get_selected_payment_method_id();
		} else {
			$this->settings_store->set_selected_payment_method_id( $settings[ 'payment_method_id' ] );
		}

		$last_box_id = '';
		$service_names = array();
		foreach ( $settings[ 'packages' ] as $index => $package ) {
			$service_names[] = $package[ 'service_name' ];
			unset( $package[ 'service_name' ] );
			$settings[ 'packages' ][ $index ] = $package;

			if ( empty( $last_box_id ) && ! empty( $package['box_id'] ) ) {
				$last_box_id = $package['box_id'];
			}
		}

		if ( ! empty( $last_box_id ) && $last_box_id !== "individual" ) {
			update_user_meta( get_current_user_id(), 'wc_connect_last_box_id', $last_box_id );
		}

		$response = $this->api_client->send_shipping_label_request( $settings );

		if ( is_wp_error( $response ) ) {
			$error = new WP_Error(
				$response->get_error_code(),
				$response->get_error_message(),
				array( 'message' => $response->get_error_message() )
			);
			$this->logger->log( $error, __CLASS__ );
			return $error;
		}

		$label_ids = array();
		$purchased_labels_meta = array();
		$package_lookup = $this->settings_store->get_package_lookup();
		foreach ( $response->labels as $index => $label_data ) {
			if ( isset( $label_data->error ) ) {
				$error = new WP_Error(
					$label_data->error->code,
					$label_data->error->message,
					array( 'message' => $label_data->error->message )
				);
				$this->logger->log( $error, __CLASS__ );
				return $error;
			}
			$label_ids[] = $label_data->label->label_id;

			$label_meta = array(
				'label_id' => $label_data->label->label_id,
				'tracking' => $label_data->label->tracking_id,
				'refundable_amount' => $label_data->label->refundable_amount,
				'created' => $label_data->label->created,
				'carrier_id' => $label_data->label->carrier_id,
				'service_name' => $service_names[ $index ],
				'status' => $label_data->label->status,
				'commercial_invoice_url' => $label_data->label->commercial_invoice_url,
			);

			$package = $settings[ 'packages' ][ $index ];
			$box_id = $package[ 'box_id' ];
			if ( 'individual' === $box_id ) {
				$label_meta[ 'package_name' ] = __( 'Individual packaging', 'woocommerce-services' );
			} else if ( isset( $package_lookup[ $box_id ] ) ) {
				$label_meta[ 'package_name' ] = $package_lookup[ $box_id ][ 'name' ];
			} else {
				$label_meta[ 'package_name' ] = __( 'Unknown package', 'woocommerce-services' );
			}

			$label_meta[ 'is_letter' ] = isset( $package[ 'is_letter' ] ) ? $package[ 'is_letter' ] : false;

			$product_names = array();
			$product_ids = array();
			foreach ( $package[ 'products' ] as $product_id ) {
				$product = wc_get_product( $product_id );
				$product_ids[] = $product_id;

				if ( $product ) {
					$product_names[] = $product->get_title();
				} else {
					$order = wc_get_order( $order_id );
					$product_names[] = WC_Connect_Compatibility::instance()->get_product_name_from_order( $product_id, $order );
				}
			}

			$label_meta[ 'product_names' ] = $product_names;
			$label_meta[ 'product_ids' ] = $product_ids;

			array_unshift( $purchased_labels_meta, $label_meta );
		}

		$this->settings_store->add_labels_to_order( $order_id, $purchased_labels_meta );

		return array(
			'labels' => $purchased_labels_meta,
			'success' => true,
		);
	}

	/* Available params for $request:
	   - `us_stores_only: Boolean`: optional with default value `false`. If `true`, only stores and origin addresses
	     with `US` country code are eligible for label creation.
	   - `can_manage_payment: Boolean`: optional with default value `true`. If `false`, a pre-selected payment method is
	     required for label creation. Otherwise, stores with a pre-selected payment method or users who can manage
	     payment methods can create a label.
	   - `customs_form_supported: Boolean`: optional with default value `true`. If `false`, the order is eligible for
	     label creation if a customs form is not required for the origin and destination address.
	*/
	public function get_creation_eligibility( $request ) {
		$order_id = $request['order_id'];

		$order = wc_get_order($order_id);

		if (!$order) {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'order_not_found'
			), 200);
		}

		// The store has to be in the US if `us_stores_only` param is set to `true`
		$us_stores_only = isset($request['us_stores_only']) ? $request['us_stores_only']: false;
		$store_country = wc_get_base_location()['country'];
		if ($us_stores_only && $store_country !== 'US') {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'store_country_not_supported_with_us_stores_only'
			), 200);
		}

		// The store country is supported
		if (!$this->shipping_label->is_supported_country($store_country)) {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'store_country_not_supported'
			), 200);
		}

		// The store currency is supported
		$store_currency = get_woocommerce_currency();
		if (!$this->shipping_label->is_supported_currency($store_currency)) {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'store_currency_not_supported'
			), 200);
		}

		// The destination address of the order is in the US / does not require a customs form
		// TODO-jc

		// There is at least one non-refunded and shippable product
		$shippable_product_ids = array();
		foreach ($order->get_items() as $item) {
			$product = WC_Connect_Compatibility::instance()->get_item_product( $order, $item );
			if ( $product && $product->needs_shipping() ) {
				array_push($shippable_product_ids, $product->id);
			}
		}
		$existing_labels = $this->settings_store->get_label_order_meta_data($order_id);
		$packaged_product_ids = array();
		foreach ($existing_labels as $existing_label) {
			if ($existing_label['refund']) {
				continue;
			}
			$product_ids = $existing_label['product_ids'];
			array_merge($packaged_product_ids, $product_ids);
		}

		if (count(array_unique($shippable_product_ids)) <= count(array_unique($packaged_product_ids))) {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'no_products_for_shipping_label_creation'
			), 200);
		}

		// If the client cannot manage payment (`can_manage_payment` param is set to `false`), a pre-selected payment method is required
		$client_can_manage_payment = isset($request['can_manage_payment']) ? $request['can_manage_payment']: true;
		if (!$client_can_manage_payment && !$this->settings_store->get_selected_payment_method_id()) {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'no_selected_payment_method_when_client_cannot_manage_payment'
			), 200);
		}

		// There is a pre-selected payment method or the user can manage the store
		if (!($this->settings_store->get_selected_payment_method_id() || $this->settings_store->can_user_manage_payment_methods())) {
			return new WP_REST_Response(array(
				'is_eligible' => false,
				'reason' => 'no_selected_payment_method_and_user_cannot_manage_payment_methods'
			), 200);
		}

		return new WP_REST_Response(array(
			'is_eligible' => true
		), 200);
	}
}
