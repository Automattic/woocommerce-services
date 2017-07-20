/**
 * External dependencies
 */
import _ from 'lodash';

/**
 * Internal dependencies
 */
import saveForm from 'lib/save-form';
import {
	ADDRESS_NORMALIZATION_IN_PROGRESS,
	SET_NORMALIZED_ADDRESS,
	ADDRESS_NORMALIZATION_COMPLETED,
	exitPrintingFlow,
} from './actions';

export default ( dispatch, address, group, addressNormalizationURL, nonce ) => {
	dispatch( { type: ADDRESS_NORMALIZATION_IN_PROGRESS, group } );
	return new Promise( ( resolve ) => {
		let error = null;
		const setError = ( err ) => error = err;
		const setSuccess = ( success, json ) => {
			if ( success ) {
				dispatch( {
					type: SET_NORMALIZED_ADDRESS,
					group,
					normalized: json.normalized,
					isTrivialNormalization: json.is_trivial_normalization,
				} );
			}
		};
		const setIsSaving = ( saving ) => {
			if ( ! saving ) {
				dispatch( {
					type: ADDRESS_NORMALIZATION_COMPLETED,
					group,
					error,
				} );
				if ( error ) {
					if ( 'rest_cookie_invalid_nonce' === error ) {
						dispatch( exitPrintingFlow( true ) );
					}

					console.error( error ); // eslint-disable-line no-console
				}
				setTimeout( () => resolve( ! error ), 0 );
			}
		};
		saveForm( setIsSaving, setSuccess, _.noop, setError, addressNormalizationURL, nonce, 'POST', { address, type: group } );
	} );
};
