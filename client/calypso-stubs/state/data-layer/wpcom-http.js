export const successMeta = ( data, headers ) => ( { meta: { dataLayer: { data, headers } } } );
export const failureMeta = ( error, headers ) => ( { meta: { dataLayer: { error, headers } } } );
