<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Notices_Controller' ) ) {
	return;
}

class WC_REST_Connect_Notices_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/notices';

	/**
	 * @var WC_Connect_Notice_Manager
	 */
	protected $notices;

	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Logger $logger,
		WC_Connect_Notice_Manager $notices
	) {
		parent::__construct( $api_client, $settings_store, $logger );
		$this->notices = $notices;
	}

	public function register_routes() {
		parent::register_routes();

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/dismiss',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'dismiss' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'id' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	public function dismiss( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );

		try {
			$this->notices->dismiss( $id );
		} catch ( InvalidArgumentException $e ) {
			$error = new WP_Error(
				'notice_id_invalid',
				__( 'Unable to dismiss notice - notice ID is invalid or notice is not dismissible.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			$this->logger->log( $error->get_error_message(), __CLASS__ );
			return $error;
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
