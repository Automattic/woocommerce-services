/**
 * External dependencies
 */
import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import { getOrigin } from 'woocommerce/lib/nav-utils';
import { fetchSettings } from 'woocommerce/woocommerce-services/state/label-settings/actions';

class AddCreditCardButton extends Component {

	onVisibilityChange = () => {
		if ( ! document.hidden ) {
			this.refetchSettings();
		}
		if ( this.addCreditCardWindow && this.addCreditCardWindow.closed ) {
			document.removeEventListener( 'visibilitychange', this.onVisibilityChange );
		}
	};

	refetchSettings = () => {
		this.props.fetchSettings( this.props.siteId );
	};

	onAddCardExternal = () => {
		this.addCreditCardWindow = window.open( getOrigin() + '/me/purchases/add-credit-card' );
		document.addEventListener( 'visibilitychange', this.onVisibilityChange );
	}

	render() {
		const { 
			translate,
			disabled
		} = this.props;
		return (
			<Fragment>
				<Button
					onClick={ this.onAddCardExternal }
					disabled={ disabled }
					primary
				>
					{ translate( 'Add credit card' ) } <Gridicon icon="external" />
				</Button>
				<div className="purchase-section__explanation">
					{
						/* eslint-disable jsx-a11y/anchor-is-valid */
						translate( 'To print this shipping label, {{a}}add a credit card to your account{{/a}}.', {
							components: { a: <a onClick={ this.onAddCardExternal } href="#" role="button" /> },
						} )
						/* eslint-enable jsx-a11y/anchor-is-valid */
					}
				</div>
			</Fragment>
		);
	}
}

export default connect(
	( state ) => state,
	( dispatch ) => bindActionCreators( {
		fetchSettings,
	}, dispatch )
)( localize( AddCreditCardButton ) );
