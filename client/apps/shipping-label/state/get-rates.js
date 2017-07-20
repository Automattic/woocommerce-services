/**
 * External dependencies
 */
import _ from 'lodash';

/**
 * Internal dependencies
 */
import saveForm from 'lib/save-form';
import {
	RATES_RETRIEVAL_IN_PROGRESS,
	SET_RATES,
	RATES_RETRIEVAL_COMPLETED,
	exitPrintingFlow,
} from './actions';

export default ( dispatch, origin, destination, packages, getRatesURL, nonce ) => {
	dispatch( { type: RATES_RETRIEVAL_IN_PROGRESS } );
	return new Promise( ( resolve, reject ) => {
		let error = null;
		const setError = ( err ) => error = err;
		const setSuccess = ( success, json ) => {
			if ( success ) {
				dispatch( {
					type: SET_RATES,
					rates: json.rates,
				} );
			}
		};
		const setIsSaving = ( saving ) => {
			if ( ! saving ) {
				dispatch( {
					type: RATES_RETRIEVAL_COMPLETED,
				} );
				if ( 'rest_cookie_invalid_nonce' === error ) {
					dispatch( exitPrintingFlow( true ) );
				} else if ( error ) {
					setTimeout( () => reject( error ), 0 );
				} else {
					setTimeout( resolve, 0 );
				}
			}
		};
		saveForm( setIsSaving, setSuccess, _.noop, setError, getRatesURL, nonce, 'POST', { origin, destination, packages } );
	} );
};
