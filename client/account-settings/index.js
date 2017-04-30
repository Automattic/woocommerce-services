import React from 'react';
import { combineReducers } from 'redux';
import AccountSettingsRootView from './views';
import reducer, { initialState } from './state/reducer';
import { fetchSettings } from './state/actions';
// from calypso
import notices from 'state/notices/reducer';

export default () => ( {
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
		return { form: initialState };
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
