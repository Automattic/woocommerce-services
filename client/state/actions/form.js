export const UPDATE_FORM_ELEMENT_FIELD = 'UPDATE_FORM_ELEMENT_FIELD';

export const updateFormElementField = ( element, field, value ) => {
	return {
		type: UPDATE_FORM_ELEMENT_FIELD,
		element,
		field,
		value,
	};
};
