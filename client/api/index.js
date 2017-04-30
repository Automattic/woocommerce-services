import * as request from './request';

export * as url from './url';

export const post = ( url, data ) => {
	return request.post( url, data )
		.catch( ( json ) => {
			if ( json.data.message ) {
				throw json.data.message;
			}

			throw JSON.stringify( json );
		} );
};

export const get = ( url ) => {

};
