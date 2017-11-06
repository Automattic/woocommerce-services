/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import Packages from 'woocommerce/woocommerce-services/views/packages';
import reducer from './state/reducer';
import { fetchSettings } from './state/actions';
// from calypso
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default ( { formData, formSchema, storeOptions } ) => ( {
	getReducer() {
		return combineReducers( {
			extensions: combineReducers( {
				woocommerce: combineReducers( {
					woocommerceServices: combineReducers( {
						1: combineReducers( {
							packages: reducer,
						} ),
					} ),
				} ),
			} ),
			notices,
			ui: () => ( {
				selectedSiteId: 1,
			} ),
		} );
	},

	getHotReducer() {
		return combineReducers( {
			form: require( './state/reducer' ),
			notices,
		} );
	},

	getInitialState() {
		storeOptions = storeOptions || {};
		formSchema = formSchema || { custom: {} };
		return {
			form: {
				pristine: true,
				isSaving: false,
				isFetching: false,
				showModal: false,
				modalErrors: {},
				packages: formData,
				dimensionUnit: storeOptions.dimension_unit,
				weightUnit: storeOptions.weight_unit,
				packageSchema: formSchema.custom.items,
				predefinedSchema: formSchema.predefined,
				packageData: {
					is_user_defined: true,
				},
			},
		};
	},

	getStateForPersisting( state ) {
		delete state.notices;
		return state;
	},

	getStateKey() {
		return 'wcs-packages';
	},

	View: () => {
		return <Packages />;
	},
} );
