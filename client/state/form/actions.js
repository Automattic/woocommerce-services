export const SET_FORM_PROPERTY = 'SET_FORM_PROPERTY';

export const setFormProperty = ( field, value ) => {
	return {
		type: SET_FORM_PROPERTY,
		field,
		value,
	};
};
