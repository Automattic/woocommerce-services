import {
	ADD_PACKAGE,
	EDIT_PACKAGE,
	DISMISS_MODAL,
} from './actions';

const reducers = {};

reducers[ADD_PACKAGE] = ( state ) => {
	return Object.assign( {}, state, {
		showModal: true,
		mode: 'add',
	} );
};

reducers[EDIT_PACKAGE] = ( state, action ) => {
	return Object.assign( {}, state, {
		showModal: true,
		mode: 'edit',
		packageData: action.package,
	} );
};

reducers[DISMISS_MODAL] = ( state ) => {
	return Object.assign( {}, state, {
		showModal: false,
	} );
};

const packages = ( state = {}, action ) => {
	if ( reducers.hasOwnProperty( action.type ) ) {
		return reducers[action.type]( state, action );
	}
	return state;
};

export default packages;
