import React from 'react';
import { combineReducers } from 'redux';
import PackagesView from './views';
import reducer from './state/reducer';
// from calypso
import notices from 'state/notices/reducer';

export default ( { formData, formSchema, storeOptions } ) => ( {
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
				showModal: false,
				modalErrors: {},
				packages: formData,
				dimensionUnit: storeOptions.dimension_unit,
				weightUnit: storeOptions.weight_unit,
				packageData: {
					is_user_defined: true,
				},
				packageSchema: formSchema.items,
			},
		};
	},

	View: () => {
		return <PackagesView />;
	},
} );
