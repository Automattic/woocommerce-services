import {
	SET_PAYMENT_METHOD,
} from './actions';

const reducers = {};

reducers[ SET_PAYMENT_METHOD ] = ( state, action ) => {
	return Object.assign( {}, state, {
		selected_payment_method_id: action.payment_method_id,
	} );
};

export default ( state = {}, action ) => {
	if ( reducers[ action.type ] ) {
		return reducers[ action.type ]( state, action );
	}
	return state;
};
