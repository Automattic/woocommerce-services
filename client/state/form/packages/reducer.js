import {
	ADD_PACKAGE,
	EDIT_PACKAGE,
	DISMISS_MODAL,
	UPDATE_PACKAGES_FIELD,
	SAVE_PACKAGE,
} from './actions';
import omit from 'lodash/omit';
import omitBy from 'lodash/omitBy';
import isNull from 'lodash/isNull';

const reducers = {};

reducers[ADD_PACKAGE] = ( state ) => {
	const newPackageData = omit( state.packageData, 'index' );
	return Object.assign( {}, state, {
		showModal: true,
		mode: 'add',
		packageData: newPackageData,
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
	} );
};

const packages = ( state = {}, action ) => {
	if ( reducers.hasOwnProperty( action.type ) ) {
		return reducers[action.type]( state, action );
	}
	return state;
};

export default packages;
