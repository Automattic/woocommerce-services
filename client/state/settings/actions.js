export const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';
export const ADD_SETTINGS_OBJECT_FIELD = 'ADD_SETTINGS_OBJECT_FIELD';
export const REMOVE_SETTINGS_OBJECT_FIELD = 'REMOVE_SETTINGS_OBJECT_FIELD';
export const UPDATE_SETTINGS_OBJECT_SUB_FIELD = 'UPDATE_SETTINGS_OBJECT_SUB_FIELD';
export const REMOVE_SETTINGS_OBJECT_SUB_FIELD = 'REMOVE_SETTINGS_OBJECT_SUB_FIELD';
export const ADD_SETTINGS_ARRAY_FIELD_ITEM = 'ADD_SETTINGS_ARRAY_FIELD_ITEM';
export const REMOVE_SETTINGS_ARRAY_FIELD_ITEM = 'REMOVE_SETTINGS_ARRAY_FIELD_ITEM';

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

export const addSettingsArrayFieldItem = ( settings_key, item ) => ( {
	type: ADD_SETTINGS_ARRAY_FIELD_ITEM,
	settings_key,
	item,
} );

export const removeSettingsArrayFieldItem = ( settings_key, index ) => ( {
	type: REMOVE_SETTINGS_ARRAY_FIELD_ITEM,
	settings_key,
	index,
} );
