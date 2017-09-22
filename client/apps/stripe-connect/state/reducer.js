/**
 * External dependencies
 */

/**
 * Internal dependencies
 */

import { SET_STATE } from './actions';

const reducers = {};

reducers[ SET_STATE ] = ( state, action ) => {
	return Object.assign( {}, state, action.value );
};

export default ( state = {}, action ) => {
	if ( reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};
