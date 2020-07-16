/** @format */

/**
 * External dependencies
 */
import { isEmpty } from 'lodash';

/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS,
} from '../action-types';

export const initialState = {
	modalErrors: {},
	pristine: true,
};

const reducers = {};

reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS ] = ( state, { carrier } ) => {
	const settings = state[ carrier ].settings;
	const { fieldErrors } = settings;

	if ( ! isEmpty( fieldErrors ) ) {
		return state;
	}

	const newState = {
		...state,
		[ carrier ]: { settings: { ...settings, fieldErrors } }
	};

	return newState;
};

reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS ] = ( state, { carrier, fieldName, newValue } ) => {
	const settings = state[ carrier ].settings;
	const { ignoreValidation, values } = settings;

	if ( newValue.target ) {
		switch( newValue.target.type ) {
			case 'checkbox':
				newValue = newValue.target.checked;
				break;
			default:
				newValue = newValue.target.value;
		}
	}

	const newState = {
		...state,
		[ carrier ]: {
			settings: {
				...settings,
				values: {
					...values,
					[ fieldName ]: newValue
				}
			}
		},
	};

	if ( ignoreValidation ) {
		newState[carrier].settings.ignoreValidation = {
			...ignoreValidation,
			[ fieldName ]: false,
		};
	}

	return newState;
};

reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_SHOW_UPS_INVOICE_FIELDS ] = ( state, { carrier } ) => {
	const settings = state[ carrier ].settings;

	const newState = {
		...state,
		[ carrier ]: {
			settings: {
				...settings,
				showUPSInvoiceFields: ! settings.showUPSInvoiceFields,
			}
		},
	};

	return newState;
};


reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_CANCEL_CONNECTION_DIALOG ] = ( state, { carrier, show } ) => {
	const settings = state[ carrier ].settings;

	const newState = {
		...state,
		[ carrier ]: {
			settings: {
				...settings,
				showCancelConnectionDialog: show,
			}
		},
	};

	return newState;
};

reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_ENABLE_DISCONNECT_DIALOG ] = ( state, { carrier, show } ) => {
	const carrierData = state[ carrier ];

	const newState = {
		...state,
		[ carrier ]: {
			...carrierData,
			showDisconnectDialog: show,
		},
	};

	return newState;
};
reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_DISCONNECT_CARRIER ] = ( state, { carrier } ) => {
	const settings = state[ carrier ].settings;

	const newState = {
		...state,
		[ carrier ]: { settings }
	};

	return newState;
};


const carrierAccounts = ( state = initialState, action ) => {
	if ( 'function' === typeof reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};

export default carrierAccounts;
