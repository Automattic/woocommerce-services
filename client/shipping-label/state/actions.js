import saveForm from 'lib/save-form';
import _ from 'lodash';
import printDocument from 'lib/utils/print-document';
import * as NoticeActions from 'state/notices/actions';
import getFormErrors from 'shipping-label/state/selectors/errors';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import normalizeAddress from './normalize-address';
import getRates from './get-rates';
import { sprintf } from 'sprintf-js';
import { translate as __ } from 'lib/mixins/i18n';
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
export const OPEN_REFUND_DIALOG = 'OPEN_REFUND_DIALOG';
export const CLOSE_REFUND_DIALOG = 'CLOSE_REFUND_DIALOG';
export const LABEL_STATUS_RESPONSE = 'LABEL_STATUS_RESPONSE';
export const REFUND_REQUEST = 'REFUND_REQUEST';
export const REFUND_RESPONSE = 'REFUND_RESPONSE';
export const OPEN_REPRINT_DIALOG = 'OPEN_REPRINT_DIALOG';
export const CLOSE_REPRINT_DIALOG = 'CLOSE_REPRINT_DIALOG';
export const CONFIRM_REPRINT = 'CONFIRM_REPRINT';

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
			if ( ! form.origin.isNormalized || ! _.isEqual( form.origin.values, form.origin.normalized ) ) {
				return 'origin';
			}
		case 'destination':
			if ( ! form.destination.isNormalized || ! _.isEqual( form.destination.values, form.destination.normalized ) ) {
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

const getLabelRates = ( dispatch, getState, handleResponse, { getRatesURL, nonce } ) => {
	const formState = getState().shippingLabel.form;
	const {
		origin,
		destination,
		packages,
	} = formState;

	return getRates( dispatch, origin.values, destination.values, packages.values, getRatesURL, nonce )
		.then( handleResponse )
		.catch( ( error ) => {
			console.error( error );
			dispatch( NoticeActions.errorNotice( error.toString() ) );
		} );
};

export const openPrintingFlow = () => ( dispatch, getState, { storeOptions, addressNormalizationURL, getRatesURL, nonce } ) => {
	let form = getState().shippingLabel.form;
	const { origin, destination } = form;
	let errors = getFormErrors( getState(), storeOptions );
	const promisesQueue = [];

	if ( ! hasNonEmptyLeaves( errors.origin ) && ! origin.isNormalized && ! origin.normalizationInProgress ) {
		promisesQueue.push( normalizeAddress( dispatch, origin.values, 'origin', addressNormalizationURL, nonce ) );
	}

	if ( ! hasNonEmptyLeaves( errors.destination ) && ! destination.isNormalized && ! destination.normalizationInProgress ) {
		promisesQueue.push( normalizeAddress( dispatch, destination.values, 'destination', addressNormalizationURL, nonce ) );
	}

	waitForAllPromises( promisesQueue ).then( () => {
		form = getState().shippingLabel.form;

		const expandStepAfterAction = () => {
			expandFirstErroneousStep( dispatch, getState, storeOptions );
		};

		// If origin and destination are normalized, get rates
		if (
			form.origin.isNormalized &&
			_.isEqual( form.origin.values, form.origin.normalized ) &&
			form.destination.isNormalized &&
			_.isEqual( form.destination.values, form.destination.normalized ) &&
			_.isEmpty( form.rates.available )
			// TODO: make sure packages are valid as well
		) {
			return getLabelRates( dispatch, getState, expandStepAfterAction, { getRatesURL, nonce } );
		}

		// Otherwise, just expand the next errant step unless the
		// user already interacted with the form
		if ( _.some( FORM_STEPS.map( ( step ) => form[ step ].expanded ) ) ) {
			return;
		}

		expandStepAfterAction();
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

export const confirmAddressSuggestion = ( group ) => ( dispatch, getState, { storeOptions, getRatesURL, nonce } ) => {
	dispatch( {
		type: CONFIRM_ADDRESS_SUGGESTION,
		group,
	} );

	const handleResponse = () => {
		expandFirstErroneousStep( dispatch, getState, storeOptions, group );
	};

	getLabelRates( dispatch, getState, handleResponse, { getRatesURL, nonce } );
};

export const submitAddressForNormalization = ( group ) => ( dispatch, getState, { addressNormalizationURL, getRatesURL, nonce, storeOptions } ) => {
	const handleNormalizeResponse = ( success ) => {
		if ( ! success ) {
			return;
		}
		const { values, normalized, expanded } = getState().shippingLabel.form[ group ];

		if ( _.isEqual( values, normalized ) ) {
			if ( expanded ) {
				dispatch( toggleStep( group ) );
			}

			const handleRatesResponse = () => {
				expandFirstErroneousStep( dispatch, getState, storeOptions, group );
			};

			getLabelRates( dispatch, getState, handleRatesResponse, { getRatesURL, nonce } );
		}
	};

	const state = getState().shippingLabel.form[ group ];
	if ( state.isNormalized && _.isEqual( state.values, state.normalized ) ) {
		handleNormalizeResponse();
		return;
	}
	normalizeAddress( dispatch, getState().shippingLabel.form[ group ].values, group, addressNormalizationURL, nonce )
		.then( handleNormalizeResponse )
		.catch( ( error ) => {
			console.error( error );
			dispatch( NoticeActions.errorNotice( error.toString() ) );
		} );
};

export const updatePackageWeight = ( packageId, value ) => {
	return {
		type: UPDATE_PACKAGE_WEIGHT,
		packageId,
		value,
	};
};

export const confirmPackages = () => ( dispatch, getState, { getRatesURL, storeOptions, nonce } ) => {
	dispatch( toggleStep( 'packages' ) );

	const handleResponse = () => {
		expandFirstErroneousStep( dispatch, getState, storeOptions, 'packages' );
	};

	getLabelRates( dispatch, getState, handleResponse, { getRatesURL, nonce } );
};

export const updateRate = ( packageId, value ) => {
	return {
		type: UPDATE_RATE,
		packageId,
		value,
	};
};

export const purchaseLabel = () => ( dispatch, getState, { purchaseURL, addressNormalizationURL, labelImageURL, nonce } ) => {
	let error = null;
	let response = null;
	const setError = ( err ) => error = err;
	const setSuccess = ( success, json ) => {
		if ( success ) {
			response = json.labels;
		}
	};
	const setIsSaving = ( saving ) => {
		if ( saving ) {
			dispatch( { type: PURCHASE_LABEL_REQUEST } );
		} else {
			dispatch( { type: PURCHASE_LABEL_RESPONSE, response, error } );
			if ( error ) {
				console.error( error );
				dispatch( NoticeActions.errorNotice( error.toString() ) );
			} else {
				// TODO: Figure out how to print multiple labels
				printDocument( sprintf( labelImageURL, response[ 0 ].label_id ), nonce )
					.then( () => dispatch( exitPrintingFlow() ) )
					.catch( ( err ) => {
						console.error( err );
						dispatch( NoticeActions.errorNotice( err.toString() ) );
					} );
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

	Promise.all( addressNormalizationQueue ).then( ( normalizationResults ) => {
		if ( ! _.every( normalizationResults ) ) {
			return;
		}
		form = getState().shippingLabel.form;
		const formData = {
			origin: form.origin.selectNormalized ? form.origin.normalized : form.origin.values,
			destination: form.destination.selectNormalized ? form.destination.normalized : form.destination.values,
			packages: _.map( form.packages.values, ( pckg, pckgId ) => ( {
				..._.omit( pckg, [ 'items', 'id' ] ),
				service_id: form.rates.values[ pckgId ],
				service_name: _.find( form.rates.available[ pckgId ].rates, { service_id: form.rates.values[ pckgId ] } ).title,
				products: _.flatten( pckg.items.map( ( item ) => _.fill( new Array( item.quantity ), item.product_id ) ) ),
			} ) ),
			order_id: form.orderId,
		};

		saveForm( setIsSaving, setSuccess, _.noop, setError, purchaseURL, nonce, 'POST', formData );
	} ).catch( ( err ) => {
		console.error( err );
		dispatch( NoticeActions.errorNotice( err.toString() ) );
	} );
};

export const openRefundDialog = ( labelId ) => {
	return {
		type: OPEN_REFUND_DIALOG,
		labelId,
	};
};

export const fetchLabelsStatus = () => ( dispatch, getState, { labelStatusURL, nonce } ) => {
	getState().shippingLabel.labels.forEach( ( label ) => {
		const labelId = label.label_id;
		let error = null;
		let response = null;
		const setError = ( err ) => error = err;
		const setSuccess = ( success, json ) => {
			if ( success ) {
				response = json.status;
			}
		};
		const setIsSaving = ( saving ) => {
			if ( ! saving ) {
				dispatch( { type: LABEL_STATUS_RESPONSE, labelId, response, error } );
				if ( error ) {
					dispatch( NoticeActions.errorNotice( error.toString() ) );
				}
			}
		};

		saveForm( setIsSaving, setSuccess, _.noop, setError, sprintf( labelStatusURL, labelId ), nonce, 'GET' );
	} );
};

export const closeRefundDialog = () => {
	return { type: CLOSE_REFUND_DIALOG };
};

export const confirmRefund = () => ( dispatch, getState, { labelRefundURL, nonce } ) => {
	const labelId = getState().shippingLabel.refundDialog.labelId;
	let error = null;
	let response = null;
	const setError = ( err ) => error = err;
	const setSuccess = ( success, json ) => {
		if ( success ) {
			response = json.label;
		}
	};
	const setIsSaving = ( saving ) => {
		if ( saving ) {
			dispatch( { type: REFUND_REQUEST } );
		} else {
			dispatch( { type: REFUND_RESPONSE, response, error } );
			if ( error ) {
				dispatch( NoticeActions.errorNotice( error.toString() ) );
			} else {
				dispatch( NoticeActions.successNotice( __( 'The refund request has been sent correctly' ), { duration: 5000 } ) );
			}
		}
	};

	saveForm( setIsSaving, setSuccess, _.noop, setError, sprintf( labelRefundURL, labelId ), nonce, 'POST' );
};

export const openReprintDialog = ( labelId ) => {
	return { type: OPEN_REPRINT_DIALOG, labelId };
};

export const closeReprintDialog = () => {
	return { type: CLOSE_REPRINT_DIALOG };
};

export const confirmReprint = () => ( dispatch, getState, { labelImageURL, nonce } ) => {
	dispatch( { type: CONFIRM_REPRINT } );
	const labelId = getState().shippingLabel.reprintDialog.labelId;
	printDocument( sprintf( labelImageURL, labelId ), nonce )
		.then( () => dispatch( closeReprintDialog() ) )
		.catch( ( error ) => dispatch( NoticeActions.errorNotice( error.toString() ) ) );
};
