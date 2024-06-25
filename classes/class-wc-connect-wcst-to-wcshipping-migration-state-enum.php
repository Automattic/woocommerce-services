<?php
/**
 * Enum for WCS&T to WCShipping migration states.
 */
class WC_Connect_WCST_To_WCShipping_Migration_State_Enum {
	// These are used for WCS&T to WCShipping migration.
	public const NOT_STARTED        = 1;
	public const STARTED            = 2;
	public const ERROR_STARTED      = 3;
	public const INSTALLING         = 4;
	public const ERROR_INSTALLING   = 5;
	public const ACTIVATING         = 6;
	public const ERROR_ACTIVATING   = 7;
	public const DB_MIGRATION       = 8;
	public const ERROR_DB_MIGRATION = 9;
	public const DEACTIVATING       = 10;
	public const ERROR_DEACTIVATING = 11;
	public const COMPLETED          = 12;

	public static function is_valid_value( $state ) {
		$valid_states = array(
			self::NOT_STARTED,
			self::STARTED,
			self::ERROR_STARTED,
			self::INSTALLING,
			self::ERROR_INSTALLING,
			self::ACTIVATING,
			self::ERROR_ACTIVATING,
			self::DB_MIGRATION,
			self::ERROR_DB_MIGRATION,
			self::DEACTIVATING,
			self::ERROR_DEACTIVATING,
			self::COMPLETED,
		);
		return in_array( $state, $valid_states, true );
	}
}
