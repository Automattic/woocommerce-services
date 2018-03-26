/* Required by some wp-calypso components. */
/*global process */

const data = {
	env: ( process && process.env.NODE_ENV ) || 'development',
	wpcom_concierge_schedule_id: 1,
};

const d = ( key ) => {
	if ( key in data ) {
		return data[ key ];
	}
	throw new Error( 'config key `' + key + '` does not exist' );
};

d.isEnabled = () => true;

export default d;

export const isEnabled = () => true;
export const anyEnabled = () => true;
