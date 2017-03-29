import React from 'react';
import { combineReducers } from 'redux';
import AccountSettingsRootView from './views';
import reducer from './state/reducer';
// from calypso
import notices from 'state/notices/reducer';

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
		const initialMeta = require( './state/reducer' ).initialState.meta;
		const combinedMeta = Object.assign( {}, initialMeta, formMeta );

		return {
			form: {
				data: formData,
				meta: combinedMeta,
			},
		};
	},

	getStateForPersisting( state ) {
		delete state.notices;
		return state;
	},

	getStateKey() {
		return 'wcs-account-settings';
	},

	View: () => (
		<AccountSettingsRootView
			storeOptions={ storeOptions } />
	),
} );
