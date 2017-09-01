/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import * as api from 'api';

export const SET_STATE = 'SET_STATE';

export const setState = ( state ) => ( {
	type: SET_STATE,
	value: state,
} );

export const fetchStripeSettings = () => ( dispatch ) => {
	console.log( 'fetch settings' );
	api.get( api.url.stripeSettings() )
		.catch( () => ( {} ) )
		.then( ( result ) => {
			console.log( result );
			dispatch( setState( Object.assign( { status: 'loaded' }, result ) ) );
		} );
};
