export const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';
export const UPDATE_SETTINGS_ARRAY_FIELD = 'UPDATE_SETTINGS_ARRAY_FIELD';
export const ADD_SETTINGS_OBJECT_FIELD = 'ADD_SETTINGS_OBJECT_FIELD';
export const REMOVE_SETTINGS_OBJECT_FIELD = 'REMOVE_SETTINGS_OBJECT_FIELD';
export const UPDATE_SETTINGS_OBJECT_SUB_FIELD = 'UPDATE_SETTINGS_OBJECT_SUB_FIELD';
export const REMOVE_SETTINGS_OBJECT_SUB_FIELD = 'REMOVE_SETTINGS_OBJECT_SUB_FIELD';
export const SET_FORM_STATE = 'SET_FORM_STATE';

export function updateSettingsField( key, value ) {
	return {
		type: UPDATE_SETTINGS_FIELD,
		key: key,
		value: value,
	};
}

export function updateSettingsArrayField( array_key, id, key, value ) {
	return {
		type: UPDATE_SETTINGS_ARRAY_FIELD,
		array_key: array_key,
		id: id,
		key: key,
		value: value,
	};
}

export function addSettingsObjectField( settings_key, key, object ) {
	return {
		type: ADD_SETTINGS_OBJECT_FIELD,
		settings_key,
		key,
		object,
	};
}

export function removeSettingsObjectField( settings_key, key ) {
	return {
		type: REMOVE_SETTINGS_OBJECT_FIELD,
		settings_key,
		key,
	};
}

export function updateSettingsObjectSubField( settings_key, key, subfield_key, value ) {
	return {
		type: UPDATE_SETTINGS_OBJECT_SUB_FIELD,
		settings_key,
		key,
		subfield_key,
		value,
	};
}

export function removeSettingsObjectSubField( settings_key, key, subfield_key ) {
	return {
		type: REMOVE_SETTINGS_OBJECT_SUB_FIELD,
		settings_key,
		key,
		subfield_key,
	};
}

export const setFormState = ( stateName ) => {
	return {
		type: SET_FORM_STATE,
		value: stateName,
	};
};
