const makeConfig = ( data ) => (
	( key ) => {
		if ( key in data ) {
			return data[ key ];
		}
		throw new Error( 'config key `' + key + '` does not exist' );
	}
);

export default makeConfig;
