const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';
const ADD_SETTINGS_OBJECT_FIELD = 'ADD_SETTINGS_OBJECT_FIELD';
const REMOVE_SETTINGS_OBJECT_FIELD = 'REMOVE_SETTINGS_OBJECT_FIELD';
const UPDATE_SETTINGS_OBJECT_SUB_FIELD = 'UPDATE_SETTINGS_OBJECT_SUB_FIELD';
const REMOVE_SETTINGS_OBJECT_SUB_FIELD = 'REMOVE_SETTINGS_OBJECT_SUB_FIELD';

const updateSettingsField = ( key, value ) => ( {
	type: UPDATE_SETTINGS_FIELD,
	key,
	value,
} );

const addSettingsObjectField = ( settings_key, key, object ) => ( {
	type: ADD_SETTINGS_OBJECT_FIELD,
	settings_key,
	key,
	object,
} );

const removeSettingsObjectField = ( settings_key, key ) => ( {
	type: REMOVE_SETTINGS_OBJECT_FIELD,
	settings_key,
	key,
} );

const updateSettingsObjectSubField = ( settings_key, key, subfield_key, value ) => ( {
	type: UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	settings_key,
	key,
	subfield_key,
	value,
} );

const removeSettingsObjectSubField = ( settings_key, key, subfield_key ) => ( {
	type: REMOVE_SETTINGS_OBJECT_SUB_FIELD,
	settings_key,
	key,
	subfield_key,
} );

export default {
	UPDATE_SETTINGS_FIELD,
	ADD_SETTINGS_OBJECT_FIELD,
	REMOVE_SETTINGS_OBJECT_FIELD,
	UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	REMOVE_SETTINGS_OBJECT_SUB_FIELD,
	updateSettingsField,
	addSettingsObjectField,
	removeSettingsObjectField,
	updateSettingsObjectSubField,
	removeSettingsObjectSubField,
};
