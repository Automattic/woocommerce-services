/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import '../assets/stylesheets/admin-notices.scss';

const TIME_TO_REMMEMBER_DISMISSAL_SECONDS = 3 * 24 * 60 * 60; // 3 Days - number of seconds

( function ( $ ) {
	$( '.wcst-wcshipping-migration-notice' ).on( 'click', '.notice-dismiss', () => {
		window.wpCookies.set( window.wc_connect_admin_notices.dismissalCookieKey, 1, TIME_TO_REMMEMBER_DISMISSAL_SECONDS );
	} );
})( window.jQuery );
