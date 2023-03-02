<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_Notice_Manager' ) ) {

	class WC_Connect_Notice_Manager {
		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		private $schemas_store;

		public function __construct( WC_Connect_Service_Schemas_Store $schemas_store ) {
			$this->schemas_store = $schemas_store;
		}

		/**
		 * Returns if a notice ID was already dismissed in the past.
		 *
		 * @param string $id Notice ID.
		 *
		 * @return bool Is the notice ID marked as dismissed in the past.
		 */
		public function is_dismissed( $id ) {
			$dismissed_in_transient = get_transient( 'wcc_notice_dismissed_' . $id ); // Legacy approach.
			$dismissed_in_option    = get_option( 'wcc_notice_dismissed_' . $id );

			return false !== $dismissed_in_transient || false !== $dismissed_in_option;
		}

		/**
		 * Returns if a notice ID can be dismissed.
		 *
		 * @param string $id Notice ID.
		 *
		 * @return bool Is the notice ID dismissible.
		 */
		public function is_dismissible( $id ) {
			return in_array( $id, $this->get_dismissible_notice_ids(), true );
		}

		/**
		 * @return object[]
		 */
		public function get_undismissed_notices() {
			return array_filter(
				$this->get_available_notices(),
				function ( $notice ) {
					return ! $this->is_dismissed( $notice->id );
				}
			);
		}

		/**
		 * @param string $id Notice ID.
		 *
		 * @return void
		 */
		public function dismiss( $id ) {
			$dismissible_notices = $this->get_dismissible_notice_ids();

			if ( ! in_array( $id, $dismissible_notices, true ) ) {
				throw new InvalidArgumentException( '', 'notice_id_invalid' );
			}

			update_option( 'wcc_notice_dismissed_' . $id, time() );
		}

		/**
		 * Returns IDs of notices that can be dismissed.
		 *
		 * @return int[]|string[]
		 */
		public function get_dismissible_notice_ids() {
			$notices = wp_list_pluck( $this->get_available_notices(), 'dismissible', 'id' );

			return array_keys( array_filter( $notices ) );
		}

		/**
		 * Array of notices received from the Connect Server upon last services refresh,
		 * indexed by notice ID.
		 *
		 * @return object[]
		 */
		public function get_available_notices() {
			$schemas = $this->schemas_store->get_service_schemas();
			$notices = isset( $schemas->notices ) ? $schemas->notices : array();

			return array_combine(
				wp_list_pluck( $notices, 'id' ),
				$notices
			);
		}
	}

}
