/**
 * External dependencies
 */
import _ from 'lodash';
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import * as api from 'api';
// from Calypso
import { successNotice, errorNotice } from 'state/notices/actions';
import { PLUGIN_STATUS_DEBUG_TOGGLE, PLUGIN_STATUS_LOGGING_TOGGLE, SERVICE_DATA_REFRESH } from './action-types';

const saveSettings = () => ( dispatch, getState ) => {
	const state = getState().status;

	const data = {
		wcc_debug_on: state.debug_enabled,
		wcc_logging_on: state.logging_enabled,
	};

	api.post( api.url.selfHelp(), data )
		.then( () => dispatch( successNotice( translate( 'Your changes have been saved.' ), {
			duration: 5000,
		} ) ) )
		.catch( ( error ) => {
			if ( _.isString( error ) ) {
				dispatch( errorNotice( error ) );
			}

			if ( _.isObject( error ) ) {
				dispatch( errorNotice( translate( 'There was a problem when saving your preferences. Please try again.' ) ) );
			}
		} );
};

export const refreshServiceData = () => (dispatch) => {
	return api.post(api.url.refreshServiceData())
		.then(response => {
			const {success, ...data} = response;
			dispatch({
				type: SERVICE_DATA_REFRESH,
				value: data,
			})

			dispatch(successNotice(translate('Service data refreshed.')));
		})
		.catch(() => {
			dispatch(errorNotice(translate('An error occurred while refreshing service data.')))
		})
}

export const toggleLogging = ( value ) => (dispatch) => {
	dispatch({
		type: PLUGIN_STATUS_LOGGING_TOGGLE,
		value,
	});

	dispatch(saveSettings());
};

export const toggleDebugging = ( value ) => (dispatch) => {
	dispatch({
		type: PLUGIN_STATUS_DEBUG_TOGGLE,
		value,
	});

	dispatch(saveSettings());
};
