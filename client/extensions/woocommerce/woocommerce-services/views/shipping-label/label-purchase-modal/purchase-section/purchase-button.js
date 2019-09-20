/** @format */

/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import formatCurrency from '@automattic/format-currency';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import getPDFSupport from 'woocommerce/woocommerce-services/lib/utils/pdf-support';
import {
	confirmPrintLabel,
	purchaseLabel,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	getTotalPriceBreakdown,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const getPurchaseButtonLabel = props => {
	const { form, ratesTotal, translate } = props;

	if ( form.needsPrintConfirmation ) {
		return translate( 'Print' );
	}

	if ( form.isSubmitting ) {
		return translate( 'Purchasing…' );
	}

	const noNativePDFSupport = 'addon' === getPDFSupport();

	if ( props.canPurchase ) {
		if ( noNativePDFSupport ) {
			return translate( 'Buy (%s)', { args: [ formatCurrency( ratesTotal, 'USD' ) ] } );
		}

		return translate( 'Buy & Print (%s)', { args: [ formatCurrency( ratesTotal, 'USD' ) ] } );
	}

	if ( noNativePDFSupport ) {
		return translate( 'Buy' );
	}

	return translate( 'Buy & Print' );
};

const PurchaseButton = props => {
	const { form, translate, disabled, busy } = props;
	return (
		<Fragment>
			<Button
				disabled={ disabled }
				onClick={ form.needsPrintConfirmation ? props.confirmPrintLabel : props.purchaseLabel }
				primary
				busy={ busy }
			>
				{ getPurchaseButtonLabel( props ) }
			</Button>
			<div className="purchase-section__explanation">
				{ translate( 'Buying shipping labels will mark items as fulfilled.' ) }
			</div>
		</Fragment>
	);
};

PurchaseButton.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	canPurchase: PropTypes.bool.isRequired,
	disabled: PropTypes.bool.isRequired,
	busy: PropTypes.bool.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	const priceBreakdown = getTotalPriceBreakdown( state, orderId, siteId );
	return {
		form: loaded && shippingLabel.form,
		ratesTotal: priceBreakdown ? priceBreakdown.total : 0,
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
