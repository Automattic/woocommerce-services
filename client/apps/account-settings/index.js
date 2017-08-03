/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import AccountSettingsRootView from './view';
import reducer, { initialState } from './state/reducer';
import { fetchSettings } from './state/actions';
// from calypso
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default ( { formData, formMeta, storeOptions } ) => ( {
	getReducer() {
		return combineReducers( {
			form: reducer,
			notices,
		} );
	},

	getHotReducer() {
		return combineReducers( {
			form: require( './state/reducer' ),
			notices,
		} );
	},

	getInitialState() {
		return {
			form: {
				data: formData,
				meta: { ...initialState.meta, ...formMeta },
				storeOptions,
			},
		};
	},

	getInitialAction() {
		return fetchSettings();
	},

	getStateForPersisting( state ) {
		delete state.notices;
		return state;
	},

	getStateKey() {
		return 'wcs-account-settings';
	},

	View: () => (
		<AccountSettingsRootView />
	),
} );
