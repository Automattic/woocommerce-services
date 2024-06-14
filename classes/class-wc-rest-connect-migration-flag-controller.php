<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Migration_Flag_Controller' ) ) {
	return;
}

class WC_REST_Connect_Migration_Flag_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/migration-flag';

	private function is_valid_state($state) {
		$valid_states = [
			WC_Connect_API_Constants::MIGRATION_STATE_NOT_STARTED,
			WC_Connect_API_Constants::MIGRATION_STATE_STARTED,
			WC_Connect_API_Constants::MIGRATION_STATE_COMPLETED,
			WC_Connect_API_Constants::MIGRATION_STATE_FAILED
		];
		if (in_array($state, $valid_states, true)) {
			return true;
		}
		return false;
	}

	public function post($request) {
		$params = $request->get_json_params();
		$migration_state = intval($params['migration_state']);
		if (!$this->is_valid_state($migration_state)) {
			$error = new WP_Error(
				'invalid_migration_state',
				__( 'Invalid migration state. Can not update migration state.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
			return $error;
		}

		$result = WC_Connect_Options::update_option( 'wcshipping_migration_state', $migration_state );

		return new WP_REST_Response( array( 'result' => $result ), 200 );
	}

}
