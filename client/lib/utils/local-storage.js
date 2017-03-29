//import from calypso
import localStorageModule from 'lib/local-storage';
localStorageModule();

export const MAX_AGE = 86400000;

export const setWithExpiry = ( key, obj ) => {
	const json = JSON.stringify( {
		...obj,
		_timestamp: Date.now(),
	} );
	localStorage.setItem( key, json );
};

export const getWithExpiry = ( key, maxAge = MAX_AGE ) => {
	const json = localStorage.getItem( key );
	if ( json ) {
		const item = JSON.parse( json );
		if ( maxAge > Date.now() - item._timestamp ) {
			delete item._timestamp;
			return item;
		}
	}

	return null;
};

export const remove = ( key ) => {
	localStorage.removeItem( key );
};
