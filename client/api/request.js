/**
 * External dependencies
 */
import { translate as __ } from 'i18n-calypso';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import parseJson from 'lib/utils/parse-json';
import addOnBeforeUnloadHandler from 'lib/before-unload';

let nonce;
export const setNonce = ( _nonce ) => nonce = _nonce;

let baseURL;
export const setBaseURL = ( _baseURL ) => baseURL = _baseURL;

const ASYNC_POLLING_INTERVAL = 3 * 1000;

const pendingPostRequests = new Set();

addOnBeforeUnloadHandler( () => {
	if ( ! window.persistState && pendingPostRequests.size ) {
		return __( 'Some requests haven\'t finished yet, please wait.' );
	}
} );

const _request = ( url, data, method ) => {
	const request = {
		method,
		credentials: 'same-origin',
		headers: {
			'X-WP-Nonce': nonce,
			'Content-Type': 'application/json',
		},
	};
	if ( data ) {
		request.body = JSON.stringify( data );
	}

	let requestId = null;
	// GET requests, by their very definition, can be interrupted without causing any unintended side effects
	if ( 'POST' === method ) {
		requestId = _.uniqueId();
		pendingPostRequests.add( requestId );
	}

	const poll = ( func ) => new Promise( ( resolve, reject ) => {
		setTimeout( () => func().then( resolve ).catch( reject ), ASYNC_POLLING_INTERVAL );
	} );

	function performRequest() {
		return fetch( baseURL + url, request ).then( ( response ) => {
			return parseJson( response ).then( ( json ) => {
				if ( json.async_token ) {
					request.body = JSON.stringify( { ...( data || {} ), async_token: json.async_token } );
					return poll( performRequest );
				}
				switch ( json.async_status ) {
					case 'IN_PROGRESS':
						return poll( performRequest );
					case 'NOT_FOUND':
						throw { code: 'async_token_not_found', message: __( 'The request failed, please try again.' ) };
				}

				if ( json.success ) {
					return json;
				}

				if ( 'rest_cookie_invalid_nonce' === json.code ) {
					window.persistState = true;
					alert( __( 'There was a problem saving your settings. Please try again after the page is reloaded.' ) );
					location.reload();
					return;
				}

				throw json;
			} );
		} );
	}

	return performRequest()
		.then( ( res ) => {
			pendingPostRequests.delete( requestId );
			return res;
		} )
		.catch( ( err ) => {
			pendingPostRequests.delete( requestId );
			throw err;
		} );
};

export const post = ( url, data ) => _request( url, data, 'POST' );

export const get = ( url ) => _request( url, null, 'GET' );
