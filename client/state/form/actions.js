export const SET_FORM_PROPERTY = 'SET_FORM_PROPERTY';
export const NEXT_STEP = 'NEXT_STEP';
export const GO_TO_STEP = 'GO_TO_STEP';

export const setFormProperty = ( field, value ) => {
	return {
		type: SET_FORM_PROPERTY,
		field,
		value,
	};
};

export const nextStep = ( ) => {
	return {
		type: NEXT_STEP,
	};
};

export const goToStep = ( stepIndex ) => {
	return {
		type: GO_TO_STEP,
		step: stepIndex,
	};
};
