/* eslint-disable no-console */
/**
 * External dependencies
 */
import { sprintf } from 'sprintf-js';
import { translate as __ } from 'i18n-calypso';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import saveForm from 'lib/save-form';
import printDocument from 'lib/utils/print-document';
import getPDFSupport from 'lib/utils/pdf-support';
import * as NoticeActions from 'state/notices/actions';
import getFormErrors from 'shipping-label/state/selectors/errors';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import normalizeAddress from './normalize-address';
import getRates from './get-rates';
import { getPrintURL } from 'lib/pdf-label-utils';

export const OPEN_PRINTING_FLOW = 'OPEN_PRINTING_FLOW';
export const EXIT_PRINTING_FLOW = 'EXIT_PRINTING_FLOW';
export const TOGGLE_STEP = 'TOGGLE_STEP';
export const UPDATE_ADDRESS_VALUE = 'UPDATE_ADDRESS_VALUE';
export const REMOVE_IGNORE_VALIDATION = 'REMOVE_IGNORE_VALIDATION';
export const ADDRESS_NORMALIZATION_IN_PROGRESS = 'ADDRESS_NORMALIZATION_IN_PROGRESS';
export const SET_NORMALIZED_ADDRESS = 'SET_NORMALIZED_ADDRESS';
export const ADDRESS_NORMALIZATION_COMPLETED = 'ADDRESS_NORMALIZATION_COMPLETED';
export const SELECT_NORMALIZED_ADDRESS = 'SELECT_NORMALIZED_ADDRESS';
export const EDIT_ADDRESS = 'EDIT_ADDRESS';
export const CONFIRM_ADDRESS_SUGGESTION = 'CONFIRM_ADDRESS_SUGGESTION';
export const UPDATE_PACKAGE_WEIGHT = 'UPDATE_PACKAGE_WEIGHT';
export const UPDATE_RATE = 'UPDATE_RATE';
export const UPDATE_PAPER_SIZE = 'UPDATE_PAPER_SIZE';
export const PURCHASE_LABEL_REQUEST = 'PURCHASE_LABEL_REQUEST';
export const PURCHASE_LABEL_RESPONSE = 'PURCHASE_LABEL_RESPONSE';
export const SHOW_PRINT_CONFIRMATION = 'SHOW_PRINT_CONFIRMATION';
export const RATES_RETRIEVAL_IN_PROGRESS = 'RATES_RETRIEVAL_IN_PROGRESS';
export const SET_RATES = 'SET_RATES';
export const RATES_RETRIEVAL_COMPLETED = 'RATES_RETRIEVAL_COMPLETED';
export const CLEAR_AVAILABLE_RATES = 'CLEAR_AVAILABLE_RATES';
export const OPEN_REFUND_DIALOG = 'OPEN_REFUND_DIALOG';
export const CLOSE_REFUND_DIALOG = 'CLOSE_REFUND_DIALOG';
export const LABEL_STATUS_RESPONSE = 'LABEL_STATUS_RESPONSE';
export const REFUND_REQUEST = 'REFUND_REQUEST';
export const REFUND_RESPONSE = 'REFUND_RESPONSE';
export const OPEN_REPRINT_DIALOG = 'OPEN_REPRINT_DIALOG';
export const CLOSE_REPRINT_DIALOG = 'CLOSE_REPRINT_DIALOG';
export const CONFIRM_REPRINT = 'CONFIRM_REPRINT';
export const OPEN_PACKAGE = 'OPEN_PACKAGE';
export const OPEN_ITEM_MOVE = 'OPEN_ITEM_MOVE';
export const MOVE_ITEM = 'MOVE_ITEM';
export const CLOSE_ITEM_MOVE = 'CLOSE_ITEM_MOVE';
export const SET_TARGET_PACKAGE = 'SET_TARGET_PACKAGE';
export const ADD_PACKAGE = 'ADD_PACKAGE';
export const REMOVE_PACKAGE = 'REMOVE_PACKAGE';
export const SET_PACKAGE_TYPE = 'SET_PACKAGE_TYPE';
export const SAVE_PACKAGES = 'SAVE_PACKAGES';
export const OPEN_ADD_ITEM = 'OPEN_ADD_ITEM';
export const CLOSE_ADD_ITEM = 'CLOSE_ADD_ITEM';
export const SET_ADDED_ITEM = 'SET_ADDED_ITEM';

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
			if ( hasNonEmptyLeaves( errors.packages ) || ! form.packages.all || ! Object.keys( form.packages.all ).length ) {
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

const convertToApiPackage = ( pckg ) => {
	return _.pick( pckg, [ 'id', 'box_id', 'service_id', 'length', 'width', 'height', 'weight' ] );
};

export const clearAvailableRates = () => {
	return { type: CLEAR_AVAILABLE_RATES };
};

const getLabelRates = ( dispatch, getState, handleResponse, { getRatesURL, nonce } ) => {
	const formState = getState().shippingLabel.form;
	const {
		origin,
		destination,
		packages,
	} = formState;

	return getRates( dispatch, origin.values, destination.values, _.map( packages.selected, convertToApiPackage ), getRatesURL, nonce )
		.then( handleResponse )
		.catch( ( error ) => {
			console.error( error );
			dispatch( NoticeActions.errorNotice( error.toString() ) );
		} );
};

export const openPrintingFlow = () => (
	dispatch,
	getState,
	context,
	getErrors = getFormErrors
) => {
	const { storeOptions, addressNormalizationURL, getRatesURL, nonce } = context;
	let form = getState().shippingLabel.form;
	const { origin, destination } = form;
	const errors = getErrors( getState(), storeOptions );
	const promisesQueue = [];

	if ( ! origin.ignoreValidation &&
		! hasNonEmptyLeaves( errors.origin ) &&
		! origin.isNormalized &&
		! origin.normalizationInProgress ) {
		promisesQueue.push( normalizeAddress( dispatch, origin.values, 'origin', addressNormalizationURL, nonce ) );
	}

	if ( origin.ignoreValidation || hasNonEmptyLeaves( errors.origin ) ) {
		dispatch( toggleStep( 'origin' ) );
	}

	if ( ! destination.ignoreValidation &&
		! hasNonEmptyLeaves( errors.destination ) &&
		! destination.isNormalized &&
		! destination.normalizationInProgress ) {
		promisesQueue.push( normalizeAddress( dispatch, destination.values, 'destination', addressNormalizationURL, nonce ) );
	}

	if ( destination.ignoreValidation || hasNonEmptyLeaves( errors.destination ) ) {
		dispatch( toggleStep( 'destination' ) );
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
			_.isEmpty( form.rates.available ) &&
			form.packages.all && Object.keys( form.packages.all ).length &&
			! hasNonEmptyLeaves( errors.packages )
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

export const exitPrintingFlow = ( force ) => ( dispatch, getState ) => {
	dispatch( { type: EXIT_PRINTING_FLOW, force } );

	const form = getState().shippingLabel.form;

	if ( form.needsPrintConfirmation ) {
		dispatch( clearAvailableRates() );
	}
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

export const removeIgnoreValidation = ( group ) => {
	return {
		type: REMOVE_IGNORE_VALIDATION,
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

	const state = getState();

	const errors = getFormErrors( state, storeOptions );

	// If all prerequisite steps are error free, fetch new rates
	if (
		hasNonEmptyLeaves( errors.origin ) ||
		hasNonEmptyLeaves( errors.destination ) ||
		hasNonEmptyLeaves( errors.packages )
	) {
		return;
	}

	getLabelRates( dispatch, getState, handleResponse, { getRatesURL, nonce } );
};

export const submitAddressForNormalization = ( group ) => ( dispatch, getState, context ) => {
	const { addressNormalizationURL, getRatesURL, nonce, storeOptions } = context;

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

			const errors = getFormErrors( getState(), storeOptions );

			// If all prerequisite steps are error free, fetch new rates
			if (
				hasNonEmptyLeaves( errors.origin ) ||
				hasNonEmptyLeaves( errors.destination ) ||
				hasNonEmptyLeaves( errors.packages )
			) {
				return;
			}

			getLabelRates( dispatch, getState, handleRatesResponse, { getRatesURL, nonce } );
		}
	};

	let state = getState().shippingLabel.form[ group ];
	if ( state.ignoreValidation ) {
		dispatch( removeIgnoreValidation( group ) );
		const errors = getFormErrors( getState(), storeOptions );
		if ( hasNonEmptyLeaves( errors[ group ] ) ) {
			return;
		}
		state = getState().shippingLabel.form[ group ];
	}
	if ( state.isNormalized && _.isEqual( state.values, state.normalized ) ) {
		handleNormalizeResponse( true );
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

export const openPackage = ( openedPackageId ) => {
	return {
		type: OPEN_PACKAGE,
		openedPackageId,
	};
};

export const openItemMove = ( movedItemIndex ) => {
	return {
		type: OPEN_ITEM_MOVE,
		movedItemIndex,
	};
};

export const moveItem = ( originPackageId, movedItemIndex, targetPackageId ) => {
	return {
		type: MOVE_ITEM,
		originPackageId,
		movedItemIndex,
		targetPackageId,
	};
};

export const closeItemMove = () => {
	return {
		type: CLOSE_ITEM_MOVE,
	};
};

export const setTargetPackage = ( targetPackageId ) => {
	return {
		type: SET_TARGET_PACKAGE,
		targetPackageId,
	};
};

export const openAddItem = () => {
	return {
		type: OPEN_ADD_ITEM,
	};
};

export const closeAddItem = () => {
	return {
		type: CLOSE_ADD_ITEM,
	};
};

export const setAddedItem = ( sourcePackageId, movedItemIndex ) => {
	return {
		type: SET_ADDED_ITEM,
		sourcePackageId,
		movedItemIndex,
	};
};

export const addPackage = () => {
	return {
		type: ADD_PACKAGE,
	};
};

export const removePackage = ( packageId ) => {
	return {
		type: REMOVE_PACKAGE,
		packageId,
	};
};

export const setPackageType = ( packageId, boxTypeId ) => {
	return {
		type: SET_PACKAGE_TYPE,
		packageId,
		boxTypeId,
	};
};

export const savePackages = () => {
	return {
		type: SAVE_PACKAGES,
	};
};

export const removeItem = ( packageId, itemIndex ) => ( dispatch, getState ) => {
	dispatch( moveItem( packageId, itemIndex, '' ) );

	const selected = getState().shippingLabel.form.packages.selected;
	if ( selected[ packageId ] && 'individual' === selected[ packageId ].box_id ) {
		dispatch( removePackage( packageId ) );
		dispatch( openPackage( '' ) );
	}
};

export const confirmPackages = () => ( dispatch, getState, context ) => {
	const { getRatesURL, storeOptions, nonce } = context;
	dispatch( toggleStep( 'packages' ) );
	dispatch( savePackages() );

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

export const updatePaperSize = ( value ) => {
	return {
		type: UPDATE_PAPER_SIZE,
		value,
	};
};

export const purchaseLabel = () => ( dispatch, getState, context ) => {
	const { purchaseURL, getRatesURL, addressNormalizationURL, nonce } = context;
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
			if ( 'rest_cookie_invalid_nonce' === error ) {
				dispatch( exitPrintingFlow( true ) );
			} else if ( error ) {
				console.error( error );
				dispatch( NoticeActions.errorNotice( error.toString() ) );
				//re-request the rates on failure to avoid attempting repurchase of the same shipment id
				dispatch( clearAvailableRates() );
				getLabelRates( dispatch, getState, _.noop, { getRatesURL, nonce } );
			} else {
				const labelsToPrint = response.map( ( label, index ) => ( {
					caption: __( 'PACKAGE %(num)d (OF %(total)d)', {
						args: {
							num: index + 1,
							total: response.length,
						},
					} ),
					labelId: label.label_id,
				} ) );
				const state = getState().shippingLabel;
				const printUrl = getPrintURL( state.paperSize, labelsToPrint, context );
				if ( 'addon' === getPDFSupport() ) {
					// If the browser has a PDF "addon", we need another user click to trigger opening it in a new tab
					dispatch( { type: SHOW_PRINT_CONFIRMATION, printUrl } );
				} else {
					printDocument( printUrl )
						.then( () => {
							const noticeText = __(
								'Your shipping label was purchased successfully',
								'Your %(count)d shipping labels were purchased successfully',
								{
									count: response.length,
									args: { count: response.length },
								}
							);
							dispatch( NoticeActions.successNotice( noticeText ) );
							dispatch( exitPrintingFlow( true ) );
							dispatch( clearAvailableRates() );
						} )
						.catch( ( err ) => {
							console.error( err );
							dispatch( NoticeActions.errorNotice( err.toString() ) );
						} );
				}
			}
		}
	};

	let form = getState().shippingLabel.form;
	const addressNormalizationQueue = [];
	if ( ! form.origin.isNormalized ) {
		const task = normalizeAddress( dispatch, form.origin.values, 'origin', addressNormalizationURL, nonce );
		addressNormalizationQueue.push( task );
	}
	if ( ! form.destination.isNormalized ) {
		const task = normalizeAddress( dispatch, form.destination.values, 'destination', addressNormalizationURL, nonce );
		addressNormalizationQueue.push( task );
	}

	Promise.all( addressNormalizationQueue ).then( ( normalizationResults ) => {
		if ( ! _.every( normalizationResults ) ) {
			return;
		}
		const state = getState().shippingLabel;
		form = state.form;
		const formData = {
			origin: form.origin.selectNormalized ? form.origin.normalized : form.origin.values,
			destination: form.destination.selectNormalized ? form.destination.normalized : form.destination.values,
			packages: _.map( form.packages.selected, ( pckg, pckgId ) => {
				const rate = _.find( form.rates.available[ pckgId ].rates, { service_id: form.rates.values[ pckgId ] } );
				return {
					...convertToApiPackage( pckg ),
					shipment_id: form.rates.available[ pckgId ].shipment_id,
					rate_id: rate.rate_id,
					service_id: form.rates.values[ pckgId ],
					carrier_id: rate.carrier_id,
					service_name: rate.title,
					products: _.flatten( pckg.items.map( ( item ) => _.fill( new Array( item.quantity ), item.product_id ) ) ),
				};
			} ),
		};

		saveForm( setIsSaving, setSuccess, _.noop, setError, purchaseURL, nonce, 'POST', formData );
	} ).catch( ( err ) => {
		console.error( err );
		dispatch( NoticeActions.errorNotice( err.toString() ) );
	} );
};

export const confirmPrintLabel = ( url ) => ( dispatch ) => {
	printDocument( url )
		.then( () => {
			dispatch( exitPrintingFlow( true ) );
			dispatch( clearAvailableRates() );
		} )
		.catch( ( error ) => dispatch( NoticeActions.errorNotice( error.toString() ) ) );
};

export const openRefundDialog = ( labelId ) => {
	return {
		type: OPEN_REFUND_DIALOG,
		labelId,
	};
};

export const fetchLabelsStatus = () => ( dispatch, getState, { labelStatusURL, nonce } ) => {
	getState().shippingLabel.labels.forEach( ( label ) => {
		if ( label.statusUpdated ) {
			return;
		}
		const labelId = label.label_id;
		let error = null;
		let response = null;
		const setError = ( err ) => error = err;
		const setSuccess = ( success, json ) => {
			if ( success ) {
				response = json.label;
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
	const setError = ( err ) => {
		error = err;
	};
	const setSuccess = ( success, json ) => {
		if ( success ) {
			response = json.refund;
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
				dispatch( NoticeActions.successNotice( __( 'The refund request has been sent successfully.' ), { duration: 5000 } ) );
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

export const confirmReprint = () => ( dispatch, getState, context ) => {
	dispatch( { type: CONFIRM_REPRINT } );
	const state = getState().shippingLabel;
	const labelId = state.reprintDialog.labelId;
	printDocument( getPrintURL( getState().shippingLabel.paperSize, [ { labelId } ], context ) )
		.catch( ( error ) => {
			console.error( error );
			dispatch( NoticeActions.errorNotice( error.toString() ) );
		} )
		.then( () => dispatch( closeReprintDialog() ) );
};
