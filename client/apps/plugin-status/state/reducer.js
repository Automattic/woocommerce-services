/**
 * Internal dependencies
 */
import {
	PLUGIN_STATUS_DEBUG_TOGGLE,
	PLUGIN_STATUS_LOGGING_TOGGLE,
	SERVICE_DATA_REFRESH,
} from './action-types';

const reducer = {
	[ PLUGIN_STATUS_DEBUG_TOGGLE ]: ( state, { value } ) => {
		return {
			...state,
			debug_enabled: value,
		};
	},

	[ PLUGIN_STATUS_LOGGING_TOGGLE ]: ( state, { value } ) => {
		return {
			...state,
			logging_enabled: value,
		};
	},

	[ SERVICE_DATA_REFRESH ]: ( state, { value } ) => {
		return {
			...state,
			health_items: {
				...state.health_items,
				woocommerce_services: {
					...state.health_items.woocommerce_services,
					...value,
				}
			}
		};
	},
};

export default ( state = {}, action ) => {
	if ( reducer[ action.type ] ) {
		return reducer[ action.type ]( state, action );
	}

	return state;
};
