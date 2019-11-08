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
import { fetchSettings } from 'woocommerce/woocommerce-services/state/label-settings/actions';

class ChooseCreditCardButton extends Component {

	onVisibilityChange = () => {
		if ( ! document.hidden ) {
			this.refetchSettings();
		}
		if ( this.chooseCreditCardWindow && this.chooseCreditCardWindow.closed ) {
			document.removeEventListener( 'visibilitychange', this.onVisibilityChange );
		}
	};

	refetchSettings = () => {
		this.props.fetchSettings( this.props.siteId );
	};

	onChooseCard = () => {
		this.chooseCreditCardWindow = window.open( 'admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings' );
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
					onClick={ this.onChooseCard }
					disabled={ disabled }
					primary
				>
					{ translate( 'Choose credit card' ) } <Gridicon icon="external" />
				</Button>
				<div className="purchase-section__explanation">
					{
						/* eslint-disable jsx-a11y/anchor-is-valid */
						translate( 'To print this shipping label, {{a}}choose a credit card to your account{{/a}}.', {
							components: { a: <a onClick={ this.onChooseCard } href="#" role="button" /> },
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
)( localize( ChooseCreditCardButton ) );
