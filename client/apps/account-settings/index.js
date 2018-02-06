/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ViewWrapper from './view-wrapper';
// from calypso
import reducer from 'woocommerce/woocommerce-services/state/label-settings/reducer';
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';

export default ( { order_id: orderId, order_href: orderHref } ) => ( {
	getReducer() {
		return combineReducers( {
			extensions: combineReducers( {
				woocommerce: combineReducers( {
					woocommerceServices: combineReducers( {
						1: combineReducers( {
							labelSettings: reducer,
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
		return 'wcs-account-settings';
	},

	View: () => (
		<ViewWrapper orderId={ orderId } orderHref={ orderHref } />
	),
} );
