<?php
/**
 * Notifier class.
 *
 * This class handles the collection and display of notices on the frontend.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Notifier class.
 */
class StoreNoticesNotifier {

	/**
	 * WC Session variable key
	 *
	 * @var string
	 */
	const WC_SESSION_KEY = 'wcservices_store_notices';

	/**
	 * Valid notice types.
	 *
	 * @var array
	 */
	private static array $valid_notice_types = array( StoreNoticeTypes::ERROR, StoreNoticeTypes::SUCCESS, StoreNoticeTypes::INFO );

	/**
	 * Is debug enabled.
	 *
	 * @var bool
	 */
	private bool $is_debug_enabled;

	/**
	 * Constructor.
	 *
	 * @param bool $is_debug_enabled Is debug enabled.
	 */
	public function __construct( bool $is_debug_enabled ) {
		$this->is_debug_enabled = $is_debug_enabled;
	}

	/**
	 * Add a debug notice.
	 * Debug notices are only displayed if debug is enabled and the user has the manage_options capability.
	 *
	 * @param string $message Message to display.
	 * @param string $type    Type of notice - either error, success or notice.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	public function debug( string $message, string $type = 'notice', array $data = array(), string $group = '' ): void {
		if ( ! $this->is_debug_enabled() ) {
			return;
		}

		// Only display debug notices to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$type = $this->sanitize_type( $type );

		$this->maybe_add_notice( $message, $type, $data, $group );
	}

	/**
	 * Check if debug is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled(): bool {
		return $this->is_debug_enabled;
	}

	/**
	 * Sanitize notice type.
	 *
	 * If an invalid notice type is passed, it will default to 'notice'.
	 *
	 * @param string $type Type of notice - either error, success or notice.
	 *
	 * @return mixed|string
	 */
	public function sanitize_type( string $type ) {
		if ( ! in_array( $type, self::$valid_notice_types, true ) ) {
			$type = 'notice';
		}

		return $type;
	}

	/**
	 * Add notice if not already added.
	 *
	 * If an invalid notice type is passed, it will default to 'notice'.
	 *
	 * @param string $message Message to display.
	 * @param string $type    Type of notice - either error, success or notice.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 *
	 * @return void
	 */
	public function maybe_add_notice( string $message, string $type = 'notice', array $data = array(), string $group = '' ): void {

		if ( $this->has_notice( $message, $type, $data, $group ) ) {
			return;
		}

		$type = $this->sanitize_type( $type );

		// Add a custom notice so that we can pull only WooCommerce Services related notices when needed.
		$this->add_notice( $message, $type, $data, $group );
	}

	/**
	 * Check if a notice has already been added.
	 *
	 * @param string $message The text to display in the notice.
	 * @param string $type    Optional. The name of the notice type - either error, success or notice.
	 * @param array  $data    Optional. Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 *
	 * @return bool
	 */
	public function has_notice( string $message, string $type = 'notice', array $data = array(), string $group = '' ): bool {
		$notices = WC()->session->get( self::WC_SESSION_KEY, array() );

		$notices = $notices[ $type ] ?? array();

		$notice = $this->format_notice( $message, $data, $group );

		return in_array( $notice, $notices, true );
	}

	/**
	 * Format a notice.
	 *
	 * @param string $message Message to display.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Group to categorize notices.
	 *
	 * @return array
	 */
	private function format_notice( string $message, array $data, string $group ): array {
		return array(
			'message' => $message,
			'data'    => $data ? print_r( $data, true ) : '', //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- print_r use for debug log formatting.
			'group'   => $group,
		);
	}

	/**
	 * Add a notice.
	 *
	 * @param string $message Message to display.
	 * @param string $type    Type of notice - either error, success or notice.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	private function add_notice( string $message, string $type, array $data = array(), string $group = '' ): void {
		if ( ! self::wc_session_exists() ) {
			return;
		}

		$type = $this->sanitize_type( $type );

		$notice = $this->format_notice( $message, $data, $group );

		$notices = WC()->session->get( self::WC_SESSION_KEY, array() );

		if ( ! isset( $notices[ $type ] ) ) {
			$notices[ $type ] = array();
		}

		$notices[ $type ][] = $notice;

		WC()->session->set( self::WC_SESSION_KEY, $notices );
	}

	/**
	 * Check if WC Session exists.
	 *
	 * @return bool
	 */
	public static function wc_session_exists(): bool {
		return ! empty( WC()->session );
	}

	/**
	 * Add an info notice.
	 *
	 * @param string $message Message to display.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	public function info( string $message, array $data = array(), string $group = '' ) {
		$this->maybe_add_notice( $message, StoreNoticeTypes::INFO, $data, $group );
	}

	/**
	 * Add an error notice.
	 *
	 * @param string $message Message to display.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	public function error( string $message, array $data = array(), string $group = '' ) {
		$this->maybe_add_notice( $message, StoreNoticeTypes::ERROR, $data, $group );
	}

	/**
	 * Add a success notice.
	 *
	 * @param string $message Message to display.
	 * @param array  $data    Additional data to pass.
	 * @param string $group   Optional. Group to categorize notices.
	 */
	public function success( string $message, array $data = array(), string $group = '' ) {
		$this->maybe_add_notice( $message, StoreNoticeTypes::SUCCESS, $data, $group );
	}

	/**
	 * Print all notices.
	 */
	public function print_notices(): void {
		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $type => $messages ) {
			foreach ( $messages as $notice ) {
				$formatted_message = $this->maybe_get_formatted_message( $notice['message'], $notice['data'] );

				if ( wc_has_notice( $formatted_message, $type ) ) {
					continue;
				}

				wc_add_notice( $formatted_message, $type );
			}
		}
	}

	/**
	 * Get all notices.
	 *
	 * @return array
	 */
	public static function get_notices(): array {
		return self::wc_session_exists() ? WC()->session->get( self::WC_SESSION_KEY, array() ) : array();
	}

	/**
	 * Clear all notices.
	 *
	 * @param string $group Optional. Group of notices to clear.
	 */
	public static function clear_notices( string $group = '' ): void {
		if ( ! self::wc_session_exists() ) {
			return;
		}

		// Clear all notices.
		if ( empty( $group ) ) {
			WC()->session->set( self::WC_SESSION_KEY, array() );

			return;
		}

		$notices = WC()->session->get( self::WC_SESSION_KEY, array() );
		if ( ! is_array( $notices ) ) {
			return;
		}

		$updated_notices = array();

		// Clear notices by group.
		foreach ( self::$valid_notice_types as $type ) {
			if ( empty( $notices[ $type ] ) || ! is_array( $notices[ $type ] ) ) {
				continue;
			}

			foreach ( $notices[ $type ] as $notice ) {
				if ( $notice['group'] === $group ) {
					continue;
				}

				$updated_notices[ $type ][] = $notice;
			}
		}

		WC()->session->set( self::WC_SESSION_KEY, $updated_notices );
	}

	/**
	 * Maybe get formatted message.
	 *
	 * @param string $message Message to display.
	 * @param string $data    Additional data to pass.
	 *
	 * @return string
	 */
	public function maybe_get_formatted_message( string $message, string $data = '' ): string {
		if ( ! empty( $data ) ) {
			$formatted_message = '<details><summary style="line-height:1.8;">' . $message . '</summary><div style="overflow: auto; max-height:50vh;"><pre style="font-size:0.8rem; line-height:1.4;">' . $data . '</pre></div></details>';
		} else {
			$formatted_message = $message;
		}

		return $formatted_message;
	}
}
