/**
 * External dependencies
 */
import React, { Component } from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import { getOrigin } from 'woocommerce/lib/nav-utils';

class AddCreditCardButton extends Component {

	onAddCardExternal = () => {
		window.open( getOrigin() + '/me/purchases/add-credit-card' );
	}

	render() {
		const { 
			translate,
			disabled
		} = this.props;
		return (
			<Button
				onClick={ this.onAddCardExternal }
				disabled={ disabled }
				primary
			>
				{ translate( 'Add credit card' ) }
			</Button>
		);
	}
}

export default connect(
)( localize( AddCreditCardButton ) );
