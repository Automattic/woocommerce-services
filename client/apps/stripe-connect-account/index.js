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

export default ( accountDetails ) => ( {
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
		const { account_id, display_name, email, business_logo, legal_entity, payouts_enabled } = accountDetails;
		return {
			extensions: {
				woocommerce: {
					sites: {
						1: {
							settings: {
								stripeConnectAccount: {
									connectedUserID: account_id,
									displayName: display_name,
									email,
									firstName: legal_entity.first_name,
									isActivated: payouts_enabled,
									logo: business_logo,
									lastName: legal_entity.last_name,
								},
							},
						},
					},
				},
			},
		};
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
