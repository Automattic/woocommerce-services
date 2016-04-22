import {
	UPDATE_SETTINGS_FIELD,
	ADD_SETTINGS_OBJECT_FIELD,
	REMOVE_SETTINGS_OBJECT_FIELD,
	UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	REMOVE_SETTINGS_OBJECT_SUB_FIELD,
	ADD_SETTINGS_ARRAY_FIELD_ITEM,
	REMOVE_SETTINGS_ARRAY_FIELD_ITEM,
} from './actions';
import { cloneDeep } from 'lodash';

const updateSettingField = ( state, action ) => {
	return Object.assign( {}, state, {
		[action.key]: action.value,
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

const addSettingsArrayFieldItem = ( state, action ) => {
	const originalArray = state[action.settings_key] || [];
	const updatedArray = cloneDeep( originalArray );
	updatedArray.push( action.item );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedArray,
	} );
}

const removeSettingsArrayFieldItem = ( state, action ) => {
	const originalArray = state[action.settings_key] || [];
	const updatedArray = originalArray.filter( ( item, idx ) => ( idx !== action.index ) );

	return Object.assign( {}, state, {
		[action.settings_key]: updatedArray,
	} );
}

const settings = ( state = {}, action ) => {
	switch ( action.type ) {
		case UPDATE_SETTINGS_FIELD:
			return updateSettingField( state, action );
		case ADD_SETTINGS_OBJECT_FIELD:
			return addSettingsObjectField( state, action );
		case REMOVE_SETTINGS_OBJECT_FIELD:
			return removeSettingsObjectField( state, action );
		case UPDATE_SETTINGS_OBJECT_SUB_FIELD:
			return updateSettingsObjectSubField( state, action );
		case REMOVE_SETTINGS_OBJECT_SUB_FIELD:
			return removeSettingsObjectSubField( state, action );
		case ADD_SETTINGS_ARRAY_FIELD_ITEM:
			return addSettingsArrayFieldItem( state, action );
		case REMOVE_SETTINGS_ARRAY_FIELD_ITEM:
			return removeSettingsArrayFieldItem( state, action );
	}

	return state;
};

export default settings;
