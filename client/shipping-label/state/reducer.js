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
} from './actions';
import _ from 'lodash';

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

reducers[ ADDRESS_NORMALIZATION_COMPLETED ] = ( state, { group, normalized } ) => {
	return { ...state,
		form: { ...state.form,
			[ group ]: { ...state.form[ group ],
				normalizationInProgress: false,
				isNormalized: true,
				selectNormalized: true,
				normalized,
			},
		},
	};
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
	const newPackages = { ...state.form.packages.values };

	newPackages[ packageId ] = {
		...newPackages[ packageId ],
		weight: parseFloat( value ),
	};

	return { ...state,
		form: { ...state.form,
			packages: { ...state.form.packages,
				values: newPackages,
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

reducers[ PURCHASE_LABEL_REQUEST ] = ( state ) => {
	return { ...state,
		form: { ...state.form,
			isSubmitting: true,
		},
	};
};

reducers[ PURCHASE_LABEL_RESPONSE ] = ( state, { response, error } ) => {
	const newState = { ...state,
		form: { ...state.form,
			isSubmitting: false,
		},
	};
	if ( ! error ) {
		newState.labels = response;
	}
	return newState;
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
				values: _.mapValues( state.form.rates.values, () => '' ),
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
		...response,
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
