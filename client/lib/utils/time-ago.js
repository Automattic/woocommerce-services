import { translate as __ } from 'i18n-calypso';

export default ( date ) => {
	const seconds = Math.floor( ( new Date().getTime() - date ) / 1000 );

	let interval = Math.floor( seconds / 31536000 );
	if ( 1 <= interval ) {
		return __( 'a year ago', '%(interval)d years ago', { count: interval, args: { interval } } );
	}

	interval = Math.floor( seconds / 2592000 );
	if ( 1 <= interval ) {
		return __( 'a month ago', '%(interval)d months ago', { count: interval, args: { interval } } );
	}

	interval = Math.floor( seconds / 86400 );
	if ( 1 <= interval ) {
		return __( 'a day ago', '%(interval)d days ago', { count: interval, args: { interval } } );
	}

	interval = Math.floor( seconds / 3600 );
	if ( 1 <= interval ) {
		return __( 'an hour ago', '%(interval)d hours ago', { count: interval, args: { interval } } );
	}

	interval = Math.floor( seconds / 60 );
	if ( 1 <= interval ) {
		return __( 'a minute ago', '%(interval)d minutes ago', { count: interval, args: { interval } } );
	}

	return __( 'less than a minute ago' );
};
