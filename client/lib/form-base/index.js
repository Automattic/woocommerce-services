import coerceFormValues from 'lib/utils/coerce-values';
import * as FormActions from './actions';
import * as NoticeActions from 'state/notices/actions';

export const submit = ( schema, silent ) => ( dispatch, getState, { callbackURL, nonce, submitMethod } ) => {
	silent = ( true === silent );

	const setIsSaving = ( value ) => dispatch( FormActions.setFormMetaProperty( 'isSaving', value ) );

	const setSuccess = ( value ) => {
		dispatch( FormActions.setFormMetaProperty( 'pristine', value ) );
		if ( ! silent && true === value ) {
			dispatch( NoticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 2250,
			} ) );
		}
	};

	const setFieldsStatus = ( value ) => {
		dispatch( FormActions.setFormMetaProperty( 'fieldsStatus', value ) );

		if ( ! silent ) {
			dispatch( NoticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ), {
				duration: 7000,
			} ) );
		}
	};

	const setError = ( value ) => {
		dispatch( FormActions.setFormMetaProperty( 'error', value ) );

		if ( ! silent ) {
			if ( isString( value ) ) {
				dispatch( NoticeActions.errorNotice( value, {
					duration: 7000,
				} ) );
			}

			if ( isObject( value ) ) {
				dispatch( NoticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ), {
					duration: 7000,
				} ) );
			}
		}
	};

	const coercedValues = coerceFormValues( schema, getState().data );

	saveForm( setIsSaving, setSuccess, setFieldsStatus, setError, callbackURL, nonce, submitMethod, coercedValues );
};
