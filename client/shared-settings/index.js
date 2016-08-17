import React from 'react';
import { combineReducers } from 'redux';
import SharedSettingsRootView from './views';
import reducer from './state/reducer';
// from calypso
import notices from 'state/notices/reducer';

export default ( { formMeta, formData, storeOptions } ) => ( {
	getReducer() {
		return combineReducers( {
			formData: reducer,
			formMeta: reducer,
			notices,
		} );
	},

	getHotReducer() {
		return combineReducers( {
			formData: require( './state/reducer' ),
			notices,
		} );
	},

	getInitialState() {
		return {
			formData: formData,
			formMeta: formMeta,
		};
	},

	View: () => (
		<SharedSettingsRootView
			storeOptions={ storeOptions } />
	),
} );
