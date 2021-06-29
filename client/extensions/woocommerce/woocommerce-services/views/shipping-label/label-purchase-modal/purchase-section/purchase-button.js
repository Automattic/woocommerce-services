/** @format */

/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import classNames from 'classnames';
import { Button } from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	confirmPrintLabel,
	purchaseLabel,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const getPurchaseButtonLabel = props => {
	const { form, translate } = props;

	if ( form.needsPrintConfirmation ) {
		return translate( 'Print' );
	}

	if ( form.isSubmitting ) {
		return translate( 'Purchasing…' );
	}

	return translate( 'Buy shipping label', 'Buy shipping labels', { count: Object.keys( form.packages.selected ).length } );
};

const PurchaseButton = props => {
	const { form, disabled, busy } = props;
	return (
		<Fragment>
			<Button
				disabled={ disabled }
				onClick={ form.needsPrintConfirmation ? props.confirmPrintLabel : props.purchaseLabel }
				isPrimary
				isBusy={ busy }
				className = { classNames( 'button' ) }
			>
				{ getPurchaseButtonLabel( props ) }
			</Button>
		</Fragment>
	);
};

PurchaseButton.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	disabled: PropTypes.bool,
	busy: PropTypes.bool,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		form: loaded && shippingLabel.form,
	};
};

const mapDispatchToProps = ( dispatch, { orderId, siteId } ) => ( {
	confirmPrintLabel: () => dispatch( confirmPrintLabel( orderId, siteId ) ),
	purchaseLabel: () => dispatch( purchaseLabel( orderId, siteId ) ),
} );

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( PurchaseButton ) );
