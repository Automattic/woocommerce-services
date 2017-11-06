/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ViewWrapper from './view-wrapper';
// from calypso
import reducer from 'woocommerce/woocommerce-services/state/packages/reducer';
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default () => ( {
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
		return <ViewWrapper />;
	},
} );
