import saveForm from 'lib/save-form';
import noop from 'lodash/noop';
import fill from 'lodash/fill';
import flatten from 'lodash/flatten';
import omit from 'lodash/omit';
import isEqual from 'lodash/isEqual';
import some from 'lodash/some';
import printDocument from 'lib/utils/print-document';
import * as NoticeActions from 'state/notices/actions';
import getFormErrors from 'shipping-label/state/selectors/errors';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import normalizeAddress from './normalize-address';
import getRates from './get-rates';
export const OPEN_PRINTING_FLOW = 'OPEN_PRINTING_FLOW';
export const EXIT_PRINTING_FLOW = 'EXIT_PRINTING_FLOW';
export const TOGGLE_STEP = 'TOGGLE_STEP';
export const UPDATE_ADDRESS_VALUE = 'UPDATE_ADDRESS_VALUE';
export const ADDRESS_NORMALIZATION_IN_PROGRESS = 'ADDRESS_NORMALIZATION_IN_PROGRESS';
export const ADDRESS_NORMALIZATION_COMPLETED = 'ADDRESS_NORMALIZATION_COMPLETED';
export const SELECT_NORMALIZED_ADDRESS = 'SELECT_NORMALIZED_ADDRESS';
export const EDIT_ADDRESS = 'EDIT_ADDRESS';
export const CONFIRM_ADDRESS_SUGGESTION = 'CONFIRM_ADDRESS_SUGGESTION';
export const UPDATE_PACKAGE_WEIGHT = 'UPDATE_PACKAGE_WEIGHT';
export const UPDATE_RATE = 'UPDATE_RATE';
export const PURCHASE_LABEL_REQUEST = 'PURCHASE_LABEL_REQUEST';
export const PURCHASE_LABEL_RESPONSE = 'PURCHASE_LABEL_RESPONSE';
export const RATES_RETRIEVAL_IN_PROGRESS = 'RATES_RETRIEVAL_IN_PROGRESS';
export const RATES_RETRIEVAL_COMPLETED = 'RATES_RETRIEVAL_COMPLETED';

const FORM_STEPS = [ 'origin', 'destination', 'packages', 'rates' ];

export const toggleStep = ( stepName ) => {
	return {
		type: TOGGLE_STEP,
		stepName,
	};
};

const waitForAllPromises = ( promises ) => {
	// Thin wrapper over Promise.all, that makes the Promise chain wait for all the promises
	// to be completed, even if one of them is rejected.
	return Promise.all( promises.map( ( p ) => p.catch( ( e ) => e ) ) );
};

const getNextErroneousStep = ( state, errors, currentStep ) => {
	const firstStepToCheck = FORM_STEPS[ FORM_STEPS.indexOf( currentStep ) + 1 ];
	const form = state.shippingLabel.form;
	switch ( firstStepToCheck ) {
		case 'origin':
			if ( ! form.origin.isNormalized || ! isEqual( form.origin.values, form.origin.normalized ) ) {
				return 'origin';
			}
		case 'destination':
			if ( ! form.destination.isNormalized || ! isEqual( form.destination.values, form.destination.normalized ) ) {
				return 'destination';
			}
		case 'packages':
			if ( hasNonEmptyLeaves( errors.packages ) ) {
				return 'packages';
			}
		case 'rates':
			if ( hasNonEmptyLeaves( errors.rates ) ) {
				return 'rates';
			}
	}
	return null;
};

const expandFirstErroneousStep = ( dispatch, getState, storeOptions, currentStep = null ) => {
	const step = getNextErroneousStep( getState(), getFormErrors( getState(), storeOptions ), currentStep );
	if ( step && ! getState().shippingLabel.form[ step ].expanded ) {
		dispatch( toggleStep( step ) );
	}
};

export const submitStep = ( stepName ) => ( dispatch, getState, { storeOptions } ) => {
	dispatch( {
		type: TOGGLE_STEP,
		stepName,
	} );
	expandFirstErroneousStep( dispatch, getState, storeOptions, stepName );
};

export const openPrintingFlow = () => ( dispatch, getState, { storeOptions, addressNormalizationURL, getRatesURL, nonce } ) => {
	let form = getState().shippingLabel.form;
	const { origin, destination, packages, rates } = form;
	let errors = getFormErrors( getState(), storeOptions );
	const promisesQueue = [];

	if ( ! hasNonEmptyLeaves( errors.origin ) && ! origin.isNormalized && ! origin.normalizationInProgress ) {
		promisesQueue.push( normalizeAddress( dispatch, origin.values, 'origin', addressNormalizationURL, nonce ) );
	}

	if ( ! hasNonEmptyLeaves( errors.destination ) && ! destination.isNormalized && ! destination.normalizationInProgress ) {
		promisesQueue.push( normalizeAddress( dispatch, destination.values, 'destination', addressNormalizationURL, nonce ) );
	}

	if (
		destination.isNormalized &&
		! destination.normalizationInProgress &&
		origin.isNormalized &&
		! origin.normalizationInProgress &&
		! rates.retrievalInProgress
	) {
		promisesQueue.push( getRates( dispatch, origin.values, destination.values, packages.values, getRatesURL, nonce ) );
	}

	waitForAllPromises( promisesQueue ).then( () => {
		// If the user already interacted with the form, don't change anything
		form = getState().shippingLabel.form;
		if ( some( FORM_STEPS.map( ( step ) => form[ step ].expanded ) ) ) {
			return;
		}
		expandFirstErroneousStep( dispatch, getState, storeOptions );
	} );
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

export const selectNormalizedAddress = ( group, selectNormalized ) => {
	return {
		type: SELECT_NORMALIZED_ADDRESS,
		group,
		selectNormalized,
	};
};

export const editAddress = ( group ) => {
	return {
		type: EDIT_ADDRESS,
		group,
	};
};

export const confirmAddressSuggestion = ( group ) => ( dispatch, getState, { storeOptions } ) => {
	dispatch( {
		type: CONFIRM_ADDRESS_SUGGESTION,
		group,
	} );
	expandFirstErroneousStep( dispatch, getState, storeOptions, group );
};

export const submitAddressForNormalization = ( group ) => ( dispatch, getState, { addressNormalizationURL, nonce, storeOptions } ) => {
	const handleResponse = () => {
		const { values, normalized, expanded } = getState().shippingLabel.form[ group ];
		if ( isEqual( values, normalized ) ) {
			if ( expanded ) {
				dispatch( toggleStep( group ) );
			}
			expandFirstErroneousStep( dispatch, getState, storeOptions, group );
		}
	};

	const state = getState().shippingLabel.form[ group ];
	if ( state.isNormalized && isEqual( state.values, state.normalized ) ) {
		handleResponse();
		return;
	}
	normalizeAddress( dispatch, getState().shippingLabel.form[ group ].values, group, addressNormalizationURL, nonce )
		.then( handleResponse )
		.catch( noop );
};

export const updatePackageWeight = ( packageIndex, value ) => {
	return {
		type: UPDATE_PACKAGE_WEIGHT,
		packageIndex,
		value,
	};
};

export const updateRate = ( packageId, value ) => {
	return {
		type: UPDATE_RATE,
		packageId,
		value,
	};
};

export const purchaseLabel = () => ( dispatch, getState, { purchaseURL, addressNormalizationURL, nonce } ) => {
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
	const addressNormalizationQueue = [];
	if ( ! form.origin.isNormalized ) {
		addressNormalizationQueue.push( normalizeAddress( dispatch, form.origin.values, 'origin', addressNormalizationURL, nonce ) );
	}
	if ( ! form.destination.isNormalized ) {
		addressNormalizationQueue.push( normalizeAddress( dispatch, form.destination.values, 'destination', addressNormalizationURL, nonce ) );
	}

	Promise.all( addressNormalizationQueue ).then( () => {
		form = getState().shippingLabel.form;
		const formData = {
			origin: form.origin.selectNormalized ? form.origin.normalized : form.origin.values,
			destination: form.destination.selectNormalized ? form.destination.normalized : form.destination.values,
			packages: form.packages.values.map( ( pckg, index ) => ( {
				...omit( pckg, [ 'items', 'id' ] ),
				service_id: form.rates.values[ index ],
				products: flatten( pckg.items.map( ( item ) => fill( new Array( item.quantity ), item.product_id ) ) ),
			} ) ),
		};

		saveForm( setIsSaving, setSuccess, noop, setError, purchaseURL, nonce, 'POST', formData );
	} ).catch( noop );
};

export const getLabelRates = () => ( dispatch, getState, { getRatesURL, nonce, storeOptions } ) => {
	const handleResponse = () => {
		expandFirstErroneousStep( dispatch, getState, storeOptions );
	};

	const formState = getState().shippingLabel.form;
	const {
		origin,
		destination,
		packages,
	} = formState;

	getRates( dispatch, origin.values, destination.values, packages.values, getRatesURL, nonce )
		.then( handleResponse )
		.catch( noop );
};
