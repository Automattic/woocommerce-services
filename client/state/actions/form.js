export const SET_FORM_STATE = 'SET_FORM_STATE';

export const setFormState = ( stateName ) => {
	return {
		type: SET_FORM_STATE,
		value: stateName,
	};
};
