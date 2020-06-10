/** @format */

/**
 * External dependencies
 */

/**
 * Internal dependencies
 */
import {
	WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SHOW_SETTINGS,
} from '../action-types';

export const initialState = {
	modalErrors: {},
	pristine: true,
};

const reducers = {};

reducers[ WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_SHOW_SETTINGS ] = ( state, { carrier } ) => {
	const newState = {
		...state,
		showSettings: carrier,
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
