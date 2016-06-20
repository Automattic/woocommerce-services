import saveForm from 'lib/save-form';
import isString from 'lodash/isString';
import isArray from 'lodash/isArray';
import { translate as __ } from 'lib/mixins/i18n';
import * as FormActions from 'state/form/actions';
import * as NoticeActions from 'state/notices/actions';

export const UPDATE_SETTINGS_FIELD = 'UPDATE_SETTINGS_FIELD';
export const ADD_SETTINGS_OBJECT_FIELD = 'ADD_SETTINGS_OBJECT_FIELD';
export const REMOVE_SETTINGS_OBJECT_FIELD = 'REMOVE_SETTINGS_OBJECT_FIELD';
export const UPDATE_SETTINGS_OBJECT_SUB_FIELD = 'UPDATE_SETTINGS_OBJECT_SUB_FIELD';
export const REMOVE_SETTINGS_OBJECT_SUB_FIELD = 'REMOVE_SETTINGS_OBJECT_SUB_FIELD';
export const ADD_SETTINGS_ARRAY_FIELD_ITEM = 'ADD_SETTINGS_ARRAY_FIELD_ITEM';
export const REMOVE_SETTINGS_ARRAY_FIELD_ITEM = 'REMOVE_SETTINGS_ARRAY_FIELD_ITEM';
export const UPDATE_SETTINGS_ARRAY_FIELD_ITEM = 'UPDATE_SETTINGS_ARRAY_FIELD_ITEM';

export const updateSettingsField = ( key, value ) => ( {
	type: UPDATE_SETTINGS_FIELD,
	key,
	value,
} );

export const addSettingsObjectField = ( settings_key, key, object ) => ( {
	type: ADD_SETTINGS_OBJECT_FIELD,
	settings_key,
	key,
	object,
} );

export const removeSettingsObjectField = ( settings_key, key ) => ( {
	type: REMOVE_SETTINGS_OBJECT_FIELD,
	settings_key,
	key,
} );

export const updateSettingsObjectSubField = ( settings_key, key, subfield_key, value ) => ( {
	type: UPDATE_SETTINGS_OBJECT_SUB_FIELD,
	settings_key,
	key,
	subfield_key,
	value,
} );

export const removeSettingsObjectSubField = ( settings_key, key, subfield_key ) => ( {
	type: REMOVE_SETTINGS_OBJECT_SUB_FIELD,
	settings_key,
	key,
	subfield_key,
} );

export const addSettingsArrayFieldItem = ( settings_key, item ) => ( {
	type: ADD_SETTINGS_ARRAY_FIELD_ITEM,
	settings_key,
	item,
} );

export const removeSettingsArrayFieldItem = ( settings_key, index ) => ( {
	type: REMOVE_SETTINGS_ARRAY_FIELD_ITEM,
	settings_key,
	index,
} );

export const updateSettingsArrayFieldItem = ( settings_key, index, item ) => ( {
	type: UPDATE_SETTINGS_ARRAY_FIELD_ITEM,
	settings_key,
	index,
	item,
} );

export const submit = ( silent ) => ( dispatch, getState, { callbackURL, nonce } ) => {
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

	saveForm( setIsSaving, setSuccess, setError, callbackURL, nonce, getState().settings );
};
