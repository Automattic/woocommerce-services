import * as FormValueActions from 'state/form/values/actions';
import * as NoticeActions from 'state/notices/actions';
import { getStepFormErrors, getStepFormSuggestions } from 'state/selectors/errors';
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

export const goToStep = ( stepIndex ) => ( dispatch, getState, { formLayout } ) => {
	const stepLayout = formLayout[ stepIndex ] || {};

	// Clear the "bypass suggestion" flag if the user is coming *back* to this step
	if ( getState().form.currentStep > stepIndex && stepLayout.bypass_suggestion_flag ) {
		dispatch( FormValueActions.updateField( stepLayout.bypass_suggestion_flag, false ) );
	}

	dispatch( {
		type: GO_TO_STEP,
		step: stepIndex,
	} );
};

export const nextStep = () => ( dispatch, getState, { callbackURL, nonce, submitMethod, formSchema, formLayout } ) => {
	const stepLayout = formLayout[ getState().form.currentStep ] || {};
	const suggestions = getStepFormSuggestions( getState(), formSchema, formLayout );
	if ( ! isEmpty( suggestions ) ) {
		if ( getState().form.acceptSuggestion ) {
			Object.keys( suggestions ).forEach( ( fieldName ) => {
				dispatch( FormValueActions.updateField( fieldName, suggestions[ fieldName ] ) );
			} );
		} else {
			dispatch( FormValueActions.updateField( stepLayout.bypass_suggestion_flag, true ) );
		}
	}

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
			if ( ! isEmpty( getStepFormSuggestions( getState(), formSchema, formLayout ) ) ) {
				dispatch( setFormProperty( 'acceptSuggestion', true ) );
			}
			return;
		}

		if ( isManualAction && currentStepLayout.confirmation_flag ) {
			dispatch( FormValueActions.updateField( currentStepLayout.confirmation_flag, true ) );
		}

		if ( getState().form.pristine ) {
			dispatch( goToStep( currentStep + 1 ) );
			tryAutoAdvance( false );
			return;
		}

		submitForm( () => {
			dispatch( setFormProperty( 'acceptSuggestion', undefined ) );
			if ( getState().form.errors && ! isObject( getState().form.errors ) ) {
				dispatch( NoticeActions.errorNotice( getState().form.errors, {
					duration: 7000,
				} ) );
				return;
			}

			if ( ! isEmpty( getStepFormErrors( getState(), formSchema, formLayout ) ) ) {
				if ( ! isEmpty( getStepFormSuggestions( getState(), formSchema, formLayout ) ) ) {
					dispatch( setFormProperty( 'acceptSuggestion', true ) );
				}
				return;
			}

			dispatch( goToStep( currentStep + 1 ) );

			tryAutoAdvance( false );
		} );
	};

	tryAutoAdvance( true );
};
