import * as api from 'api';
import _ from 'lodash';
import * as NoticeActions from 'state/notices/actions';
import { translate as __ } from 'lib/mixins/i18n';

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

export const fetchSettings = () => ( dispatch, getState ) => {
	if ( getState().form.data || getState().form.meta.isFetching ) {
		return;
	}
	dispatch( setFormMetaProperty( 'isFetching', true ) );

	api.get( api.url.accountSettings() )
		.then( ( { formMeta, formData } ) => {
			// TODO: Create single action/reducer instead of looping
			_.forIn( formMeta, ( value, key ) => dispatch( setFormMetaProperty( key, value ) ) );
			_.forIn( formData, ( value, key ) => dispatch( setFormDataValue( key, value ) ) );
			dispatch( setFormMetaProperty( 'pristine', true ) );
		} )
		.catch( ( error ) => {
			console.error( error );
			dispatch( NoticeActions.errorNotice( __( 'Unable to get your settings. Please try again.' ) ) );
		} )
		.then( () => dispatch( setFormMetaProperty( 'isFetching', false ) ) );
};

export const submit = ( onSaveSuccess, onSaveFailure ) => ( dispatch, getState ) => {
	dispatch( setFormMetaProperty( 'isSaving', true ) );
	api.post( api.url.accountSettings(), getState().form.data )
		.then( onSaveSuccess )
		.catch( onSaveFailure )
		.then( () => dispatch( setFormMetaProperty( 'isSaving', false ) ) );
};
