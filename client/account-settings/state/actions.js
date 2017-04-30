import * as api from 'api';

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

export const submit = ( onSaveSuccess, onSaveFailure ) => ( dispatch, getState ) => {
	dispatch( setFormMetaProperty( 'isSaving', true ) );
	api.post( api.url.saveAccountSettings(), getState().form.data )
		.then( onSaveSuccess )
		.catch( ( error ) => {
			if ( error && 'rest_cookie_invalid_nonce' !== error ) {
				onSaveFailure();
			}
		} )
		.then( () => dispatch( setFormMetaProperty( 'isSaving', false ) ) );
};
