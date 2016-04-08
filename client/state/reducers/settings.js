import cloneDeep from 'lodash/cloneDeep';
import {
	UPDATE_SETTINGS_FIELD,
	UPDATE_SETTINGS_ARRAY_FIELD,
	ADD_SETTINGS_OBJECT_FIELD,
	REMOVE_SETTINGS_OBJECT_FIELD,
	UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	REMOVE_SETTINGS_OBJECT_SUB_FIELD,
} from '../actions/settings';

const updateSettingField = ( state, action ) => {
	return Object.assign( {}, state, {
		[action.key]: action.value,
	} );
};

const updateSettingsArrayField = ( state, action ) => {
	const originalArray = cloneDeep( state[action.array_key] );
	const updatedArray = originalArray.map( arrayItemState => {
		if ( action.id !== arrayItemState.id ) {
			return arrayItemState;
		}
		arrayItemState[action.key] = action.value;
		return arrayItemState;
	} );
	return Object.assign( {}, state, {
		[action.array_key]: updatedArray,
	} );
};

const addSettingsObjectField = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const updatedObject = Object.assign( {}, originalObject, {
		[action.key]: action.object,
	} );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

const removeSettingsObjectField = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const updatedObject = Object.assign( {}, originalObject );
	delete updatedObject[action.key];

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

const updateSettingsObjectSubField = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const originalSubObject = originalObject[action.key] || {};
	const updatedSubObject = Object.assign( {}, originalSubObject, {
		[action.subfield_key]: action.value,
	} );
	const updatedObject = Object.assign( {}, originalObject, {
		[action.key]: updatedSubObject,
	} );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

const removeSettingsObjectSubField = ( state, action ) => {
	const originalObject = state[action.settings_key] || {};
	const originalSubObject = originalObject[action.key] || {};
	const updatedSubObject = Object.assign( {}, originalSubObject );
	delete updatedSubObject[action.subfield_key];
	const updatedObject = Object.assign( {}, originalObject, {
		[action.key]: updatedSubObject,
	} );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedObject,
	} );
};

export default function settings( state = {}, action ) {
	switch ( action.type ) {
		case UPDATE_SETTINGS_FIELD:
			return updateSettingField( state, action );
		case UPDATE_SETTINGS_ARRAY_FIELD:
			return updateSettingsArrayField( state, action );
		case ADD_SETTINGS_OBJECT_FIELD:
			return addSettingsObjectField( state, action );
		case REMOVE_SETTINGS_OBJECT_FIELD:
			return removeSettingsObjectField( state, action );
		case UPDATE_SETTINGS_OBJECT_SUB_FIELD:
			return updateSettingsObjectSubField( state, action );
		case REMOVE_SETTINGS_OBJECT_SUB_FIELD:
			return removeSettingsObjectSubField( state, action );
	}

	return state;
}
