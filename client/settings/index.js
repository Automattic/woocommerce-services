import React from 'react';
import { combineReducers } from 'redux';
import initializeState from 'lib/initialize-state';
import SettingsView from './views';
import form from './state/reducer';

// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, formSchema, formLayout, storeOptions, predefinedPackages } ) => ( {
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
		return initializeState( formSchema, formData );
	},

	View: () => (
		<SettingsView
			storeOptions={ storeOptions }
			schema={ formSchema }
			layout={ formLayout }
			predefinedPackages={ predefinedPackages } />
	),
} );
