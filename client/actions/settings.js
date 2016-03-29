export const UPDATE_TEXT_FIELD = 'UPDATE_TEXT_FIELD';

export function onFieldChange( event ) {
	return {
		type: UPDATE_TEXT_FIELD,
		key: event.target.name,
		value: ( 'checkbox' === event.target.type ) ? event.target.checked : event.target.value
	};
};
