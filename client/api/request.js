/**
 * External dependencies
 */
import { translate as __ } from 'i18n-calypso';
import { startsWith, endsWith } from 'lodash';

/**
 * Internal dependencies
 */
import parseJson from 'lib/utils/parse-json';

let nonce;
export const setNonce = ( _nonce ) => nonce = _nonce;

let baseURL;
export const setBaseURL = ( _baseURL ) => baseURL = _baseURL;

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

	return fetch( baseURL + url, request ).then( ( response ) => {
		return parseJson( response ).then( ( json ) => {
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
};

export const post = ( url, data ) => _request( url, data, 'POST' );

export const get = ( url ) => _request( url, null, 'GET' );

export const createGetUrlWithNonce = ( url, queryString ) => {
	if ( queryString ) {
		if ( startsWith( queryString, '?' ) ) {
			queryString = queryString.substring( 1 );
		}

		if ( ! startsWith( queryString, '&' ) ) {
			queryString = '&' + queryString;
		}
	}

	const queryStart = endsWith( baseURL, 'index.php?rest_route=/' ) ? '&' : '?';

	return `${ baseURL }${ url }${ queryStart }_wpnonce=${ nonce }${ queryString }`;
};
