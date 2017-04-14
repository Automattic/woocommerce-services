<?php

if ( ! class_exists( 'WC_Connect_Utils' ) ) {
	class WC_Connect_Utils {
		/**
		 * Encodes an object to JSON. Uses wp_json_encode to escape quotes and then double-escapes them for
		 * saving in the database
		 *
		 * @param $object
		 *
		 * @return string
		 */
		public static function json_encode_for_db( $object ) {
			$json = wp_json_encode( $object );
			return str_replace( '\\"', '\\\\"', $json );
		}
	}
}
