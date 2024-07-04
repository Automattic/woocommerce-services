<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_REST_Connect_Migration_Flag_Controller' ) ) {
	return;
}

class WC_REST_Connect_Migration_Flag_Controller extends WC_REST_Connect_Base_Controller {
	protected $rest_base = 'connect/migration-flag';

	/**
	 * Tracks instance.
	 *
	 * @var WC_Connect_Tracks
	 */
	protected $tracks;

	public function __construct( $api_client, $settings_store, $logger, $tracks ) {
		parent::__construct( $api_client, $settings_store, $logger );

		$this->set_tracks( $tracks );
	}

	/**
	 * Set tracks instance.
	 *
	 * @param WC_Connect_Tracks $tracks Tracks instance.
	 */
	public function set_tracks( $tracks ) {
		$this->tracks = $tracks;
	}

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

		$existing_migration_state = WC_Connect_Options::get_option( 'wcshipping_migration_state' );
		if ( $existing_migration_state === $migration_state ) {
			return new WP_REST_Response( array( 'result' => 'Migration flag is the same, no changes needed.' ), 304 );
		}

		$result = WC_Connect_Options::update_option( 'wcshipping_migration_state', $migration_state );

		if ( $result ) {
			$this->tracks->record_user_event(
				'migration_flag_state_update',
				array(
					'migration_state' => $migration_state,
					'updated'         => $result,
				)
			);
			return new WP_REST_Response( array( 'result' => 'Migration flag updated successfully.' ), 200 );
		}

		$error = new WP_Error(
			'wcst_to_wcshipping_migration_failed_to_update',
			__( 'Unable to update migration flag. The flag could not be updated.', 'woocommerce-services' ),
			array( 'status' => 500 )
		);
		$this->logger->log( $error, __CLASS__ );
		return $error;
	}
}
