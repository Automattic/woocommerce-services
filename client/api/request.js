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
export const setNonce = ( _nonce ) => ( nonce = _nonce );
export const getNonce = () => nonce;

let baseURL;
export const setBaseURL = ( _baseURL ) => ( baseURL = _baseURL );
export const getBaseURL = () => baseURL;

const _request = ( url, data, method, namespace = '' ) => {
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

	if ( namespace && ! namespace.endsWith( '/' ) ) {
		namespace += '/';
	}
	if ( -1 !== baseURL.indexOf( '?' ) ) {
		url = url.replace( '?', '&' );
	}
	return fetch( baseURL + namespace + url, request ).then( ( response ) => {
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

const createGetUrlWithNonce = ( url, queryString ) => {
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

export default () => ( {
	post: ( url, data, namespace ) => _request( url, data, 'POST', namespace ),
	put: ( url, data, namespace ) => _request( url, data, 'PUT', namespace ),
	get: ( url, namespace ) => _request( url, null, 'GET', namespace ),
	del: ( url, namespace ) => _request( url, null, 'DELETE', namespace ),
	createGetUrlWithNonce,
} );
