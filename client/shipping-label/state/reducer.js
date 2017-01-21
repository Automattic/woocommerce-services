import {
	OPEN_PRINTING_FLOW,
	EXIT_PRINTING_FLOW,
	TOGGLE_STEP,
	UPDATE_ADDRESS_VALUE,
	ADDRESS_NORMALIZATION_IN_PROGRESS,
	ADDRESS_NORMALIZATION_COMPLETED,
	SELECT_NORMALIZED_ADDRESS,
	EDIT_ADDRESS,
	CONFIRM_ADDRESS_SUGGESTION,
	UPDATE_PACKAGE_WEIGHT,
	UPDATE_RATE,
	UPDATE_PAPER_SIZE,
	UPDATE_PREVIEW,
	PURCHASE_LABEL_REQUEST,
	PURCHASE_LABEL_RESPONSE,
	RATES_RETRIEVAL_IN_PROGRESS,
	RATES_RETRIEVAL_COMPLETED,
	OPEN_REFUND_DIALOG,
	CLOSE_REFUND_DIALOG,
	LABEL_STATUS_RESPONSE,
	REFUND_REQUEST,
	REFUND_RESPONSE,
	OPEN_REPRINT_DIALOG,
	CLOSE_REPRINT_DIALOG,
	CONFIRM_REPRINT,
	OPEN_PACKAGE,
	OPEN_ITEM_MOVE,
	MOVE_ITEM,
	CLOSE_ITEM_MOVE,
	SET_TARGET_PACKAGE,
	ADD_PACKAGE,
	REMOVE_PACKAGE,
	SET_PACKAGE_TYPE,
	SAVE_PACKAGES,
	OPEN_ADD_ITEM,
	CLOSE_ADD_ITEM,
	SET_ADDED_ITEM,
} from './actions';
import getBoxDimensions from 'lib/utils/get-box-dimensions';
import _ from 'lodash';

const generateUniqueBoxId = ( keyBase, boxIds ) => {
	for ( let i = 0; i <= boxIds.length; i++ ) {
		if ( -1 === boxIds.indexOf( keyBase + i ) ) {
			return keyBase + i;
		}
	}
};

const reducers = {};

reducers[ OPEN_PRINTING_FLOW ] = ( state ) => {
	return { ...state,
		showPurchaseDialog: true,
	};
};

reducers[ EXIT_PRINTING_FLOW ] = ( state ) => {
	return { ...state,
		showPurchaseDialog: false,
	};
};

reducers[ TOGGLE_STEP ] = ( state, { stepName } ) => {
	return { ...state,
		form: { ...state.form,
			[ stepName ]: { ...state.form[ stepName ],
				expanded: ! state.form[ stepName ].expanded,
			},
		},
	};
};

reducers[ UPDATE_ADDRESS_VALUE ] = ( state, { group, name, value } ) => {
	const newState = { ...state,
		form: { ...state.form,
			[ group ]: { ...state.form[ group ],
				values: { ...state.form[ group ].values,
					[ name ]: value,
				},
				isNormalized: false,
				normalized: null,
			},
		},
	};
	if ( 'country' === name ) {
		return reducers[ UPDATE_ADDRESS_VALUE ]( newState, { group, name: 'state', value: '' } );
	}
	return newState;
};

reducers[ ADDRESS_NORMALIZATION_IN_PROGRESS ] = ( state, { group } ) => {
	return { ...state,
		form: { ...state.form,
			[ group ]: { ...state.form[ group ],
				normalizationInProgress: true,
			},
		},
	};
};

reducers[ ADDRESS_NORMALIZATION_COMPLETED ] = ( state, { group, normalized, isTrivialNormalization } ) => {
	const newState = { ...state,
		form: { ...state.form,
			[ group ]: { ...state.form[ group ],
				normalizationInProgress: false,
				isNormalized: true,
				selectNormalized: true,
				normalized,
			},
		},
	};
	if ( isTrivialNormalization ) {
		newState.form[ group ].values = normalized;
	}
	return newState;
};

reducers[ SELECT_NORMALIZED_ADDRESS ] = ( state, { group, selectNormalized } ) => {
	return { ...state,
		form: { ...state.form,
			[ group ]: { ...state.form[ group ],
				selectNormalized,
			},
		},
	};
};

reducers[ EDIT_ADDRESS ] = ( state, { group } ) => {
	return { ...state,
		form: { ...state.form,
			[ group ]: { ...state.form[ group ],
				selectNormalized: false,
				normalized: null,
				isNormalized: false,
			},
		},
	};
};

reducers[ CONFIRM_ADDRESS_SUGGESTION ] = ( state, { group } ) => {
	const groupState = {
		...state.form[ group ],
		expanded: false,
	};
	if ( groupState.selectNormalized ) {
		groupState.values = groupState.normalized;
	} else {
		groupState.normalized = groupState.values;
	}
	return { ...state,
		form: { ...state.form,
			[ group ]: groupState,
		},
	};
};

reducers[ UPDATE_PACKAGE_WEIGHT ] = ( state, { packageId, value } ) => {
	const newPackages = { ...state.form.packages.selected };

	newPackages[ packageId ] = {
		...newPackages[ packageId ],
		weight: parseFloat( value ),
	};

	return { ...state,
		form: { ...state.form,
			packages: { ...state.form.packages,
				selected: newPackages,
				saved: false,
			},
		},
	};
};

reducers[ OPEN_PACKAGE ] = ( state, { openedPackageId } ) => {
	return { ...state,
		openedPackageId,
	};
};

reducers[ OPEN_ITEM_MOVE ] = ( state, { movedItemIndex } ) => {
	return {
		...state,
		showItemMoveDialog: true,
		targetPackageId: state.openedPackageId,
		movedItemIndex,
	};
};

reducers[ MOVE_ITEM ] = ( state, { openedPackageId, movedItemIndex, targetPackageId } ) => {
	if ( -1 === movedItemIndex || openedPackageId === targetPackageId || undefined === openedPackageId ) {
		return state;
	}

	const newPackages = { ...state.form.packages.selected };
	const newUnpacked = [ ...state.form.packages.unpacked ];
	let movedItem;
	let addedPackageId = '';

	if ( '' === openedPackageId ) {
		//move from unpacked
		movedItem = newUnpacked.splice( movedItemIndex, 1 )[ 0 ];
	} else {
		//move from packed
		const originItems = [ ...newPackages[ openedPackageId ].items ];
		movedItem = originItems.splice( movedItemIndex, 1 )[ 0 ];

		newPackages[ openedPackageId ] = {
			...newPackages[ openedPackageId ],
			items: originItems,
			weight: newPackages[ openedPackageId ].weight - movedItem.weight,
		};
	}

	if ( 'individual' === targetPackageId ) {
		//move to an individual packaging
		const packageKeys = Object.keys( newPackages );
		addedPackageId = generateUniqueBoxId( 'client_individual_', packageKeys );
		const { height, length, width, weight } = movedItem;
		newPackages[ addedPackageId ] = {
			height, length, width, weight,
			box_id: 'individual',
			items: [ movedItem ],
		};
	} else if ( '' === targetPackageId ) {
		//move to unpacked
		newUnpacked.push( movedItem );
	} else {
		//move to a custom package
		const targetItems = [ ...newPackages[ targetPackageId ].items ];
		targetItems.push( movedItem );
		newPackages[ targetPackageId ] = {
			...newPackages[ targetPackageId ],
			items: targetItems,
			weight: newPackages[ targetPackageId ].weight + movedItem.weight,
		};
	}

	return {
		...state,
		form: {
			...state.form,
			packages: {
				...state.form.packages,
				selected: newPackages,
				unpacked: newUnpacked,
				saved: false,
			},
			rates: {
				...state.form.rates,
				values: _.mapValues( newPackages, () => '' ),
				available: {},
			},
		},
		addedPackageId,
	};
};

reducers[ CLOSE_ITEM_MOVE ] = ( state ) => {
	return {
		...state,
		movedItemIndex: -1,
		showItemMoveDialog: false,
	};
};

reducers[ SET_TARGET_PACKAGE ] = ( state, { targetPackageId } ) => {
	return {
		...state,
		targetPackageId,
	};
};

reducers[ OPEN_ADD_ITEM ] = ( state ) => {
	return {
		...state,
		showAddItemDialog: true,
	};
};

reducers[ CLOSE_ADD_ITEM ] = ( state ) => {
	return {
		...state,
		movedItemIndex: -1,
		showAddItemDialog: false,
	};
};

reducers[ SET_ADDED_ITEM ] = ( state, { sourcePackageId, movedItemIndex } ) => {
	return {
		...state,
		sourcePackageId,
		movedItemIndex,
	};
};

reducers[ ADD_PACKAGE ] = ( state ) => {
	const newPackages = {...state.form.packages.selected};
	const packageKeys = Object.keys( newPackages );
	const boxesKeys = Object.keys( state.form.packages.all );
	if ( ! boxesKeys.length ) {
		return state;
	}

	const boxId = boxesKeys[ 0 ];
	const box = state.form.packages.all[ boxId ];
	const { height, length, width } = getBoxDimensions( box );
	const addedPackageId = generateUniqueBoxId( 'client_custom_', packageKeys );
	const openedPackageId = addedPackageId;
	newPackages[ addedPackageId ] = {
		height, length, width,
		id: addedPackageId,
		weight: box.box_weight,
		box_id: boxId,
		items: [],
	};

	return {
		...state,
		openedPackageId,
		addedPackageId,
		form: {
			...state.form,
			packages: {
				...state.form.packages,
				selected: newPackages,
				saved: false,
			},
			rates: {
				...state.form.rates,
				values: _.mapValues( newPackages, () => '' ),
				available: {},
			},
		},
	};
};

reducers[ REMOVE_PACKAGE ] = ( state, { packageId } ) => {
	const newPackages = {...state.form.packages.selected};
	const pckg = newPackages[ packageId ];
	const newUnpacked = state.form.packages.unpacked.concat( pckg.items );
	delete newPackages[ packageId ];
	const openedPackageId = Object.keys( newPackages )[ 0 ] || '';

	return {
		...state,
		openedPackageId,
		form: {
			...state.form,
			packages: {
				...state.form.packages,
				selected: newPackages,
				unpacked: newUnpacked,
				saved: false,
			},
			rates: {
				...state.form.rates,
				values: _.mapValues( newPackages, () => '' ),
				available: {},
			},
		},
	};
};

reducers[ SET_PACKAGE_TYPE ] = ( state, { packageId, boxTypeId } ) => {
	const box = state.form.packages.all[ boxTypeId ];
	const newPackages = {...state.form.packages.selected};
	const { length, width, height } = getBoxDimensions( box );
	const oldPackage = newPackages[ packageId ];

	newPackages[ packageId ] = {
		..._.omit( oldPackage, 'service_id' ),
		height, length, width,
		weight: box.box_weight + _.sumBy( oldPackage.items, 'weight' ),
		box_id: boxTypeId,
	};

	return {
		...state,
		form: {
			...state.form,
			packages: {
				...state.form.packages,
				selected: newPackages,
				saved: false,
			},
			rates: {
				...state.form.rates,
				values: _.mapValues( newPackages, () => '' ),
				available: {},
			},
		},
	};
};

reducers[ SAVE_PACKAGES ] = ( state ) => {
	return {
		...state,
		form: {
			...state.form,
			packages: {
				...state.form.packages,
				saved: true,
			},
		},
	};
};

reducers[ UPDATE_RATE ] = ( state, { packageId, value } ) => {
	const newRates = { ...state.form.rates.values };
	newRates[ packageId ] = value;

	return { ...state,
		form: { ...state.form,
			rates: { ...state.form.rates,
				values: newRates,
			},
		},
	};
};

reducers[ UPDATE_PAPER_SIZE ] = ( state, { value } ) => {
	return { ...state,
		paperSize: value,
	};
};

reducers[ UPDATE_PREVIEW ] = ( state, { url } ) => {
	return { ...state,
		form: { ...state.form,
			preview: { ...state.form.preview,
				labelPreviewURL: url,
			},
		},
	};
};

reducers[ PURCHASE_LABEL_REQUEST ] = ( state ) => {
	return { ...state,
		form: { ...state.form,
			isSubmitting: true,
		},
	};
};

reducers[ PURCHASE_LABEL_RESPONSE ] = ( state, { response, error } ) => {
	if ( error ) {
		return { ...state,
			form: { ...state.form,
				isSubmitting: false,
			},
		};
	}

	return { ...state,
		labels: response.map( ( label ) => ( { ...label, statusUpdated: true } ) ),
	};
};

reducers[ RATES_RETRIEVAL_IN_PROGRESS ] = ( state ) => {
	return { ...state,
		form: { ...state.form,
			rates: { ...state.form.rates,
				retrievalInProgress: true,
			},
		},
	};
};

reducers[ RATES_RETRIEVAL_COMPLETED ] = ( state, { rates } ) => {
	return { ...state,
		form: { ...state.form,
			rates: {
				values: _.mapValues( rates, ( rate ) => {
					const selected = rate.rates.find( ( r ) => r.is_selected );
					if ( selected ) {
						return selected.service_id;
					}

					return '';
				} ),
				retrievalInProgress: false,
				available: rates,
			},
		},
	};
};

reducers[ OPEN_REFUND_DIALOG ] = ( state, { labelId } ) => {
	return { ...state,
		refundDialog: {
			labelId,
		},
	};
};

reducers[ CLOSE_REFUND_DIALOG ] = ( state ) => {
	if ( state.refundDialog.isSubmitting ) {
		return state;
	}
	return { ...state,
		refundDialog: null,
	};
};

reducers[ LABEL_STATUS_RESPONSE ] = ( state, { labelId, response, error } ) => {
	if ( error ) {
		response = {};
	}

	const labelIndex = _.findIndex( state.labels, { label_id: labelId } );
	const labelData = {
		...state.labels[ labelIndex ],
		...response,
		statusUpdated: true,
	};

	const newState = { ...state,
		labels: [ ...state.labels ],
	};
	newState.labels[ labelIndex ] = labelData;
	return newState;
};

reducers[ REFUND_REQUEST ] = ( state ) => {
	return { ...state,
		refundDialog: { ...state.refundDialog,
			isSubmitting: true,
		},
	};
};

reducers[ REFUND_RESPONSE ] = ( state, { response, error } ) => {
	if ( error ) {
		return { ...state,
			refundDialog: {
				...state.refundDialog,
				isSubmitting: false,
			},
		};
	}

	const labelIndex = _.findIndex( state.labels, { label_id: state.refundDialog.labelId } );
	const labelData = {
		...state.labels[ labelIndex ],
		refund: response,
	};

	const newState = { ...state,
		refundDialog: null,
		labels: [ ...state.labels ],
	};
	newState.refundDialog = null;
	newState.labels[ labelIndex ] = labelData;

	return newState;
};

reducers[ OPEN_REPRINT_DIALOG ] = ( state, { labelId } ) => {
	return { ...state,
		reprintDialog: {
			labelId,
		},
	};
};

reducers[ CLOSE_REPRINT_DIALOG ] = ( state ) => {
	return { ...state,
		reprintDialog: null,
	};
};

reducers[ CONFIRM_REPRINT ] = ( state ) => {
	return { ...state,
		reprintDialog: { ...state.reprintDialog,
			isFetching: true,
		},
	};
};

export default ( state = {}, action ) => {
	if ( reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};
