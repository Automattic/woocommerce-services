import saveForm from 'lib/save-form';
import noop from 'lodash/noop';
import {
	ADDRESS_VALIDATION_IN_PROGRESS,
	ADDRESS_VALIDATION_COMPLETED,
} from './actions';

export default ( dispatch, address, type, addressValidationURL, nonce ) => {
	dispatch( { type: ADDRESS_VALIDATION_IN_PROGRESS, group: type } );
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
					type: ADDRESS_VALIDATION_COMPLETED,
					group: type,
					normalized: ( response || {} ).normalized,
					error,
				} );
				if ( error ) {
					setTimeout( () => reject( error ), 0 );
				} else {
					setTimeout( resolve, 0 );
				}
			}
		};
		saveForm( setIsSaving, setSuccess, noop, setError, addressValidationURL, nonce, 'POST', { address, type } );
	} );
};
