import {
	ADD_PACKAGE,
	EDIT_PACKAGE,
	DISMISS_MODAL,
	SET_SELECTED_PRESET,
	UPDATE_PACKAGES_FIELD,
	SAVE_PACKAGE,
	TOGGLE_OUTER_DIMENSIONS,
} from './actions';
import omitBy from 'lodash/omitBy';
import trim from 'lodash/trim';

const isNullOrEmpty = ( value ) => null === value || '' === trim( value );

const reducers = {};

reducers[ADD_PACKAGE] = ( state ) => {
	const newState = Object.assign( {}, state, {
		showModal: true,
		mode: 'add',
	} );

	if ( 'edit' === state.mode || ! newState.packageData ) {
		newState.packageData = { is_user_defined: true }
	}

	return newState;
};

reducers[EDIT_PACKAGE] = ( state, action ) => {
	return Object.assign( {}, state, {
		showModal: true,
		modalReadOnly: false,
		mode: 'edit',
		packageData: action.package,
		showOuterDimensions: false,
	} );
};

reducers[DISMISS_MODAL] = ( state ) => {
	return Object.assign( {}, state, {
		showModal: false,
	} );
};

reducers[SET_SELECTED_PRESET] = ( state, action ) => {
	return Object.assign( {}, state, {
		selectedPreset: action.value,
	} );
};

reducers[UPDATE_PACKAGES_FIELD] = ( state, action ) => {
	const mergedPackageData = Object.assign( {}, state.packageData, action.values );
	const newPackageData = omitBy( mergedPackageData, isNullOrEmpty );
	return Object.assign( {}, state, {
		packageData: newPackageData,
	} );
};

reducers[SAVE_PACKAGE] = ( state ) => {
	return Object.assign( {}, state, {
		showModal: false,
		mode: 'add',
		packageData: {
			is_user_defined: true,
		},
		showOuterDimensions: false,
		selectedPreset: null,
	} );
};

reducers[TOGGLE_OUTER_DIMENSIONS] = ( state ) => {
	return Object.assign( {}, state, {
		showOuterDimensions: true,
	} );
};

const packages = ( state = {}, action ) => {
	if ( reducers.hasOwnProperty( action.type ) ) {
		return reducers[action.type]( state, action );
	}
	return state;
};

export default packages;
