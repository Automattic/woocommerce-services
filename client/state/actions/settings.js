const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';

export function onFieldChange( event ) {
	return {
		type: UPDATE_SETTINGS_FIELD,
		key: event.target.name,
		value: ( 'checkbox' === event.target.type ) ? event.target.checked : event.target.value
	};
}

export const TOGGLE_SERVICE = 'TOGGLE_SERVICE';

export const toggleService = ( id ) => {
	console.log( 'in actions/settings.js TOGGLE_SERVICE' );
	return {
		type: TOGGLE_SERVICE,
		id: id
	};
};
