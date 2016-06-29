import { SET_FORM_PROPERTY } from './actions';
import packages from './packages/reducer';
import values from './values/reducer';
import * as packagesActions from './packages/actions';
import * as formValueActions from './values/actions';

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

	if ( 'function' === typeof reducers[ action.type ] ) {
		newState = reducers[ action.type ]( state, action );
	}

	if ( state.packages || packagesActions[ action.type ] ) {
		newState = Object.assign( newState, {
			packages: packages( state.packages || {}, action ),
		} );
	}

	if ( formValueActions[ action.type ] || ( packagesActions.SAVE_PACKAGE === action.type ) ) {
		newState.pristine = false;

		// Allow client-side form validation to take over error state when inputs change
		if ( newState.errors ) {
			delete newState.errors;
		}

		newState = Object.assign( newState, {
			values: values( state.values || {}, action ),
		} );
	}

	return newState;
}
