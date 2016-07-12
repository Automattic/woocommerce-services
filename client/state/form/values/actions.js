import saveForm from 'lib/save-form';
import coerceFormValues from 'lib/utils/coerce-values';
import isString from 'lodash/isString';
import isArray from 'lodash/isArray';
import { translate as __ } from 'lib/mixins/i18n';
import * as FormActions from 'state/form/actions';
import * as NoticeActions from 'state/notices/actions';

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

export const submit = ( schema, silent ) => ( dispatch, getState, { callbackURL, nonce, submitMethod } ) => {
	silent = ( true === silent );
	const setIsSaving = ( value ) => dispatch( FormActions.setFormProperty( 'isSaving', value ) );
	const setSuccess = ( value ) => {
		dispatch( FormActions.setFormProperty( 'success', value ) );
		if ( ! silent && true === value ) {
			dispatch( NoticeActions.successNotice( __( 'Your changes have been saved.' ), {
				duration: 2250,
			} ) );
		}
	};
	const setError = ( value ) => {
		dispatch( FormActions.setFormProperty( 'errors', value ) );

		if ( ! silent ) {
			if ( isString( value ) ) {
				dispatch( NoticeActions.errorNotice( value, {
					duration: 7000,
				} ) );
			}

			if ( isArray( value ) ) {
				dispatch( NoticeActions.errorNotice( __( 'There was a problem with one or more entries. Please fix the errors below and try saving again.' ), {
					duration: 7000,
				} ) );
			}
		}
	};
	const coercedValues = coerceFormValues( schema, getState().form.values );

	saveForm( setIsSaving, setSuccess, setError, callbackURL, nonce, submitMethod, coercedValues );
};
