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

export const PLUGIN_STATUS_DEBUG_TOGGLE = 'PLUGIN_STATUS_DEBUG_TOGGLE';
export const PLUGIN_STATUS_LOGGING_TOGGLE = 'PLUGIN_STATUS_LOGGING_TOGGLE';

export const toggleLogging = ( value ) => ( {
	type: PLUGIN_STATUS_LOGGING_TOGGLE,
	value,
} );

export const toggleDebug = ( value ) => ( {
	type: PLUGIN_STATUS_DEBUG_TOGGLE,
	value,
} );

export const save = () => ( dispatch, getState ) => {
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
