<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Migration_Flag_Controller' ) ) {
	return;
}

class WC_REST_Connect_Migration_Flag_Controller extends WC_REST_Connect_Base_Controller {
	public const MIGRATION_STATE_NOT_STARTED = 1;
	public const MIGRATION_STATE_STARTED = 2;
	public const MIGRATION_STATE_COMPLETED = 3;
	public const MIGRATION_STATE_FAILED = 4;

	protected $rest_base = 'connect/migration-flag';

	private function is_valid_state($state) {
		$valid_states = [
			self::MIGRATION_STATE_NOT_STARTED,
			self::MIGRATION_STATE_STARTED,
			self::MIGRATION_STATE_COMPLETED,
			self::MIGRATION_STATE_FAILED
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

		WC_Connect_Options::update_option( 'wcshipping_migration_state', $migration_state );

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

}
