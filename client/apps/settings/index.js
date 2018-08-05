/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ViewWrapper from './view-wrapper';
import reduxMiddleware from './redux-middleware';
// from calypso
import zoneMethodsReducer from 'woocommerce/state/sites/shipping-zone-methods/reducer';
import zonesReducer from 'woocommerce/state/sites/shipping-zones/reducer';
import methodSettingsReducer from 'woocommerce/woocommerce-services/state/shipping-zone-method-settings/reducer';
import uiZonesReducer, { initialState as uiZonesInitialState } from 'woocommerce/state/ui/shipping/zones/reducer';
import { initialState as uiMethodsInitialState } from 'woocommerce/state/ui/shipping/zones/methods/reducer';
import { initialState as uiLocationsInitialState } from 'woocommerce/state/ui/shipping/zones/locations/reducer';
import notices from 'state/notices/reducer';
import { combineReducers } from 'state/utils';
import { fetchShippingZoneMethodSettings } from 'woocommerce/woocommerce-services/state/shipping-zone-method-settings/actions';
import { fetchShippingClasses } from 'woocommerce/state/sites/shipping-classes/actions';
import methodSchemasReducer from 'woocommerce/woocommerce-services/state/shipping-method-schemas/reducer';
import wcsUiDataLayer from 'woocommerce/state/data-layer/ui/woocommerce-services';
import { middleware as rawWpcomApiMiddleware } from 'state/data-layer/wpcom-api-middleware';
import shippingClassesReducer from 'woocommerce/state/sites/shipping-classes/reducers';
import shippingClassesLayer from 'woocommerce/state/data-layer/shipping-classes/';

export default ( { methodId, instanceId } ) => ( {
	getReducer() {
		return combineReducers( {
			extensions: combineReducers( {
				woocommerce: combineReducers( {
					sites: combineReducers( {
						1: combineReducers( {
							shippingZoneMethods: zoneMethodsReducer,
							shippingZones: zonesReducer,
							shippingZoneLocations: state => state || null,
							shippingMethods: state => state || null,
							shippingClasses: shippingClassesReducer,
						} ),
					} ),
					ui: combineReducers( {
						shipping: combineReducers( {
							1: combineReducers( {
								zones: uiZonesReducer,
							} ),
						} ),
					} ),
					woocommerceServices: combineReducers( {
						1: combineReducers( {
							shippingZoneMethodSettings: methodSettingsReducer,
							shippingMethodSchemas: methodSchemasReducer,
						} ),
					} ),
				} ),
			} ),
			ui: state => state || null,
			notices,
		} );
	},

	getInitialState() {
		return {
			extensions: {
				woocommerce: {
					sites: {
						1: {
							shippingMethods: [ { id: methodId } ],
							shippingZones: [ { id: 0, order: 0, methodIds: [ instanceId ] } ],
							shippingZoneLocations: { 0: { continent: [], country: [], state: [], postcode: [] } },
							shippingZoneMethods: { [ instanceId ]: { id: instanceId, methodType: methodId } },
						},
					},
					ui: {
						shipping: {
							1: {
								zones: {
									...uiZonesInitialState,
									currentlyEditingId: 0,
									currentlyEditingChanges: {
										methods: {
											...uiMethodsInitialState,
											currentlyEditingId: instanceId,
											currentlyEditingChanges: {},
										},
										locations: uiLocationsInitialState,
									},
								},
							},
						},
					},
				},
			},
			ui: {
				selectedSiteId: 1,
			},
		};
	},

	getStateForPersisting( state ) {
		delete state.notices;
		return state;
	},

	getInitialActions() {
		return [
			fetchShippingZoneMethodSettings( 0, methodId, instanceId ),
			fetchShippingClasses( 0 )
		];
	},

	getMiddlewares() {
		return [
			reduxMiddleware,
			rawWpcomApiMiddleware( wcsUiDataLayer ),
			rawWpcomApiMiddleware( shippingClassesLayer ),
		];
	},

	getStateKey() {
		return `wcs-settings-${ methodId }-${ instanceId }`;
	},

	View: () => {
		return <ViewWrapper />;
	},
} );
