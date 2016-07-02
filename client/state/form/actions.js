import * as FormValueActions from 'state/form/values/actions';
import * as NoticeActions from 'state/notices/actions';
import { getStepFormErrors } from 'state/selectors/errors';
import saveForm from 'lib/save-form';
import isObject from 'lodash/isObject';
import isEmpty from 'lodash/isEmpty';

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

export const nextStep = () => ( dispatch, getState, { callbackURL, nonce, submitMethod, formSchema, formLayout } ) => {
	const submitForm = ( callback ) => {
		const setIsSaving = ( value ) => {
			dispatch( setFormProperty( 'isSaving', value ) );
			if ( false === value ) {
				callback();
			}
		};
		const setSuccess = ( value ) => dispatch( setFormProperty( 'success', value ) );
		const setError = ( value ) => dispatch( setFormProperty( 'errors', value ) );

		dispatch( setFormProperty( 'pristine', true ) );
		saveForm( setIsSaving, setSuccess, setError, callbackURL, nonce, submitMethod, getState().form.values );
	};

	const tryAutoAdvance = ( isManualAction ) => {
		const currentStep = getState().form.currentStep;
		const currentStepLayout = formLayout[ currentStep ] || {};

		if ( currentStep >= formLayout.length - 1 ) {
			return;
		}

		if ( ! isEmpty( getStepFormErrors( getState(), formSchema, formLayout ) ) ) {
			return;
		}

		if ( getState().form.pristine ) {
			dispatch( goToStep( currentStep + 1 ) );
			tryAutoAdvance( false );
			return;
		}

		submitForm( () => {
			if ( getState().form.errors && ! isObject( getState().form.errors ) ) {
				dispatch( NoticeActions.errorNotice( getState().form.errors, {
					duration: 7000,
				} ) );
				return;
			}

			if ( ! isEmpty( getStepFormErrors( getState(), formSchema, formLayout ) ) ) {
				return;
			}

			if ( isManualAction && currentStepLayout.confirmation_flag ) {
				dispatch( FormValueActions.updateField( currentStepLayout.confirmation_flag, true ) );
			}

			dispatch( goToStep( currentStep + 1 ) );

			tryAutoAdvance( false );
		} );
	};

	tryAutoAdvance( true );
};
