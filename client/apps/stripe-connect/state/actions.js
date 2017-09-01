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
	api.get( api.url.stripeAccount() )
		.catch( () => ( {} ) )
		.then( ( result ) => {
			dispatch( setState( Object.assign( { status: 'loaded' }, result ) ) );
		} );
};

export const setEmail = ( email ) => ( dispatch ) => {
	dispatch( setState( { email } ) );
};

export const setCountry = ( country ) => ( dispatch ) => {
	dispatch( setState( { country } ) );
};

export const createAccount = () => ( dispatch, getState ) => {
	const { email, country } = getState();
	api.post( api.url.stripeAccount(), { email, country } )
		.then( ( result ) => {
			return { message: `Account created for ${ result.account_id }` };
		} )
		.catch( ( result ) => {
			return { message: JSON.stringify( result ) };
		} )
		.then( ( result ) => {
			dispatch( setState( result ) );
		} );
};

export const startOauth = () => ( dispatch, getState ) => {
	window.location.href = getState().oauthUrl;
};
