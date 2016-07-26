import saveForm from 'lib/save-form';
import isObject from 'lodash/isObject';
import isEmpty from 'lodash/isEmpty';

export const submitForm = ( callbackURL, nonce, submitMethod, formValues, setFormProperty ) => {
	return new Promise( ( resolve ) => {
		const setIsSaving = ( value ) => {
			setFormProperty( 'isSaving', value );
			if ( false === value ) {
				resolve();
			}
		};
		const setSuccess = ( value ) => setFormProperty( 'success', value );
		const setError = ( value ) => setFormProperty( 'errors', value );

		setFormProperty( 'pristine', true );
		saveForm( setIsSaving, setSuccess, setError, callbackURL, nonce, submitMethod, formValues );
	} );
};

export const autoAdvanceForm = ( {
	getStepFormErrors,
	getStepFormSuggestions,
	checkAcceptSuggestion,
	uncheckAcceptSuggestion,
	goToNextStep,
	updateField,
	showErrorNotice,
	getFormState,
	submit,
	setConfirmationFlag,
	setBypassSuggestionFlag,
	isLastStep,
} ) => {
	const suggestions = getStepFormSuggestions();
	if ( ! isEmpty( suggestions ) ) {
		if ( getFormState().acceptSuggestion ) {
			Object.keys( suggestions ).forEach( ( fieldName ) => {
				updateField( fieldName, suggestions[ fieldName ] );
			} );
		} else {
			setBypassSuggestionFlag();
		}
	}

	const tryAutoAdvance = ( isManualAction ) => {
		if ( isLastStep() ) {
			return;
		}

		if ( ! isEmpty( getStepFormErrors() ) ) {
			if ( ! isEmpty( getStepFormSuggestions() ) ) {
				checkAcceptSuggestion();
			}
			return;
		}

		if ( isManualAction ) {
			setConfirmationFlag();
		}

		if ( getFormState().pristine ) {
			goToNextStep();
			tryAutoAdvance( false );
			return;
		}

		submit().then( () => {
			uncheckAcceptSuggestion();
			if ( getFormState().errors && ! isObject( getFormState().errors ) ) {
				showErrorNotice( getFormState().errors );
				return;
			}

			if ( ! isEmpty( getStepFormErrors() ) ) {
				if ( ! isEmpty( getStepFormSuggestions() ) ) {
					checkAcceptSuggestion();
				}
				return;
			}

			goToNextStep();
			tryAutoAdvance( false );
		} );
	};

	tryAutoAdvance( true );
};
