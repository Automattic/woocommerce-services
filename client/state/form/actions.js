import * as SettingsActions from 'state/settings/actions';
import * as FormActions from 'state/form/actions';
import * as NoticeActions from 'state/notices/actions';
import { getStepFormErrors } from 'state/selectors';
import saveForm from 'lib/save-form';
import isArray from 'lodash/isArray';

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

export const nextStep = () => ( dispatch, getState, { callbackURL, nonce, formSchema, formLayout } ) => {
	const submitForm = ( callback ) => {
		const setIsSaving = ( value ) => {
			dispatch( FormActions.setFormProperty( 'isSaving', value ) );
			if ( false === value ) {
				callback();
			}
		};
		const setSuccess = ( value ) => dispatch( FormActions.setFormProperty( 'success', value ) );
		const setError = ( value ) => dispatch( FormActions.setFormProperty( 'errors', value ) );

		dispatch( FormActions.setFormProperty( 'pristine', true ) );
		saveForm( setIsSaving, setSuccess, setError, callbackURL, nonce, getState().settings );
	};

	const tryAutoAdvance = ( isManualAction ) => {
		const currentStep = getState().form.currentStep;
		const currentStepLayout = formLayout[ currentStep ] || {};

		if ( currentStep >= formLayout.length - 1 ) {
			return;
		}

		if ( getStepFormErrors( getState(), formSchema, formLayout ).length ) {
			return;
		}

		if ( getState().form.pristine ) {
			dispatch( goToStep( currentStep + 1 ) );
			tryAutoAdvance( false );
			return;
		}

		submitForm( () => {
			if ( getState().form.errors && ! isArray( getState().form.errors ) ) {
				dispatch( NoticeActions.errorNotice( getState().form.errors, {
					duration: 7000,
				} ) );
				return;
			}

			if ( getStepFormErrors( getState(), formSchema, formLayout ).length ) {
				return;
			}

			if ( isManualAction && currentStepLayout.confirmation_flag ) {
				dispatch( SettingsActions.updateSettingsField( currentStepLayout.confirmation_flag, true ) );
			}

			dispatch( goToStep( currentStep + 1 ) );

			tryAutoAdvance( false );
		} );
	};

	tryAutoAdvance( true );
};
