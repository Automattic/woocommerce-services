import saveForm from 'lib/save-form';
import noop from 'lodash/noop';
import fill from 'lodash/fill';
import flatten from 'lodash/flatten';
import omit from 'lodash/omit';
import printDocument from 'lib/utils/print-document';
import * as NoticeActions from 'state/notices/actions';
import getFormErrors from 'shipping-label/state/selectors/errors';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import validateAddress from './validate-address';
export const OPEN_PRINTING_FLOW = 'OPEN_PRINTING_FLOW';
export const EXIT_PRINTING_FLOW = 'EXIT_PRINTING_FLOW';
export const UPDATE_ADDRESS_VALUE = 'UPDATE_ADDRESS_VALUE';
export const ADDRESS_VALIDATION_IN_PROGRESS = 'ADDRESS_VALIDATION_IN_PROGRESS';
export const ADDRESS_VALIDATION_COMPLETED = 'ADDRESS_VALIDATION_COMPLETED';
export const PICK_NORMALIZED_ADDRESS = 'PICK_NORMALIZED_ADDRESS';
export const EDIT_ORIGINAL_ADDRESS = 'EDIT_ORIGINAL_ADDRESS';
export const UPDATE_PACKAGE_WEIGHT = 'UPDATE_PACKAGE_WEIGHT';
export const UPDATE_RATE = 'UPDATE_RATE';
export const PURCHASE_LABEL_REQUEST = 'PURCHASE_LABEL_REQUEST';
export const PURCHASE_LABEL_RESPONSE = 'PURCHASE_LABEL_RESPONSE';

export const openPrintingFlow = () => ( dispatch, getState, { storeOptions, addressValidationURL, nonce } ) => {
	const { origin, destination } = getState().shippingLabel.form;
	const errors = getFormErrors( getState(), storeOptions );
	if ( ! hasNonEmptyLeaves( errors.origin ) && ! origin.isValidated && ! origin.validationInProgress ) {
		validateAddress( dispatch, origin.values, 'origin', addressValidationURL, nonce ).catch( noop );
	}
	if ( ! hasNonEmptyLeaves( errors.destination ) && ! destination.isValidated && ! destination.validationInProgress ) {
		validateAddress( dispatch, destination.values, 'destination', addressValidationURL, nonce ).catch( noop );
	}
	dispatch( { type: OPEN_PRINTING_FLOW } );
};

export const exitPrintingFlow = () => {
	return { type: EXIT_PRINTING_FLOW };
};

export const updateAddressValue = ( group, name, value ) => {
	return {
		type: UPDATE_ADDRESS_VALUE,
		group,
		name,
		value,
	};
};

export const pickNormalizedAddress = ( group, pickNormalized ) => {
	return {
		type: PICK_NORMALIZED_ADDRESS,
		group,
		pickNormalized,
	};
};

export const editOriginalAddress = ( group ) => {
	return {
		type: EDIT_ORIGINAL_ADDRESS,
		group,
	};
};

export const updatePackageWeight = ( packageIndex, value ) => {
	return {
		type: UPDATE_PACKAGE_WEIGHT,
		packageIndex,
		value,
	};
};

export const updateRate = ( packageIndex, value ) => {
	return {
		type: UPDATE_RATE,
		packageIndex,
		value,
	};
};

export const purchaseLabel = () => ( dispatch, getState, { purchaseURL, addressValidationURL, nonce } ) => {
	let error = null;
	let response = null;
	const setError = ( err ) => error = err;
	const setSuccess = ( success, json ) => {
		if ( success ) {
			response = json;
		}
	};
	const setIsSaving = ( saving ) => {
		if ( saving ) {
			dispatch( { type: PURCHASE_LABEL_REQUEST } );
		} else {
			dispatch( { type: PURCHASE_LABEL_RESPONSE, response, error } );
			if ( error ) {
				dispatch( NoticeActions.errorNotice( error.toString() ) );
			} else {
				printDocument( 'data:application/pdf;base64,' + response[ 0 ].image ); // TODO: Figure out how to print multiple PDFs
				dispatch( exitPrintingFlow() );
			}
		}
	};

	let form = getState().shippingLabel.form;
	const addressesValidation = [];
	if ( ! form.origin.isValidated ) {
		addressesValidation.push( validateAddress( dispatch, form.origin.values, 'origin', addressValidationURL, nonce ) );
	}
	if ( ! form.destination.isValidated ) {
		addressesValidation.push( validateAddress( dispatch, form.destination.values, 'destination', addressValidationURL, nonce ) );
	}

	Promise.all( addressesValidation ).then( () => {
		form = getState().shippingLabel.form;
		const formData = {
			origin: form.origin.pickNormalized ? form.origin.normalized : form.origin.values,
			destination: form.destination.pickNormalized ? form.destination.normalized : form.destination.values,
			packages: form.packages.values.map( ( pckg, index ) => ( {
				...omit( pckg, [ 'items', 'id' ] ),
				service_id: form.rates.values[ index ],
				products: flatten( pckg.items.map( ( item ) => fill( new Array( item.quantity ), item.product_id ) ) ),
			} ) ),
		};

		saveForm( setIsSaving, setSuccess, noop, setError, purchaseURL, nonce, 'POST', formData );
	} ).catch( noop );
};
