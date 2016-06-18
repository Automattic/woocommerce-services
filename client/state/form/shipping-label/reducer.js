import {
	OPEN_PRINTING_FLOW,
	EXIT_PRINTING_FLOW,
} from './actions';

const reducers = {};

reducers[ OPEN_PRINTING_FLOW ] = ( state ) => {
	return Object.assign( {}, state, {
		showDialog: true,
	} );
};

reducers[ EXIT_PRINTING_FLOW ] = ( state ) => {
	return Object.assign( {}, state, {
		showDialog: false,
	} );
};

export default ( state = {}, action ) => {
	if ( reducers.hasOwnProperty( action.type ) ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};
