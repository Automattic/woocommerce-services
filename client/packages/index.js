import React from 'react';
import { combineReducers } from 'redux';
import PackagesView from './views';
import reducer from './state/reducer';
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
		return {
			form: {
				pristine: true,
				isSaving: false,
				isFetching: false,
				showModal: false,
				modalErrors: {},
				packageData: {
					is_user_defined: true,
				},
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
		return 'wcs-packages';
	},

	View: () => {
		return <PackagesView />;
	},
} );
