import { translate as __ } from 'i18n-calypso';
import { sprintf } from 'sprintf-js';

export default ( date ) => {
	const seconds = Math.floor( ( new Date().getTime() - date ) / 1000 );

	let interval = Math.floor( seconds / 31536000 );
	if ( 1 < interval ) {
		return sprintf( __( '%d years ago' ), interval );
	}
	if ( 1 === interval ) {
		return __( 'a year ago' );
	}

	interval = Math.floor( seconds / 2592000 );
	if ( 1 < interval ) {
		return sprintf( __( '%d months ago' ), interval );
	}
	if ( 1 === interval ) {
		return sprintf( __( 'a month ago' ), interval );
	}

	interval = Math.floor( seconds / 86400 );
	if ( 1 < interval ) {
		return sprintf( __( '%d days ago' ), interval );
	}
	if ( 1 === interval ) {
		return sprintf( __( 'a day ago' ), interval );
	}

	interval = Math.floor( seconds / 3600 );
	if ( 1 < interval ) {
		return sprintf( __( '%d hours ago' ), interval );
	}
	if ( 1 === interval ) {
		return sprintf( __( 'an hour ago' ), interval );
	}

	interval = Math.floor( seconds / 60 );
	if ( 1 < interval ) {
		return sprintf( __( '%d minutes ago' ), interval );
	}
	if ( 1 === interval ) {
		return sprintf( __( 'a minute ago' ), interval );
	}

	return __( 'less than a minute ago' );
};
