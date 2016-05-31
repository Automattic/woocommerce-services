import {
	UPDATE_FORM_ELEMENT_FIELD,
	SET_FIELD,
} from './actions';
import packages from './packages/reducer';
import * as packagesActions from './packages/actions';
import * as settingsActions from '../settings/actions';

const reducers = {};

reducers[UPDATE_FORM_ELEMENT_FIELD] = ( state, action ) => {
	const newObj = {};
	newObj[action.element] = {};
	newObj[action.element][action.field] = action.value;
	return Object.assign( {}, state, newObj );
};

reducers[SET_FIELD] = ( state, action ) => {
	const newObj = {};
	newObj[action.field] = action.value;
	return Object.assign( {}, state, newObj );
};

export default function form( state = {}, action ) {
	let newState = Object.assign( {}, state );

	if ( reducers.hasOwnProperty( action.type ) ) {
		newState = reducers[action.type]( state, action );
	}

	if ( state.hasOwnProperty( 'packages' ) || packagesActions.hasOwnProperty( action.type ) ) {
		newState = Object.assign( newState, {
			packages: packages( state.packages || {}, action ),
		} );
	}

	// Allow client-side form validation to take over error state when inputs change
	if ( settingsActions.hasOwnProperty( action.type ) && newState.hasOwnProperty( 'errors' ) ) {
		delete newState.errors;
	}

	return newState;
}
