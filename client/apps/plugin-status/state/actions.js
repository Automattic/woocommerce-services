/**
 * External dependencies
 */
import _ from 'lodash';
import { translate } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import * as api from 'api';
import * as NoticeActions from 'state/notices/actions';

export const PLUGIN_STATUS_SET_DEBUG = 'PLUGIN_STATUS_SET_DEBUG';
export const PLUGIN_STATUS_SET_LOGGING = 'PLUGIN_STATUS_SET_LOGGING';
export const PLUGIN_STATUS_REST_REQUEST = 'PLUGIN_STATUS_REST_REQUEST';
export const PLUGIN_STATUS_REST_RESPONSE = 'PLUGIN_STATUS_REST_RESPONSE';

export const setLogging = ( value, saving = true ) => ( {
	type: PLUGIN_STATUS_SET_LOGGING,
	value,
	saving,
} );

export const setDebug = ( value, saving = true ) => ( {
	type: PLUGIN_STATUS_SET_DEBUG,
	value,
	saving,
} );

export const save = () => ( dispatch, getState ) => {
	const state = getState().status;

	const data = {
		wcc_debug_on: state.debug_enabled,
		wcc_logging_on: state.logging_enabled,
	};

	api.post( api.url.selfHelp(), data )
		.then( () => {
			dispatch( NoticeActions.successNotice( translate( 'Your changes have been saved.' ), {
				duration: 5000,
			} ) );

			if ( state.debug_saving ) {
				dispatch( setDebug( state.debug_enabled, false ) );
			}
			if ( state.logging_saving ) {
				dispatch( setLogging( state.logging_enabled, false ) );
			}
		} )
		.catch( ( error ) => {
			if ( _.isString( error ) ) {
				dispatch( NoticeActions.errorNotice( error ) );
			} else if ( _.isObject( error ) ) {
				dispatch( NoticeActions.errorNotice( translate( 'There was a problem when saving your preferences. Please try again.' ) ) );
			}

			if ( state.debug_saving ) {
				dispatch( setDebug( ! state.debug_enabled, false ) );
			}
			if ( state.logging_saving ) {
				dispatch( setLogging( ! state.logging_enabled, false ) );
			}
		} );
};

export const checkRestApi = () => ( dispatch, getState ) => {
	dispatch( { type: PLUGIN_STATUS_REST_REQUEST } );

	const state = getState().status;

	const data = {
		wcc_debug_on: state.debug_enabled,
		wcc_logging_on: state.logging_enabled,
	};

	api.post( api.url.selfHelp(), data )
		.then( () => {
			dispatch( {
				type: PLUGIN_STATUS_REST_RESPONSE,
				success: true,
				message: translate( 'WooCommerce REST API is working properly' ),
			} );
		} )
		.catch( () => {
			dispatch( {
				type: PLUGIN_STATUS_REST_RESPONSE,
				success: false,
				message: translate( 'Encountered an issue with the WooCommerce REST API' ),
			} );
		} );
};
