/**
 * External dependencies
 */
import React from 'react';
import { combineReducers } from 'redux';

/**
 * Internal dependencies
 */
import initializeState from 'lib/initialize-state';
import SettingsView from './views';
import form from './state/reducer';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, formSchema, formLayout, storeOptions, noticeDismissed, methodId, instanceId } ) => ( {
	getReducer() {
		return combineReducers( {
			form,
			notices,
		} );
	},

	getHotReducer() {
		return combineReducers( {
			form: require( './state/reducer' ).default,
			notices,
		} );
	},

	getInitialState() {
		return initializeState( formSchema, formData, noticeDismissed );
	},

	getStateForPersisting( state ) {
		delete state.notices;
		return state;
	},

	getStateKey() {
		return `wcs-settings-${ methodId }-${ instanceId }`;
	},

	View: () => (
		<SettingsView
			storeOptions={ storeOptions }
			schema={ formSchema }
			layout={ formLayout } />
	),
} );
