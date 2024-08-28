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

		// If the migration state is greater than "COMPLETED", then we can assume that the next part of the migration
		// state is being handled by WooCommerce Shipping.
		// This shouldn't be necessary since our migration shouldn't be displayed to begin with, but we're adding
		// support for it, just in case.
		if ( $migration_state > WC_Connect_WCST_To_WCShipping_Migration_State_Enum::COMPLETED ) {
			return new WP_REST_Response(
				array(
					'result' => __( 'WooCommerce Shipping has taken over the migration process.', 'woocommerce-services' ),
				),
				200
			);
		}

		if ( ! WC_Connect_WCST_To_WCShipping_Migration_State_Enum::is_valid_value( $migration_state ) ) {
			return new WP_Error(
				'invalid_migration_state',
				__( 'Invalid migration state. Can not update migration state.', 'woocommerce-services' ),
				array( 'status' => 400 )
			);
		}

		$existing_migration_state = get_option( 'wcshipping_migration_state' );
		if ( $existing_migration_state && intval( $existing_migration_state ) === $migration_state ) {
			return new WP_REST_Response( array( 'result' => __( 'Migration flag is the same, no changes needed.', 'woocommerce-services' ) ), 304 );
		}

		$result = update_option( 'wcshipping_migration_state', $migration_state );

		if ( $result ) {
			$this->tracks->record_user_event(
				'migration_flag_state_update',
				array(
					'migration_state' => $migration_state,
					'updated'         => $result,
				)
			);

			return new WP_REST_Response( array( 'result' => __( 'Migration flag updated successfully.', 'woocommerce-services' ) ), 200 );
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
