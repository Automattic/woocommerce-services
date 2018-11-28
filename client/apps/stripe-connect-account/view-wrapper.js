/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { reloadPage } from './state/actions';
// from calypso
import StripeConnectAccount from 'woocommerce/app/settings/payments/stripe/payment-method-stripe-connect-account';
import Notice from 'components/notice';
import { getSelectedSiteId } from 'state/ui/selectors';
import {
	getIsRequesting,
	getStripeConnectAccount,
	getIsDeauthorizing,
} from 'woocommerce/state/sites/settings/stripe-connect-account/selectors';
import {
	fetchAccountDetails,
	deauthorizeAccount,
} from 'woocommerce/state/sites/settings/stripe-connect-account/actions';

class StripeConnectAccountWrapper extends Component {
	componentDidMount() {
		const { siteId } = this.props;
		this.props.fetchAccountDetails( siteId );
	}

	onDeauthorize = () => {
		const { siteId } = this.props;
		this.props.deauthorizeAccount( siteId );
	}

	render() {
		const {
			isRequesting,
			stripeConnectAccount,
			isDeauthorizing,
			isReloading,
			translate,
		} = this.props;

		if ( isRequesting ) {
			return (
				<div className="stripe-connect-account__placeholder-container">
					<div className="stripe-connect-account__placeholder-body" />
				</div>
			);
		}

		if ( isReloading ) {
			return (
				<Notice showDismiss={ false } isCompact isLoading>
					{ translate( 'Account disconnected. Reloading page…' ) }
				</Notice>
			);
		}

		if ( ! stripeConnectAccount.connectedUserID ) {
			return translate( 'Stripe account keys may be configured below.' );
		}

		return (
			<StripeConnectAccount
				stripeConnectAccount={ stripeConnectAccount }
				isDeauthorizing={ isDeauthorizing }
				onDeauthorize={ this.onDeauthorize }
			/>
		);
	}
}

export default connect(
	state => {
		const siteId = getSelectedSiteId( state );
		return {
			siteId,
			isRequesting: getIsRequesting( state, siteId ),
			stripeConnectAccount: getStripeConnectAccount( state, siteId ),
			isDeauthorizing: getIsDeauthorizing( state, siteId ),
			isReloading: state.isReloading,
		};
	},
	dispatch => ( {
		fetchAccountDetails: ( siteId ) => dispatch( fetchAccountDetails( siteId ) ),
		deauthorizeAccount: ( siteId ) => {
			dispatch( deauthorizeAccount( siteId ) ).then( () => {
				dispatch( reloadPage );
			} );
		},
	} ),
)( localize( StripeConnectAccountWrapper ) );
