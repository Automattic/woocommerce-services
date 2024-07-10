/**
 * External dependencies
 */
import React, {Suspense} from 'react';

/**
 * Internal dependencies
 */

import ShippingLabelViewWrapper from './view-wrapper-label';
// Lazy load ShipmentTrackingViewWrapper so shipping label will render faster.
const ShipmentTrackingViewWrapper = React.lazy(() => import('./view-wrapper-tracking'));
import reduxMiddleware from './redux-middleware';
// from calypso
import notices from 'state/notices/reducer';
import reducer from '../../extensions/woocommerce/woocommerce-services/state/shipping-label/reducer';
import packagesReducer from '../../extensions/woocommerce/woocommerce-services/state/packages/reducer';
import labelSettingsReducer from '../../extensions/woocommerce/woocommerce-services/state/label-settings/reducer';
import ordersReducer from '../../extensions/woocommerce/state/sites/orders/reducer';
import { combineReducers } from 'state/utils';
import orders from '../../extensions/woocommerce/state/data-layer/orders';
import notes from  '../../extensions/woocommerce/state/data-layer/orders/notes';
import actionList from '../../extensions/woocommerce/state/data-layer/action-list';
import wcsUiDataLayer from '../../extensions/woocommerce/state/data-layer/ui/woocommerce-services';
import { middleware as rawWpcomApiMiddleware } from 'state/data-layer/wpcom-api-middleware';
import locations from '../../extensions/woocommerce/state/data-layer/data/locations';
import locationsReducer from '../../extensions/woocommerce/state/sites/data/locations/reducer';
import { mergeHandlers } from 'state/action-watchers/utils';
import initializeLabelsState from 'woocommerce/woocommerce-services/lib/initialize-labels-state';
import './style.scss';

export default ( { order, accountSettings, packagesSettings, shippingLabelData, continents, euCountries, context, items } ) => {
	const orderId = order ? order.id : null;
	const isPreloaded = ( undefined !== accountSettings );

	const addPreloadedState = function( initialState ) {
		const { storeOptions, formMeta, userMeta, formData } = accountSettings;
		const packages = packagesSettings.formData;
		const dimensionUnit = packagesSettings.storeOptions.dimension_unit;
		const weightUnit = packagesSettings.storeOptions.weight_unit;
		const packageSchema = packagesSettings.formSchema.custom ? packagesSettings.formSchema.custom.items : undefined;
		const predefinedSchema = packagesSettings.formSchema.predefined;
		initialState.extensions.woocommerce.woocommerceServices = {
			1: {
				shippingLabel: {
					[ orderId ]: initializeLabelsState( shippingLabelData )
				},
				labelSettings: {
					storeOptions,
					meta: {
						...formMeta,
						pristine: true,
						isLoaded: true,
						user: userMeta,
					},
					data: {
						...formData,
					},
				},
				packages: {
					modalErrors: {},
					pristine: true,
					packages,
					dimensionUnit,
					weightUnit,
					packageSchema,
					predefinedSchema,
					packageData: {
						is_user_defined: true,
					},
					isLoaded: true,
				}
			}
		};
		initialState.extensions.woocommerce.sites[1].orders.isLoading = {
			[ orderId ]: false,
		}

		initialState.extensions.woocommerce.sites[1].orders.items = {
			[ orderId ]: order,
		}
		initialState.extensions.woocommerce.sites[1].data = {
			locations: continents,
			euCountries
		}

		return initialState;
	}

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
			const initialState = {
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
			return isPreloaded ? addPreloadedState( initialState ) : initialState;
		},

		getStateForPersisting() {
			return null; //do not persist any state for labels
		},

		getStateKey() {
			return `wcs-label-${ orderId }`;
		},

		getMiddlewares() {
			return [ reduxMiddleware, rawWpcomApiMiddleware( mergeHandlers( wcsUiDataLayer, actionList, orders, notes, locations ) ) ];
		},

		View: () => (
			( 'shipment_tracking' === context ) ?
				<Suspense fallback={ <div /> }>
					<ShipmentTrackingViewWrapper orderId={ orderId } />
				</Suspense>
			:
				<ShippingLabelViewWrapper orderId={ orderId } items={ items } />
		),
	};
};
