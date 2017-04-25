import saveForm from 'lib/save-form';
import _ from 'lodash';

// SET_FORM_DATA_VALUE is used to update a form field's underlying setting, e.g. selected_payment_method_id
export const SET_FORM_DATA_VALUE = 'SET_FORM_DATA_VALUE';

export const setFormDataValue = ( key, value ) => ( {
	type: SET_FORM_DATA_VALUE,
	key,
	value,
} );

// SET_FORM_META_PROPERTY is used to update the form state, e.g. isSaving or success
export const SET_FORM_META_PROPERTY = 'SET_FORM_META_PROPERTY';

export const setFormMetaProperty = ( key, value ) => {
	return {
		type: SET_FORM_META_PROPERTY,
		key,
		value,
	};
};

export const SAVE_FORM = 'SAVE_FORM';

// The callbackURL, nonce and submitMethod are extracted from wcConnectData
// courtesy thunk.withExtraArgument in main.js
export const submit = ( onSaveSuccess, onSaveFailure ) => ( dispatch, getState, { callbackURL, nonce } ) => {
	dispatch( setFormMetaProperty( 'isSaving', true ) );
	const setError = ( error ) => {
		if ( error && 'rest_cookie_invalid_nonce' !== error ) {
			onSaveFailure();
		}
	};
	const setSuccess = ( success ) => {
		if ( success ) {
			onSaveSuccess();
		}
	};
	const setIsSaving = ( saving ) => {
		dispatch( setFormMetaProperty( 'isSaving', saving ) );
	};
	saveForm( setIsSaving, setSuccess, _.noop, setError, callbackURL, nonce, 'POST', getState().form.data );
};
