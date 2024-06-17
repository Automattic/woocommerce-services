<?php
/**
 * Enum for WCS&T to WCShipping migration states.
 */
class WC_Connect_WCST_To_WCShipping_Migration_State_Enum {
	// These are used for WCS&T to WCShipping migration.
	public const NOT_STARTED = 1;
	public const STARTED     = 2;
	public const COMPLETED   = 3;
	public const FAILED      = 4;

	public static function is_valid_value( $state ) {
		$valid_states = array(
			self::NOT_STARTED,
			self::STARTED,
			self::COMPLETED,
			self::FAILED,
		);
		if ( in_array( $state, $valid_states, true ) ) {
			return true;
		}
		return false;
	}
}
