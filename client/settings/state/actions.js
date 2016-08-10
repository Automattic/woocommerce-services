import * as FormValueActions from './values/actions';
import * as NoticeActions from 'state/notices/actions';
import { getStepFormErrors, getStepFormSuggestions } from './selectors/errors';
import { bindActionCreators } from 'redux';
import { submitForm, autoAdvanceForm } from './auto-advance';

export const SET_FORM_PROPERTY = 'SET_FORM_PROPERTY';
export const GO_TO_STEP = 'GO_TO_STEP';

export const setFormProperty = ( field, value ) => {
	return {
		type: SET_FORM_PROPERTY,
		field,
		value,
	};
};

export const backFromSuggestion = () => ( dispatch ) => {
	dispatch( setFormProperty( 'fieldsStatus', {} ) );
	dispatch( setFormProperty( 'acceptSuggestion', undefined ) );
	dispatch( setFormProperty( 'pristine', false ) );
};

export const goToStep = ( stepIndex ) => ( dispatch, getState, { formLayout } ) => {
	const stepLayout = formLayout[ stepIndex ] || {};

	if ( getState().form.currentStep > stepIndex ) {
		dispatch( setFormProperty( 'acceptSuggestion', undefined ) );
		// Clear the "bypass suggestion" flag if the user is coming *back* to this step
		if ( stepLayout.bypass_suggestion_flag ) {
			dispatch( FormValueActions.updateField( stepLayout.bypass_suggestion_flag, false ) );
		}
	}

	dispatch( {
		type: GO_TO_STEP,
		step: stepIndex,
	} );
};

export const resetFlow = () => ( dispatch ) => {
	dispatch( setFormProperty( 'pristine', true ) );
	dispatch( goToStep( -1 ) );
};

export const nextStep = () => ( dispatch, getState, { callbackURL, nonce, submitMethod, formSchema, formLayout } ) => {
	const getCurrentStepLayout = () => formLayout[ getState().form.currentStep ] || {};
	const getFormState = () => getState().form;
	const updateField = bindActionCreators( FormValueActions.updateField, dispatch );

	autoAdvanceForm( {
		getStepFormErrors: () => getStepFormErrors( getState(), formSchema, formLayout ),
		getStepFormSuggestions: () => getStepFormSuggestions( getState(), formSchema, formLayout ),
		checkAcceptSuggestion: () => dispatch( setFormProperty( 'acceptSuggestion', true ) ),
		uncheckAcceptSuggestion: () => dispatch( setFormProperty( 'acceptSuggestion', undefined ) ),
		goToNextStep: () => dispatch( goToStep( getFormState().currentStep + 1 ) ),
		updateField: updateField,
		showErrorNotice: ( error ) => dispatch( NoticeActions.errorNotice( error, { duration: 7000 } ) ),
		getFormState: getFormState,
		submit: () => submitForm( callbackURL, nonce, submitMethod, getFormState().values, bindActionCreators( setFormProperty, dispatch ) ),
		setConfirmationFlag: () => getCurrentStepLayout().confirmation_flag && updateField( getCurrentStepLayout().confirmation_flag, true ),
		setBypassSuggestionFlag: () => updateField( getCurrentStepLayout().bypass_suggestion_flag, true ),
		isLastStep: () => getFormState().currentStep >= formLayout.length - 1,
	} );
};
