<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Label_Eligibility_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Label_Eligibility_Controller extends WC_REST_Connect_Base_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'connect/label';


	/**
	 * @var WC_Connect_Shipping_Label
	 */
	protected $shipping_label;

	/**
	 * @var WC_Connect_Payment_Methods_Store
	 */
	protected $payment_methods_store;


	protected $has_only_tax_functionality;

	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Logger $logger,
		WC_Connect_Shipping_Label $shipping_label,
		WC_Connect_Payment_Methods_Store $payment_methods_store,
		$has_only_tax_functionality
	) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->shipping_label             = $shipping_label;
		$this->payment_methods_store      = $payment_methods_store;
		$this->has_only_tax_functionality = $has_only_tax_functionality;
	}

	/**
	 * Register the routes for the shipping label eligibility.
	 */
	public function register_routes() {
		// Accept request without order_id
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/creation_eligibility',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_creation_eligibility' ),
					'args'                => array(
						'order_id' => array(
							'required' => false,
							'type'     => 'integer',
						),
					),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Accept request with order_id
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<order_id>\d+)/creation_eligibility',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_creation_eligibility' ),
					'args'                => array(
						'order_id' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Available params for $request:
	 * - `can_create_payment_method: Boolean`: optional with default value `true`. If `false`, at least one existing payment method is
	 *   required for label creation.
	 * - `can_create_package: Boolean`: optional with default value `true`. If `false`, at least one pre-existing
	 *   package (custom or predefined) is required for label creation.
	 * - `can_create_customs_form: Boolean`: optional with default value `true`. If `false`, the order is eligible for
	 *   label creation if a customs form is not required for the origin and destination address in the US.
	 *
	 * @param WP_REST_Request $request API request with optional parameters as above.
	 * @return WP_REST_Response
	 */
	public function get_creation_eligibility( $request ) {

		if ( $this->has_only_tax_functionality ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'only_tax_functionality_is_supported',
				),
				200
			);
		}

		$order_id = $request['order_id'];
		$order    = empty( $order_id ) ? null : wc_get_order( $order_id );

		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'order_not_found',
				),
				200
			);
		}

		// Shipping labels should be enabled in account settings.
		if ( true !== $this->settings_store->get_account_settings()['enabled'] ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'account_settings_disabled',
				),
				200
			);
		}

		// Check if the store is eligible for shipping label creation.
		if ( ! $this->shipping_label->is_store_eligible_for_shipping_label_creation() ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'store_not_eligible',
				),
				200
			);
		}

		// If the client cannot create a customs form:
		// - The store address has to be in the US.
		// - The origin and destination addresses have to be in the US.
		$client_can_create_customs_form = isset( $request['can_create_customs_form'] ) ? filter_var( $request['can_create_customs_form'], FILTER_VALIDATE_BOOLEAN ) : true;
		$store_country                  = wc_get_base_location()['country'];
		if ( ! $client_can_create_customs_form ) {
			// The store address has to be in the US.
			if ( 'US' !== $store_country ) {
				return new WP_REST_Response(
					array(
						'is_eligible' => false,
						'reason'      => 'store_country_not_supported_when_customs_form_is_not_supported_by_client',
					),
					200
				);
			}

			// The origin and destination addresses have to be in the US.
			$origin_address      = $this->settings_store->get_origin_address();
			$destination_address = $order->get_address( 'shipping' );
			if ( 'US' !== $origin_address['country'] || 'US' !== $destination_address['country'] ) {
				return new WP_REST_Response(
					array(
						'is_eligible' => false,
						'reason'      => 'origin_or_destination_country_not_supported_when_customs_form_is_not_supported_by_client',
					),
					200
				);
			}
		}

		// If the client cannot create a package (`can_create_package` param is set to `false`), a pre-existing package
		// is required.
		$client_can_create_package = isset( $request['can_create_package'] ) ? filter_var( $request['can_create_package'], FILTER_VALIDATE_BOOLEAN ) : true;
		if ( ! $client_can_create_package ) {
			if ( empty( $this->settings_store->get_packages() ) && empty( $this->settings_store->get_predefined_packages() ) ) {
				return new WP_REST_Response(
					array(
						'is_eligible' => false,
						'reason'      => 'no_packages_when_client_cannot_create_package',
					),
					200
				);
			}
		}

		// There is at least one non-refunded and shippable product.
		if ( ! $this->shipping_label->is_order_eligible_for_shipping_label_creation( $order ) ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'order_not_eligible',
				),
				200
			);
		}

		// If the client cannot create a payment method (`can_create_payment_method` param is set to `false`), an existing payment method is required.
		$client_can_create_payment_method = isset( $request['can_create_payment_method'] ) ? filter_var( $request['can_create_payment_method'], FILTER_VALIDATE_BOOLEAN ) : true;
		if ( ! $client_can_create_payment_method && empty( $this->payment_methods_store->get_payment_methods() ) ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'no_payment_methods_and_client_cannot_create_one',
				),
				200
			);
		}

		// There is a pre-selected payment method or the user can manage payment methods.
		if ( ! ( $this->settings_store->get_selected_payment_method_id() || $this->settings_store->can_user_manage_payment_methods() ) ) {
			return new WP_REST_Response(
				array(
					'is_eligible' => false,
					'reason'      => 'no_selected_payment_method_and_user_cannot_manage_payment_methods',
				),
				200
			);
		}

		return new WP_REST_Response(
			array(
				'is_eligible' => true,
			),
			200
		);
	}
}
