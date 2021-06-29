/**
 * External dependencies
 */
import React, { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';
import { Button } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { fetchSettings } from 'woocommerce/woocommerce-services/state/label-settings/actions';

class CreditCardButton extends Component {

	onVisibilityChange = () => {
		if ( ! document.hidden ) {
			this.refetchSettings();
		}
		if ( this.creditCardWindow && this.creditCardWindow.closed ) {
			document.removeEventListener( 'visibilitychange', this.onVisibilityChange );
		}
	};

	refetchSettings = () => {
		this.props.fetchSettings( this.props.siteId );
	};

	onChooseCard = () => {
		this.creditCardWindow = window.open( this.props.url );
		document.addEventListener( 'visibilitychange', this.onVisibilityChange );
	}

	render() {
		const { 
			disabled,
			buttonLabel,
			buttonDescription
		} = this.props;

		return (
			<Fragment>
				<Button
					onClick={ this.onChooseCard }
					disabled={ disabled }
					isPrimary
					className = { classNames( 'button' ) }
				>
					{ buttonLabel } <Gridicon icon="external" />
				</Button>
				<div className="purchase-section__explanation">
					{
						buttonDescription( this.onChooseCard )
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
)( localize( CreditCardButton ) );
