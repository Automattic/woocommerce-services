<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Migration_Flag_Controller' ) ) {
	return;
}

class WC_REST_Connect_Migration_Flag_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/migration-flag';

	public function post( $request ) {
		$params          = $request->get_json_params();
		$migration_state = intval( $params['migration_state'] );
		if ( ! WC_Connect_WCST_To_WCShipping_Migration_State_Enum::is_valid_value( $migration_state ) ) {
			$error = new WP_Error(
				'invalid_migration_state',
				__( 'Invalid migration state. Can not update migration state.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			return $error;
		}

		$result = WC_Connect_Options::update_option( 'wcshipping_migration_state', $migration_state );

		if ( $result ) {
			return new WP_REST_Response( array( 'result' => 'Migration flag updated successfully.' ), 200 );
		}

		return new WP_REST_Response( array( 'result' => 'Migration flag did not update.' ), 304 );
	}
}
