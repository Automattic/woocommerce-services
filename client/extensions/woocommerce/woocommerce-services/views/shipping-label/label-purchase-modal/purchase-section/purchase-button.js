/** @format */

/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import {
	confirmPrintLabel,
	purchaseLabel,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	hasSelectedRates,
	isLoaded,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	getSelectedPaymentMethodId,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';

const getPurchaseButtonData = props => {
	const { form, translate, hasLabelsPaymentMethod } = props;

	const hasSelectedRate = hasSelectedRates( form.rates );

	if ( ! hasLabelsPaymentMethod && hasSelectedRate ) {
		return {
			buttonLabel: <>{ translate( 'Add credit card' ) } <Gridicon icon="external" /></>,
			// TODO: Implement this, logic similar to `ShippingLabels` class, but figure out where it belongs in this case
			onClick: null,
		};
	}

	if ( form.needsPrintConfirmation ) {
		return {
			buttonLabel: translate( 'Print' ),
			onClick: props.confirmPrintLabel,
		};
	}

	if ( form.isSubmitting ) {
		return {
			buttonLabel: translate( 'Purchasing…' ),
			onClick: null,
		};
	}

	return {
		buttonLabel: translate( 'Buy shipping labels' ),
		onClick: props.purchaseLabel,
	};
};

const PurchaseButton = props => {
	const { translate, disabled, busy } = props;
	const { onClick, buttonLabel } = getPurchaseButtonData( props );

	return (
		<Fragment>
			<Button
				disabled={ disabled }
				onClick={ onClick }
				primary
				busy={ busy }
			>
				{ buttonLabel }
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
	disabled: PropTypes.bool,
	busy: PropTypes.bool,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		hasLabelsPaymentMethod: Boolean( getSelectedPaymentMethodId( state, siteId ) ),
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
