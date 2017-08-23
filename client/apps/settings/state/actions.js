/**
 * External dependencies
 */
import _ from 'lodash';

/**
 * Internal dependencies
 */
import * as api from 'api';
import saveForm from 'lib/save-form';

export const INIT_FORM = 'INIT_FORM';
export const SET_FORM_PROPERTY = 'SET_FORM_PROPERTY';
export const SET_ALL_PRISTINE = 'SET_ALL_PRISTINE';
export const DISMISS_NOTICE = 'DISMISS_NOTICE';

const initForm = ( form ) => {
	return {
		type: INIT_FORM,
		...form,
	};
};

export const fetchForm = () => ( dispatch, getState, { methodId, instanceId } ) => {
	return api.get( api.url.shippingMethod( methodId, instanceId ) )
		.then( ( response ) => {
			dispatch( initForm( response ) );
		} )
		.catch( () => {
			//TODO: handle error
		} );
};

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
