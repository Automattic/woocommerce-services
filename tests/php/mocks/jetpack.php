<?php

// mocks of various Jetpack functions/classes we need for our tests

define( 'JETPACK__VERSION', '4.0' );

function jetpack_tracks_record_event() {
    return func_get_args();
}

class Jetpack_Options {
    static function get_option( $option ){
        switch( $option ) {
            case 'id':
                return 12345;
            default:
                return false;
        }
    }
}
