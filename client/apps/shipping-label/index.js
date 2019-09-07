/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ShippingLabelViewWrapper from './view-wrapper-label';
import ShipmentTrackingViewWrapper from './view-wrapper-tracking';
import reduxMiddleware from './redux-middleware';
// from calypso
import notices from 'state/notices/reducer';
import reducer from '../../extensions/woocommerce/woocommerce-services/state/shipping-label/reducer';
import packagesReducer from '../../extensions/woocommerce/woocommerce-services/state/packages/reducer';
import labelSettingsReducer from '../../extensions/woocommerce/woocommerce-services/state/label-settings/reducer';
import ordersReducer from '../../extensions/woocommerce/state/sites/orders/reducer';
import { combineReducers } from 'state/utils';
import orders from '../../extensions/woocommerce/state/data-layer/orders';
import { middleware as rawWpcomApiMiddleware } from 'state/data-layer/wpcom-api-middleware';
import locations from '../../extensions/woocommerce/state/data-layer/data/locations';
import locationsReducer from '../../extensions/woocommerce/state/sites/data/locations/reducer';
import { mergeHandlers } from 'state/action-watchers/utils';

export default ( { orderId, context, items } ) => {
	return {
		getReducer() {
			return combineReducers( {
				extensions: combineReducers( {
					woocommerce: combineReducers( {
						woocommerceServices: combineReducers( {
							1: combineReducers( {
								shippingLabel: reducer,
								packages: packagesReducer,
								labelSettings: labelSettingsReducer,
							} ),
						} ),
						sites: combineReducers( {
							1: combineReducers( {
								orders: ordersReducer,
								data: combineReducers( {
									locations: locationsReducer,
								} )
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
								orders: {
									notes: {
										isLoading: {
											[ orderId ]: false,
										},
									},
								},
							},
						},
					},
				},
			};
		},

		getStateForPersisting() {
			return null; //do not persist any state for labels
		},

		getStateKey() {
			return `wcs-label-${ orderId }`;
		},

		getMiddlewares() {
			return [ reduxMiddleware, rawWpcomApiMiddleware( mergeHandlers( orders, locations ) ) ];
		},

		View: () => (
			( 'shipment_tracking' === context ) ?
				<ShipmentTrackingViewWrapper orderId={ orderId } />
			:
				<ShippingLabelViewWrapper orderId={ orderId } items={ items } />
		),
	};
};
