import saveForm from 'lib/save-form';
import _ from 'lodash';
import {
	RATES_RETRIEVAL_IN_PROGRESS,
	RATES_RETRIEVAL_COMPLETED,
} from './actions';

export default ( dispatch, orderId, origin, destination, packages, getRatesURL, nonce ) => {
	dispatch( { type: RATES_RETRIEVAL_IN_PROGRESS } );
	return new Promise( ( resolve, reject ) => {
		let error = null;
		let response = null;
		const setError = ( err ) => error = err;
		const setSuccess = ( success, json ) => {
			if ( success ) {
				response = json;
			}
		};
		const setIsSaving = ( saving ) => {
			if ( ! saving ) {
				dispatch( {
					type: RATES_RETRIEVAL_COMPLETED,
					rates: ( response || {} ).rates,
					error,
				} );
				if ( error ) {
					setTimeout( () => reject( error ), 0 );
				} else {
					setTimeout( resolve, 0 );
				}
			}
		};
		saveForm( setIsSaving, setSuccess, _.noop, setError, getRatesURL, nonce, 'POST', { orderId, origin, destination, packages } );
	} );
};
