/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ViewWrapper from './view-wrapper';
import { STRIPE_CONNECT_ACCOUNT_RELOAD_PAGE } from './state/actions';
// from calypso
import reducer from 'woocommerce/state/sites/settings/stripe-connect-account/reducer';
import { combineReducers } from 'state/utils';

export default () => ( {
	getReducer() {
		return combineReducers( {
			extensions: combineReducers( {
				woocommerce: combineReducers( {
					sites: combineReducers( {
						1: combineReducers( {
							settings: combineReducers( {
								stripeConnectAccount: reducer,
							} ),
						} ),
					} ),
				} ),
			} ),
			ui: () => ( {
				selectedSiteId: 1,
			} ),
			isReloading: ( state = false, { type } ) => {
				return state || STRIPE_CONNECT_ACCOUNT_RELOAD_PAGE === type;
			},
		} );
	},

	getInitialState() {
		return {};
	},

	getStateForPersisting() {
		return null;
	},

	getStateKey() {
		return 'wcs-stripe-connect-account';
	},

	View: () => (
		<ViewWrapper />
	),
} );
