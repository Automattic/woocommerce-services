/**
 * External dependencies
 */
import _ from 'lodash';

/**
 * Internal dependencies
 */
import { SET_FORM_PROPERTY, SET_ALL_PRISTINE, DISMISS_NOTICE } from './actions';
import values from './values/reducer';
import * as formValueActions from './values/actions';

const reducers = {};

reducers[ SET_FORM_PROPERTY ] = ( state, action ) => {
	const newObj = {};
	newObj[ action.field ] = action.value;
	if ( 'success' === action.field && action.value ) {
		newObj.pristine = _.mapValues( state.pristine, () => true );
	} else if ( 'fieldsStatus' === action.field && action.value ) {
		Object.keys( action.value ).forEach( ( fieldPath ) => {
			const fieldStatus = action.value[ fieldPath ];
			if ( 'overwrite' === fieldStatus.level ) {
				const overwriteAction = formValueActions.updateField( fieldPath, fieldStatus.value );
				newObj.values = values( newObj.values || state.values || {}, overwriteAction );
				delete action.value[ fieldPath ];
			}
		} );
	}
	return Object.assign( {}, state, newObj );
};

reducers[ SET_ALL_PRISTINE ] = ( state, action ) => ( {
	...state,
	pristine: _.mapValues( state.pristine, () => action.pristineValue ),
} );

reducers[ DISMISS_NOTICE ] = ( state ) => ( {
	...state,
	noticeDismissed: true,
} );

export default function form( state = {}, action ) {
	let newState = Object.assign( {}, state );

	if ( 'function' === typeof reducers[ action.type ] ) {
		newState = reducers[ action.type ]( state, action );
	}

	if ( formValueActions[ action.type ] ) {
		newState.pristine = { ...state.pristine, [ action.path ]: false };

		// Allow client-side form validation to take over error state when inputs change
		delete newState.error;
		delete newState.fieldsStatus;

		newState = Object.assign( newState, {
			values: values( state.values || {}, action ),
		} );
	}

	return newState;
}
