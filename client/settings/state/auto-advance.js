import saveForm from 'lib/save-form';
import isEmpty from 'lodash/isEmpty';

export const submitForm = ( callbackURL, nonce, submitMethod, formValues, setFormProperty ) => {
	return new Promise( ( resolve ) => {
		const setIsSaving = ( value ) => {
			setFormProperty( 'isSaving', value );
			if ( false === value ) {
				resolve();
			}
		};
		const setSuccess = ( success, response ) => {
			if ( success ) {
				setFormProperty( 'response', response );
			}
			setFormProperty( 'success', success );
		};
		const setFieldsOptions = ( value ) => setFormProperty( 'fieldsOptions', value );
		const setFieldsStatus = ( value ) => setFormProperty( 'fieldsStatus', value );
		const setError = ( value ) => setFormProperty( 'error', value );

		setFormProperty( 'pristine', true );
		saveForm( setIsSaving, setSuccess, setFieldsOptions, setFieldsStatus, setError, callbackURL, nonce, submitMethod, formValues );
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
			if ( ! isLastStep() ) {
				goToNextStep();
				tryAutoAdvance( false );
			}
			return;
		}

		submit().then( () => {
			uncheckAcceptSuggestion();
			if ( getFormState().error ) {
				showErrorNotice( getFormState().error );
				return;
			}

			if ( ! isEmpty( getStepFormErrors() ) ) {
				if ( ! isEmpty( getStepFormSuggestions() ) ) {
					checkAcceptSuggestion();
				}
				return;
			}

			if ( isLastStep() ) {
				return;
			}

			goToNextStep();
			tryAutoAdvance( false );
		} );
	};

	tryAutoAdvance( true );
};
