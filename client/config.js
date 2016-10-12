/* Required by some wp-calypso components. */
/*global process */

const data = {
	env: ( process && process.env.NODE_ENV ) || 'development',
};

export default ( key ) => {
	if ( key in data ) {
		return data[ key ];
	}
	throw new Error( 'config key `' + key + '` does not exist' );
};

export const isEnabled = () => false;
export const anyEnabled = () => false;
