/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import {
	getShippingLabel,
	isLoaded,
	canPurchase,
	hasSelectedRates,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import PurchaseButton from './purchase-button';
import AddCreditCardButton from './add-credit-card-button';
import ChooseCreditCardButton from './choose-credit-card-button';
import {
	getSelectedPaymentMethodId,
	getPaymentMethods,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';

/**
 * Style dependencies
 */
import './style.scss';

const PurchaseSection = props => {
	const {
		orderId,
		siteId,
		hasLabelsPaymentMethod,
		paymentMethods,
		form,
		disablePurchase,
	} = props;
	const purchaseBusy = form.isSubmitting && ! form.needsPrintConfirmation;
	const hasSelectedRate = hasSelectedRates( form.rates );

	/* eslint-disable no-nested-ternary */
	return (
		<div className="purchase-section">
			{ ( hasLabelsPaymentMethod || ! hasSelectedRate ) ? (
				<PurchaseButton
					siteId={ siteId }
					orderId={ orderId }
					disabled={ disablePurchase }
					busy={ purchaseBusy }
				/>
			) : ( ! paymentMethods.length ) ? (
				<AddCreditCardButton disabled={ disablePurchase } />
			) : (
				<ChooseCreditCardButton disabled={ disablePurchase } />
			) }
		</div>
	);
	/* eslint-enable no-nested-ternary */
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	const purchaseReady = loaded && canPurchase( state, orderId, siteId );
	const form = loaded && shippingLabel.form;
	return {
		form,
		hasLabelsPaymentMethod: Boolean( getSelectedPaymentMethodId( state, siteId ) ),
		paymentMethods: getPaymentMethods( state, siteId ),
		disablePurchase: ! form.needsPrintConfirmation && ( ! purchaseReady || form.isSubmitting ),
	};
};

export default connect(
	mapStateToProps,
)( localize( PurchaseSection ) );
