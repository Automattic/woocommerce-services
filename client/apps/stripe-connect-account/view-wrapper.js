/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

/**
 * Internal dependencies
 */
// from calypso
import StripeConnectAccount from 'woocommerce/app/settings/payments/stripe/payment-method-stripe-connect-account';
import { getSelectedSiteId } from 'state/ui/selectors';
import {
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
			stripeConnectAccount,
			isDeauthorizing,
		} = this.props;

		if ( ! stripeConnectAccount.connectedUserID ) {
			return null;
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
			stripeConnectAccount: getStripeConnectAccount( state, siteId ),
			isDeauthorizing: getIsDeauthorizing( state, siteId ),
		};
	},
	dispatch => bindActionCreators( {
		fetchAccountDetails,
		deauthorizeAccount,
	}, dispatch )
)( StripeConnectAccountWrapper );
