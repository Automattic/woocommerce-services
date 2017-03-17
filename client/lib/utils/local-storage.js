export const MAX_AGE = 86400000;

export const setWithExpiry = ( key, obj ) => {
	const json = JSON.stringify( {
		...obj,
		_timestamp: Date.now(),
	} );
	window.localStorage.setItem( key, json );
};

export const getWithExpiry = ( key, maxAge = MAX_AGE ) => {
	const json = window.localStorage.getItem( key );
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
	window.localStorage.removeItem( key );
};
