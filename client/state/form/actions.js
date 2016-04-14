export const UPDATE_FORM_ELEMENT_FIELD = 'UPDATE_FORM_ELEMENT_FIELD';
export const SET_FIELD = 'SET_FIELD';

export const updateFormElementField = ( element, field, value ) => {
	return {
		type: UPDATE_FORM_ELEMENT_FIELD,
		element,
		field,
		value,
	};
};

export const setField = ( field, value ) => {
	return {
		type: SET_FIELD,
		field,
		value,
	};
};
