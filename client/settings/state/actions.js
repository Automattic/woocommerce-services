import saveForm from 'lib/save-form';
import _ from 'lodash';

export const SET_FORM_PROPERTY = 'SET_FORM_PROPERTY';
export const SET_ALL_PRISTINE = 'SET_ALL_PRISTINE';
export const DISMISS_NOTICE = 'DISMISS_NOTICE';

export const setFormProperty = ( field, value ) => {
	return {
		type: SET_FORM_PROPERTY,
		field,
		value,
	};
};

export const setAllPristine = ( pristineValue ) => ( {
	type: SET_ALL_PRISTINE,
	pristineValue,
} );

export const dismissNotice = () => ( dispatch, getState, { dismissURL, nonce } ) => {
	dispatch( { type: DISMISS_NOTICE } );
	saveForm( _.noop, _.noop, _.noop, _.noop, dismissURL, nonce, 'POST' );
};
