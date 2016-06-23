import { SET_FORM_PROPERTY } from './actions';
import packages from './packages/reducer';
import settings from './settings/reducer';
import * as packagesActions from './packages/actions';
import * as settingsActions from './settings/actions';

const reducers = {};

reducers[ SET_FORM_PROPERTY ] = ( state, action ) => {
	const newObj = {};
	newObj[ action.field ] = action.value;
	if ( 'success' === action.field && action.value ) {
		newObj.pristine = true;
	}
	return Object.assign( {}, state, newObj );
};

export default function form( state = {}, action ) {
	let newState = Object.assign( {}, state );

	if ( reducers.hasOwnProperty( action.type ) ) {
		newState = reducers[ action.type ]( state, action );
	}

	if ( state.hasOwnProperty( 'packages' ) || packagesActions.hasOwnProperty( action.type ) ) {
		newState = Object.assign( newState, {
			packages: packages( state.packages || {}, action ),
		} );
	}

	if ( settingsActions.hasOwnProperty( action.type ) || ( packagesActions.SAVE_PACKAGE === action.type ) ) {
		newState.pristine = false;

		// Allow client-side form validation to take over error state when inputs change
		if ( newState.hasOwnProperty( 'errors' ) ) {
			delete newState.errors;
		}

		newState = Object.assign( newState, {
			settings: settings( state.settings || {}, action ),
		} );
	}

	return newState;
}
