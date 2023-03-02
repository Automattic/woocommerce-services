/**
 * Internal dependencies
 */
import request from './request';

export * as url from './url';

const handleError = ( jsonError ) => {
	if ( jsonError.data.message ) {
		throw jsonError.data.message;
	}

	throw JSON.stringify( jsonError );
};

export const post = ( url, data ) => request().post( url, data ).catch( handleError );

export const get = ( url ) => request().get( url ).catch( handleError );

export const put = ( url, data ) => request().put( url, data ).catch( handleError );

export const createGetUrlWithNonce = ( url, queryString ) => request().createGetUrlWithNonce( url, queryString );
