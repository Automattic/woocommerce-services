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
	getError,
	getIsRequesting,
	getStripeConnectAccount,
	getIsDeauthorizing,
	getIsOAuthInitializing,
	getOAuthURL,
} from 'woocommerce/state/sites/settings/stripe-connect-account/selectors';
import {
	fetchAccountDetails,
	deauthorizeAccount,
	oauthInit,
} from 'woocommerce/state/sites/settings/stripe-connect-account/actions';

class StripeConnectAccountWrapper extends Component {
	componentDidMount() {
		const { siteId } = this.props;
		this.props.fetchAccountDetails( siteId );
	}

	componentDidUpdate( prevProps ) {
		const { siteId, isLoading, connectedUserID, oauthURL, isOAuthInitializing, stripeError } = this.props;
		const isInitError = ! isOAuthInitializing && prevProps.isOAuthInitializing && stripeError;
		if ( ! isLoading && prevProps.isLoading && ! connectedUserID && ! oauthURL && ! isInitError ) {
			this.props.oauthInit( siteId );
		}
	}

	onDeauthorize = () => {
		const { siteId } = this.props;
		this.props.deauthorizeAccount( siteId );
	}

	render() {
		const {
			isLoading,
			stripeConnectAccount,
			stripeError,
			isDeauthorizing,
			isReloading,
			oauthURL,
			translate,
		} = this.props;

		if ( isLoading ) {
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

		if ( oauthURL ) {
			return translate( "You’ve got WooCommerce Tax installed. If you’d like, we can automatically copy keys from your Stripe account for you. {{a}}Click here{{/a}} to connect your Stripe account using WooCommerce Tax.", {
				components: { a: <a href={ oauthURL } /> },
			} );
		}

		if ( stripeError ) {
			return stripeError;
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
		const stripeConnectAccount = getStripeConnectAccount( state, siteId );
		const isOAuthInitializing = getIsOAuthInitializing( state, siteId );

		return {
			siteId,
			isLoading: getIsRequesting( state, siteId ) || isOAuthInitializing,
			isOAuthInitializing,
			stripeConnectAccount,
			stripeError: getError( state, siteId ),
			connectedUserID: stripeConnectAccount.connectedUserID,
			isDeauthorizing: getIsDeauthorizing( state, siteId ),
			isReloading: state.isReloading,
			oauthURL: getOAuthURL( state, siteId ),
		};
	},
	dispatch => ( {
		fetchAccountDetails: ( siteId ) => dispatch( fetchAccountDetails( siteId ) ),
		deauthorizeAccount: ( siteId ) => {
			dispatch( deauthorizeAccount( siteId ) ).then( () => {
				dispatch( reloadPage );
			} );
		},
		oauthInit: ( siteId ) => dispatch( oauthInit( siteId ) ),
	} ),
)( localize( StripeConnectAccountWrapper ) );
