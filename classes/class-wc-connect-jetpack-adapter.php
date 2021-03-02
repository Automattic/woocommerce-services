<?php

use Automattic\Jetpack\Connection\Tokens;
use Automattic\Jetpack\Connection\Manager;

class WC_Connect_Jetpack_Adapter {
	public static function get_master_user_access_token( $user_id ) {
		if ( class_exists( '\Automattic\Jetpack\Connection\Tokens' ) && method_exists( '\Automattic\Jetpack\Connection\Tokens', 'get_access_token' ) ) {
			$connection = new Tokens();

			return $connection->get_access_token( $user_id );
		}

		if ( class_exists( '\Automattic\Jetpack\Connection\Manager' ) && method_exists( '\Automattic\Jetpack\Connection\Manager', 'get_access_token' ) ) {
			$connection = new Manager();

			return $connection->get_access_token( $user_id );
		}

		// fallback
		return new stdClass();
	}
}
