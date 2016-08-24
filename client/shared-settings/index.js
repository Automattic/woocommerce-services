import React from 'react';
import { combineReducers } from 'redux';
import SharedSettingsRootView from './views';
import reducer from 'lib/form-base/reducer';
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
			form: reducer,
			notices,
		} );
	},

	getInitialState() {
		const initialMeta = require( 'lib/form-base/reducer' ).initialState.meta;
		const combinedMeta = Object.assign( {}, initialMeta, formMeta );

		return {
			form: {
				data: formData,
				meta: combinedMeta,
			},
		};
	},

	View: () => (
		<SharedSettingsRootView
			storeOptions={ storeOptions } />
	),
} );
