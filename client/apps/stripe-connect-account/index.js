/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
// from calypso
import ViewWrapper from './view-wrapper';
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
