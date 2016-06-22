import {
	SET_FORM_PROPERTY,
	NEXT_STEP,
	GO_TO_STEP,
} from './actions';
import packages from './packages/reducer';
import * as packagesActions from './packages/actions';
import shippingLabel from './shipping-label/reducer';
import * as shippingLabelActions from './shipping-label/actions';
import * as settingsActions from '../settings/actions';

const reducers = {};

reducers[ SET_FORM_PROPERTY ] = ( state, action ) => {
	const newObj = {};
	newObj[ action.field ] = action.value;
	if ( 'success' === action.field && action.value ) {
		newObj.pristine = true;
	}
	return Object.assign( {}, state, newObj );
};

reducers[ NEXT_STEP ] = ( state ) => {
	return Object.assign( {}, state, { currentStep: state.currentStep + 1 } );
};

reducers[ GO_TO_STEP ] = ( state, action ) => {
	return Object.assign( {}, state, { currentStep: action.stepIndex } );
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

	if ( state.hasOwnProperty( 'shippingLabel' ) || shippingLabelActions.hasOwnProperty( action.type ) ) {
		newState = Object.assign( newState, {
			shippingLabel: shippingLabel( state.shippingLabel || {}, action ),
		} );
	}

	if ( settingsActions.hasOwnProperty( action.type ) ) {
		newState.pristine = false;

		// Allow client-side form validation to take over error state when inputs change
		if ( newState.hasOwnProperty( 'errors' ) ) {
			delete newState.errors;
		}
	}

	return newState;
}
