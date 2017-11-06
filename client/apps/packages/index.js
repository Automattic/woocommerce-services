/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import Packages from 'woocommerce/woocommerce-services/views/packages';
import reducer from 'woocommerce/woocommerce-services/state/packages/reducer';
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
		return {};
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
