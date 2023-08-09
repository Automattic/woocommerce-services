<?php

// mocks of various Jetpack functions/classes we need for our tests

define( 'JETPACK__VERSION', '4.0' );

global $mock_recorded_tracks_events;

function jetpack_tracks_record_event() {
	global $mock_recorded_tracks_events;
	$mock_recorded_tracks_events[] = func_get_args();
	return func_get_args();
}

/*
class Jetpack_Options {
	static function get_option( $option ) {
		switch ( $option ) {
			case 'id':
				return 12345;
			default:
				return false;
		}
	}
}*/
