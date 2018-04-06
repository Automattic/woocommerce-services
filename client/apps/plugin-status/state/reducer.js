/**
 * Internal dependencies
 */
import {
	PLUGIN_STATUS_DEBUG_TOGGLE,
	PLUGIN_STATUS_LOGGING_TOGGLE,
	PLUGIN_STATUS_REST_REQUEST,
	PLUGIN_STATUS_REST_RESPONSE,
} from './actions';

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

	[ PLUGIN_STATUS_REST_REQUEST ]: ( state ) => {
		return { ...state,
			health_items: { ...state.health_items,
				rest_api: {
					state: 'warning',
					message: 'Checking REST API health...',
				},
			},
		};
	},

	[ PLUGIN_STATUS_REST_RESPONSE ]: ( state, { success, message } ) => {
		return {
			...state,
			health_items: { ...state.health_items,
				rest_api: {
					state: success ? 'success' : 'error',
					message,
				},
			},
		};
	},
};

export default ( state = {}, action ) => {
	if ( reducer[ action.type ] ) {
		return reducer[ action.type ]( state, action );
	}

	return state;
};
