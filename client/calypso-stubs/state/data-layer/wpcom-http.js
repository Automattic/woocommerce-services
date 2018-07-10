/**
 * Stubs https://github.com/Automattic/wp-calypso/blob/52cfffdb51c915be3ca0e40413b82bbbee452573/client/state/data-layer/wpcom-http/index.js
 * Only exports the functions necessary for http-handlers to work
 */

export const successMeta = ( data, headers ) => ( { meta: { dataLayer: { data, headers } } } );
export const failureMeta = ( error, headers ) => ( { meta: { dataLayer: { error, headers } } } );
