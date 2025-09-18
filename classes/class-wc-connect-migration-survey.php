<?php
/**
 * Migration Survey Class
 *
 * Handles the display and submission of migration survey for users
 * who haven't migrated from WCS&T to WooCommerce Shipping
 */

use Automattic\WCServices\Utils;

if ( ! class_exists( 'WC_Connect_Migration_Survey' ) ) {

	class WC_Connect_Migration_Survey {


		/**
		 * Maximum number of times to show the survey
		 */
		const MAX_DISPLAY_COUNT = 2;

		/**
		 * Cooldown period between survey displays (in seconds)
		 */
		const COOLDOWN_PERIOD = 259200; // 3 days

		/**
		 * Initialize the migration survey
		 */
		public function __construct() {
			// Hook into script enqueue action
			add_action( 'enqueue_wc_connect_script', array( $this, 'add_survey_data_to_script' ), 10, 2 );

			// AJAX handlers
			add_action( 'wp_ajax_wcs_migration_survey_submit', array( $this, 'handle_survey_submission' ) );
			add_action( 'wp_ajax_wcs_migration_survey_dismiss', array( $this, 'handle_survey_dismissal' ) );
			add_action( 'wp_ajax_wcs_migration_survey_track_display', array( $this, 'handle_survey_display_tracking' ) );
		}

		/**
		 * Check if survey should be shown to current user
		 */
		public function should_show_survey() {
			// Force survey to show for testing/debugging
			if ( isset( $_GET['force_survey'] ) ) {
				return true;
			}

			// Don't show if WooCommerce Shipping is already active
			if ( WC_Connect_Loader::is_wc_shipping_activated() ) {
				return false;
			}

			// Check if user has already been shown the survey max times
			$user_id       = get_current_user_id();
			$display_count = get_user_meta( $user_id, 'wcs_migration_survey_count', true );

			if ( $display_count >= self::MAX_DISPLAY_COUNT ) {
				return false;
			}

			// Check cooldown period
			$last_shown = get_user_meta( $user_id, 'wcs_migration_survey_last_shown', true );
			if ( $last_shown && ( time() - $last_shown ) < self::COOLDOWN_PERIOD ) {
				return false;
			}

			// Check if user has already completed the survey
			if ( get_user_meta( $user_id, 'wcs_migration_survey_completed', true ) ) {
				return false;
			}

			return true;
		}


		/**
		 * Add survey data to shipping label script localization
		 */
		public function add_survey_data_to_script( $root_view, $payload ) {
			// Only add survey data to shipping label context
			if ( 'wc-connect-create-shipping-label' !== $root_view ) {
				return;
			}

			$should_show = $this->should_show_survey();

			if ( ! $should_show ) {
				return;
			}

			// Add survey data via separate localize_script call
			// NOTE: We don't update user meta here - only when survey is actually displayed in frontend
			$survey_data = array(
				'shouldShow' => true,
				'nonce'      => wp_create_nonce( 'wcs_migration_survey' ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			);

			wp_localize_script( 'wc_connect_admin', 'wcsMigrationSurvey', $survey_data );
		}

		/**
		 * Handle survey submission via AJAX
		 */
		public function handle_survey_submission() {
			// Verify nonce
			if ( ! wp_verify_nonce( Utils::get_sanitized_request_data( 'nonce' ), 'wcs_migration_survey' ) ) {
				wp_die( 'Security check failed' );
			}

			// Get survey data
			$survey_data = json_decode( stripslashes( $_POST['survey_data'] ), true );

			if ( ! $survey_data ) {
				wp_send_json_error( 'Invalid survey data' );
			}

			// Mark survey as completed for this user
			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'wcs_migration_survey_completed', true );

			// Track submission event with detailed properties
			$track_properties = array(
				'primary_reason' => sanitize_text_field( $survey_data['primary'] ),
			);

			// Add followup responses to tracking (flatten the array)
			if ( ! empty( $survey_data['followup'] ) ) {
				foreach ( $survey_data['followup'] as $key => $value ) {
					if ( is_bool( $value ) ) {
						$track_properties[ 'followup_' . $key ] = $value ? 'true' : 'false';
					} else {
						$track_properties[ 'followup_' . $key ] = sanitize_text_field( $value );
					}
				}
			}

			$this->track_event( 'submitted', $track_properties );

			wp_send_json_success();
		}

		/**
		 * Handle survey dismissal
		 */
		public function handle_survey_dismissal() {
			// Verify nonce
			if ( ! wp_verify_nonce( Utils::get_sanitized_request_data( 'nonce' ), 'wcs_migration_survey' ) ) {
				wp_die( 'Security check failed' );
			}

			// Track dismissal event
			$this->track_event( 'dismissed', array() );

			wp_send_json_success();
		}

		/**
		 * Handle survey display tracking (update user meta when survey is actually shown)
		 */
		public function handle_survey_display_tracking() {
			// Verify nonce
			if ( ! wp_verify_nonce( Utils::get_sanitized_request_data( 'nonce' ), 'wcs_migration_survey' ) ) {
				wp_die( 'Security check failed' );
			}

			// Update user meta to track that survey was displayed
			$user_id       = get_current_user_id();
			$display_count = get_user_meta( $user_id, 'wcs_migration_survey_count', true );
			update_user_meta( $user_id, 'wcs_migration_survey_count', intval( $display_count ) + 1 );
			update_user_meta( $user_id, 'wcs_migration_survey_last_shown', time() );

			// Track survey display event
			$this->track_event( 'displayed', array() );

			wp_send_json_success();
		}

		/**
		 * Track survey events
		 */
		private function track_event( $event_name, $properties = array() ) {
			$wc_logger = wc_get_logger();
			$logger    = new WC_Connect_Logger( $wc_logger );
			$tracks    = new WC_Connect_Tracks( $logger, WCSERVICES_PLUGIN_FILE );
			$tracks->record_user_event( 'migration_survey_' . $event_name, $properties );
		}
	}
}
