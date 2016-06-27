import * as SettingsActions from 'state/settings/actions';
import { getStepFormErrors } from 'state/selectors';

export const SET_FORM_PROPERTY = 'SET_FORM_PROPERTY';
export const GO_TO_STEP = 'GO_TO_STEP';

export const setFormProperty = ( field, value ) => {
	return {
		type: SET_FORM_PROPERTY,
		field,
		value,
	};
};

export const goToStep = ( stepIndex ) => {
	return {
		type: GO_TO_STEP,
		step: stepIndex,
	};
};

export const nextStep = () => ( dispatch, getState, { formSchema, formLayout } ) => {
	const tryAutoAdvance = ( isManualAction ) => {
		const currentStep = getState().form.currentStep;
		const currentStepLayout = formLayout[ currentStep ] || {};

		if ( getStepFormErrors( getState(), formSchema, formLayout ).length ) {
			return;
		}

		if ( isManualAction && currentStepLayout.confirmation_flag ) {
			dispatch( SettingsActions.updateSettingsField( currentStepLayout.confirmation_flag, true ) );
		}

		dispatch( goToStep( currentStep + 1 ) );

		tryAutoAdvance( false );
	};

	tryAutoAdvance( true );
};
