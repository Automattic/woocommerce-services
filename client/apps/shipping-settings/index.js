/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ViewWrapper from './view-wrapper';
// from calypso
import labelSettingsReducer from '../../extensions/woocommerce/woocommerce-services/state/label-settings/reducer';
import packagesReducer from '../../extensions/woocommerce/woocommerce-services/state/packages/reducer';
import notices from 'state/notices/reducer';
import actionList from '../../extensions/woocommerce/state/data-layer/action-list';
import wcsUiDataLayer from '../../extensions/woocommerce/state/data-layer/ui/woocommerce-services';
import locationsReducer from '../../extensions/woocommerce/state/sites/data/locations/reducer';
import { mergeHandlers } from 'state/action-watchers/utils';
import { middleware as rawWpcomApiMiddleware } from 'state/data-layer/wpcom-api-middleware';
import { combineReducers } from 'state/utils';

export default ( { order_id: orderId, order_href: orderHref, carrier: carrier, continents, carriers } ) => ( {
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
					sites: combineReducers( {
						1: combineReducers( {
							data: combineReducers( {
								locations: locationsReducer,
							} ),
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
		return {
			extensions: {
				woocommerce: {
					sites: {
						1: {
							data: {
								locations: continents,
							},
						},
					},
				},
			},
		};
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

	View: () => <ViewWrapper orderId={ orderId } orderHref={ orderHref } carrier={ carrier } carriers={ carriers } />,
} );
