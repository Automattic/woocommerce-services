export const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';
export const UPDATE_SETTINGS_ARRAY_FIELD = 'UPDATE_SETTINGS_ARRAY_FIELD';

export function onFieldChange( event ) {
	return {
		type: UPDATE_SETTINGS_FIELD,
		key: event.target.name,
		value: ( 'checkbox' === event.target.type ) ? event.target.checked : event.target.value
	};
}

export function updateSettingsArrayField( array_key, id, key, value ) {
	return {
		type: UPDATE_SETTINGS_ARRAY_FIELD,
		array_key: array_key,
		id: id,
		key: key,
		value: value
	};
}
