import {
	ADD_PACKAGE,
	EDIT_PACKAGE,
	DISMISS_MODAL,
	SET_MODAL_READONLY,
	UPDATE_PACKAGES_FIELD,
	SAVE_PACKAGE,
	TOGGLE_OUTER_DIMENSIONS,
} from './actions';
import omit from 'lodash/omit';
import omitBy from 'lodash/omitBy';
import isNull from 'lodash/isNull';

const reducers = {};

reducers[ADD_PACKAGE] = ( state ) => {
	const newPackageData = ( 'edit' === state.mode ) ? {} : omit( state.packageData, 'index' );
	return Object.assign( {}, state, {
		showModal: true,
		mode: 'add',
		packageData: newPackageData,
	} );
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

reducers[SET_MODAL_READONLY] = ( state, action ) => {
	return Object.assign( {}, state, {
		modalReadOnly: action.value,
	} );
};

reducers[UPDATE_PACKAGES_FIELD] = ( state, action ) => {
	const mergedPackageData = Object.assign( {}, state.packageData, action.values );
	const newPackageData = omitBy( mergedPackageData, isNull );
	return Object.assign( {}, state, {
		packageData: newPackageData,
	} );
};

reducers[SAVE_PACKAGE] = ( state ) => {
	return Object.assign( {}, state, {
		showModal: false,
		mode: 'add',
		packageData: {},
		showOuterDimensions: false,
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
