/**
 * External dependencies
 */
import * as url from 'url';

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
	const query = url.parse( window.location.href, true ).query;

	if ( query.wcs_stripe_state && query.wcs_stripe_code ) {
		api.post( api.url.stripeOauth(), { state: query.wcs_stripe_state, code: query.wcs_stripe_code } )
			.catch( ( error ) => ( error ) )
			.then( ( result ) => {
				dispatch( setState( { message: JSON.stringify( result ) } ) );
			} );
		return;
	}

	api.post( api.url.stripeSettings(), { returnUrl: window.location.href } )
		.catch( ( error ) => ( { message: JSON.stringify( error ) } ) )
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
