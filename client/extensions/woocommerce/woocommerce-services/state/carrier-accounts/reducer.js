/** @format */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS,
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_UPDATE_SETTINGS,
} from '../action-types';

export const initialState = {
	modalErrors: {},
	pristine: true,
};

const reducers = {};

reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SUBMIT_SETTINGS ] = ( state, { carrier } ) => {
	const newState = {
		...state,
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

const carrierAccounts = ( state = initialState, action ) => {
	if ( 'function' === typeof reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};

export default carrierAccounts;
