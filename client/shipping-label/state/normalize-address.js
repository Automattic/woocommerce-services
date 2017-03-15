import saveForm from 'lib/save-form';
import _ from 'lodash';
import {
	ADDRESS_NORMALIZATION_IN_PROGRESS,
	ADDRESS_NORMALIZATION_COMPLETED,
} from './actions';

export default ( dispatch, address, type, addressNormalizationURL, nonce ) => {
	dispatch( { type: ADDRESS_NORMALIZATION_IN_PROGRESS, group: type } );
	return new Promise( ( resolve ) => {
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
					type: ADDRESS_NORMALIZATION_COMPLETED,
					group: type,
					normalized: ( response || {} ).normalized,
					isTrivialNormalization: ( response || {} ).is_trivial_normalization,
					error,
				} );
				if ( error ) {
					console.error( error );
				}
				setTimeout( () => resolve( ! error ), 0 );
			}
		};
		saveForm( setIsSaving, setSuccess, _.noop, setError, addressNormalizationURL, nonce, 'POST', { address, type } );
	} );
};
