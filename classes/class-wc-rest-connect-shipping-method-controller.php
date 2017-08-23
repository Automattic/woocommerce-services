<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Shipping_Method_Controller' ) ) {
	return;
}

class WC_REST_Connect_Shipping_Method_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/shipping-method/(?P<method_id>[a-zA-Z0-9_]+)(/(?P<instance_id>\d+))?';

	/*
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $schemas_store;

	/*
	 * @var WC_Connect_Nux
	 */
	protected $nux;

	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Logger $logger,
		WC_Connect_Service_Schemas_Store $schemas_store,
		WC_Connect_Nux $nux
	) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->schemas_store = $schemas_store;
		$this->nux = $nux;
	}

	public function get( $request ) {
		$method_id = $request[ 'method_id' ];
		$instance_id = isset( $request[ 'instance_id' ] ) ? $request[ 'instance_id' ] : false;

		$service_schema = $this->schemas_store->get_service_schema_by_id_or_instance_id( $instance_id
			? $instance_id
			: $method_id );

		if ( ! $service_schema ) {
			return new WP_Error( 'schemas_not_found', __( 'Service schemas were not loaded', 'woocommerce-services' ), array( 'status' => 500 ) );
		}

		$payload = array(
			'success'            => true,
			'storeOptions'       => $this->settings_store->get_store_options(),
			'formSchema'         => $service_schema->service_settings,
			'formLayout'         => $service_schema->form_layout,
			'formData'           => $this->settings_store->get_service_settings( $method_id, $instance_id ),
			'methodId'           => $method_id,
			'instanceId'         => $instance_id,
			//'callbackURL'        => get_rest_url( null, $path ),
			//'nonce'              => wp_create_nonce( 'wp_rest' ),
			'noticeDismissed'    => $this->nux->is_notice_dismissed( 'service_settings' ),
			//'dismissURL'         => get_rest_url( null, '/wc/v1/connect/services/dismiss_notice' )
		);

		return new WP_REST_Response( $payload, 200 );
	}
}