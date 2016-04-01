const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';

export function onFieldChange( event ) {
	return {
		type: UPDATE_SETTINGS_FIELD,
		key: event.target.name,
		value: ( 'checkbox' === event.target.type ) ? event.target.checked : event.target.value
	};
};
