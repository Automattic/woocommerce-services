export const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';
export const ADD_SETTINGS_OBJECT_FIELD = 'ADD_SETTINGS_OBJECT_FIELD';
export const REMOVE_SETTINGS_OBJECT_FIELD = 'REMOVE_SETTINGS_OBJECT_FIELD';
export const UPDATE_SETTINGS_OBJECT_SUB_FIELD = 'UPDATE_SETTINGS_OBJECT_SUB_FIELD';
export const REMOVE_SETTINGS_OBJECT_SUB_FIELD = 'REMOVE_SETTINGS_OBJECT_SUB_FIELD';

export const updateSettingsField = ( key, value ) => ( {
	type: UPDATE_SETTINGS_FIELD,
	key,
	value,
} );

export const addSettingsObjectField = ( settings_key, key, object ) => ( {
	type: ADD_SETTINGS_OBJECT_FIELD,
	settings_key,
	key,
	object,
} );

export const removeSettingsObjectField = ( settings_key, key ) => ( {
	type: REMOVE_SETTINGS_OBJECT_FIELD,
	settings_key,
	key,
} );

export const updateSettingsObjectSubField = ( settings_key, key, subfield_key, value ) => ( {
	type: UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	settings_key,
	key,
	subfield_key,
	value,
} );

export const removeSettingsObjectSubField = ( settings_key, key, subfield_key ) => ( {
	type: REMOVE_SETTINGS_OBJECT_SUB_FIELD,
	settings_key,
	key,
	subfield_key,
} );
