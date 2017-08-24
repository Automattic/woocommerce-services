/**
 * Internal dependencies
 */
import * as api from 'api';

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

export const dismissNotice = () => {
	api.post( api.url.dismissSettingsNuxNotice() );
	return { type: DISMISS_NOTICE };
};
