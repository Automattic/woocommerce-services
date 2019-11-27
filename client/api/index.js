/**
 * Internal dependencies
 */
import request from './request';

import * as url from './url';
export { url };

const handleError = ( jsonError ) => {
	if ( jsonError.data.message ) {
		throw jsonError.data.message;
	}

	throw JSON.stringify( jsonError );
};

export const post = ( url, data ) => request().post( url, data ).catch( handleError );

export const get = ( url ) => request().get( url ).catch( handleError );

export const createGetUrlWithNonce = ( url, queryString ) => request().createGetUrlWithNonce( url, queryString );
