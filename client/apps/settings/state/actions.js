/**
 * External dependencies
 */
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import * as api from 'api';
import * as NoticeActions from 'state/notices/actions';
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

export const setFormProperty = ( field, value ) => {
	return {
		type: SET_FORM_PROPERTY,
		field,
		value,
	};
};

export const fetchForm = () => ( dispatch, getState, { methodId, instanceId, formType } ) => {
	dispatch( setFormProperty( 'isFetching', true ) );
	return api.get( api.url.settingsForm( methodId, instanceId, formType ) )
		.then( ( response ) => {
			dispatch( initForm( response ) );
		} )
		.catch( () => {
			NoticeActions.errorNotice( __( 'Error while loading the settings. Please refresh the page to try again.' ) );
			dispatch( setFormProperty( 'fetchError', false ) );
		} )
		.then( () => {
			dispatch( setFormProperty( 'isFetching', false ) );
		} );
};

export const setAllPristine = ( pristineValue ) => ( {
	type: SET_ALL_PRISTINE,
	pristineValue,
} );

export const dismissNotice = () => {
	api.post( api.url.dismissShippingSettingsNuxNotice() );
	return { type: DISMISS_NOTICE };
};
