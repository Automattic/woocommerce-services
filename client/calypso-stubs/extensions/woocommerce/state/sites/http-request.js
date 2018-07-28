/** @format */

/**
 * This stub replaces /client/extensions/woocommerce/state/sites/http-request.js
 *
 * It's main purpose is to replace WPCOM HTTP actions with vanilla
 * HTTP actions that don't use the Jetpack proxy.
 */

/**
 * Internal dependencies
 */
import { http } from 'state/http/actions';
import { getNonce, getBaseURL } from 'api/request';
import { extendAction } from 'state/utils';

/**
 * Returns a proper HTTP_REQUEST action (http data layer) for dispatching requests
 * in data-layer handlers.
 * The resulting data will be in the form of `{ API data }`
 * @param {String} method HTTP Request Method
 * @param {String} path The WC API path to make a request to (after /wc/v#)
 * @param {Number} siteId Site ID to make the request to
 * @param {Object} body HTTP Body for POST and PUT Requests
 * @param {Object} action The original requesting action
 * @param {String} namespace Namespace to be pre-pended to path (e.g. /wc/v2)
 * @return {Object} HTTP_REQUEST Action
 */
const _request = ( method, path, siteId, body, action, namespace ) => {
	const baseURL = getBaseURL();

	if ( namespace && ! namespace.endsWith( '/' ) ) {
		namespace += '/';
	}
	if ( namespace && namespace.startsWith( '/' ) ) {
		// baseURL ends in a "/", so if namespace starts with another it must be removed
		namespace = namespace.substr( 1 );
	}

	if ( -1 !== baseURL.indexOf( '?' ) ) {
		path = path.replace( '?', '&' );
	}

	// Extend the HTTP_REQUEST action with a flag that the request
	// was altered to hit the local WC API rather than the Jetpack proxy.
	return extendAction( http(
		{
			url: baseURL + namespace + path,
			method,
			headers: [
				[ 'X-WP-Nonce', getNonce() ],
				[ 'Content-Type', 'application/json' ],
			],
			body: body && JSON.stringify( body ),
			withCredentials: true,
		},
		action
	), { meta: { localApiRequest: true } } );
};

/**
 * Prepares a request action that will return the body and headers.
 * The resulting data will be in the form of `{ data: { status: <code>, body: { API data }, headers: { API response headers } } }`
 * @param {String} method HTTP Request Method
 * @param {String} path The WC API path to make a request to (after /wc/v#)
 * @param {Number} siteId Site ID to make the request to
 * @param {Object} body HTTP Body for POST and PUT Requests
 * @param {Object} action The original requesting action
 * @param {String} namespace Namespace to be pre-pended to path (e.g. /wc/v2)
 * @return {Object} WPCOM_HTTP_REQUEST Action
 */
const _requestWithHeaders = ( method, path, siteId, body, action, namespace ) => {
	return _request( method, path + '&_envelope', siteId, body, action, namespace );
};

/**
 * Provides a wrapper over the http data-layer, made specifically for making requests to
 * WooCommerce endpoints without repeating things like /wc/v3.
 * @param {Number} siteId Site ID to make the request to
 * @param {Object} action The original requesting action
 * @param {String} namespace Namespace to be pre-pended to path. Defaults to wc/v2
 * @return {Object} An object with the properties "get", "post", "put" and "del", which are functions to
 * make an HTTP GET, POST, PUT and DELETE request, respectively.
 */
export default ( siteId, action, namespace = 'wc/v1' ) => ( {
	/**
	 * Sends a GET request to the API
	 * @param {String} path REST path to hit, omitting the "blog.url/wp-json/wc/v#/" prefix
	 * @return {Object} WPCOM_HTTP_REQUEST Action with `data = { API data }`
	 */
	get: path => _request( 'GET', path, siteId, null, action, namespace ),

	/**
	 * Sends a GET request to the API that will return with headers
	 * @param {String} path REST path to hit, omitting the "blog.url/wp-json-/wc/v#/" prefix
	 * @return {Object} WPCOM_HTTP_REQUEST Action with `data = { status: <code>, body: { API data }, headers: { API response headers } }`
	 */
	getWithHeaders: path => _requestWithHeaders( 'GET', path, siteId, null, action, namespace ),

	/**
	 * Sends a POST request to the API
	 * @param {String} path REST path to hit, omitting the "blog.url/wp-json/wc/v#/" prefix
	 * @param {Object} body Payload to send
	 * @return {Object} WPCOM_HTTP_REQUEST Action
	 */
	post: ( path, body ) => _request( 'POST', path, siteId, body || {}, action, namespace ),

	/**
	 * Sends a PUT request to the API.
	 * Note that the underlying request will be a POST, with an special URL parameter to
	 * be interpreted by the WPCOM server as a PUT request.
	 * @param {String} path REST path to hit, omitting the "blog.url/wp-json/wc/v#/" prefix
	 * @param {Object} body Payload to send
	 * @return {Object} WPCOM_HTTP_REQUEST Action
	 */
	put: ( path, body ) => _request( 'PUT', path, siteId, body || {}, action, namespace ),

	/**
	 * Sends a DELETE request to the API.
	 * Note that the underlying request will be a POST, with an special URL parameter to
	 * be interpreted by the WPCOM server as a DELETE request.
	 * @param {String} path REST path to hit, omitting the "blog.url/wp-json/wc/v#/" prefix
	 * @return {Object} WPCOM_HTTP_REQUEST Action
	 */
	del: path => _request( 'DELETE', path, siteId, null, action, namespace ),
} );
