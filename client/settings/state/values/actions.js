import saveForm from 'lib/save-form';
import coerceFormValues from 'lib/utils/coerce-values';
import _ from 'lodash';
import { translate as __ } from 'lib/mixins/i18n';
import * as FormActions from '../actions';
import * as NoticeActions from 'state/notices/actions';
import getFormErrors from 'settings/state/selectors/errors';

export const UPDATE_FIELD = 'UPDATE_FIELD';
export const REMOVE_FIELD = 'REMOVE_FIELD';
export const ADD_ARRAY_FIELD_ITEM = 'ADD_ARRAY_FIELD_ITEM';

export const updateField = ( path, value ) => ( {
	type: UPDATE_FIELD,
	path,
	value,
} );

export const removeField = ( path ) => ( {
	type: REMOVE_FIELD,
	path,
} );

export const addArrayFieldItem = ( path, item ) => ( {
	type: ADD_ARRAY_FIELD_ITEM,
	path,
	item,
} );

export const submit = ( schema, silent ) => ( dispatch, getState, { callbackURL, nonce } ) => {
	silent = ( true === silent );

	const setIsSaving = ( value ) => dispatch( FormActions.setFormProperty( 'isSaving', value ) );

	const setSuccess = ( value ) => {
		dispatch( FormActions.setFormProperty( 'success', value ) );
		if ( ! silent && true === value ) {
			dispatch( NoticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 5000,
			} ) );
		}
	};

	const setFieldsStatus = ( value ) => {
		dispatch( FormActions.setFormProperty( 'fieldsStatus', value ) );

		if ( ! silent ) {
			dispatch( NoticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ) ) );
		}
	};

	const setError = ( value ) => {
		dispatch( FormActions.setFormProperty( 'error', value ) );

		if ( 'rest_cookie_invalid_nonce' !== value && ! silent ) {
			if ( _.isString( value ) ) {
				dispatch( NoticeActions.errorNotice( value ) );
			}

			if ( _.isObject( value ) ) {
				dispatch( NoticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ) ) );
			}
		}
	};

	const coercedValues = coerceFormValues( schema, getState().form.values );

	// Trigger a client-side validation before hitting the server
	dispatch( FormActions.setAllPristine( false ) );

	const errors = getFormErrors( getState(), schema );

	if ( _.isEmpty( errors ) ) {
		saveForm( setIsSaving, setSuccess, setFieldsStatus, setError, callbackURL, nonce, 'POST', coercedValues );
	} else {
		setError( errors );
	}
};
