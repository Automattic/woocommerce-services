/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ViewWrapper from './view-wrapper';
// from calypso
import labelSettingsReducer from 'woocommerce/woocommerce-services/state/label-settings/reducer';
import packagesReducer from 'woocommerce/woocommerce-services/state/packages/reducer';
import notices from 'state/notices/reducer';
import actionList from 'woocommerce/state/data-layer/action-list';
import wcsUiDataLayer from 'woocommerce/state/data-layer/ui/woocommerce-services';
import { mergeHandlers } from 'state/action-watchers/utils';
import { middleware as rawWpcomApiMiddleware } from 'state/data-layer/wpcom-api-middleware';
import { combineReducers } from 'state/utils';

export default ( { order_id: orderId, order_href: orderHref } ) => ( {
	getReducer() {
		return combineReducers( {
			extensions: combineReducers( {
				woocommerce: combineReducers( {
					woocommerceServices: combineReducers( {
						1: combineReducers( {
							packages: packagesReducer,
							labelSettings: labelSettingsReducer,
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

	getMiddlewares() {
		return [ rawWpcomApiMiddleware( mergeHandlers( wcsUiDataLayer, actionList ) ) ];
	},

	View: () => (
		<ViewWrapper orderId={ orderId } orderHref={ orderHref } />
	),
} );
