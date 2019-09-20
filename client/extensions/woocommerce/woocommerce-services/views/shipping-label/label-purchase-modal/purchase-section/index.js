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
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import PurchaseButton from './purchase-button';
import AddCreditCardButton from './add-credit-card-button';
import {
	getSelectedPaymentMethodId,
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
		form,
	} = props;

	const disablePurchase = ! form.needsPrintConfirmation && ( ! props.canPurchase || form.isSubmitting );
	const purchaseBusy = form.isSubmitting && ! form.needsPrintConfirmation;

	return (
			<div className="purchase-section">
			{ hasLabelsPaymentMethod ? (
				<PurchaseButton
					siteId={ siteId }
					orderId={ orderId }
					canPurchase={ canPurchase }
					disabled={ disablePurchase }
					busy={ purchaseBusy }
				/>
			) : (
				<AddCreditCardButton disabled={ disablePurchase } />
			) }
		</div>
	);
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		form: loaded && shippingLabel.form,
		hasLabelsPaymentMethod: Boolean( getSelectedPaymentMethodId( state, siteId ) ),
		canPurchase: loaded && canPurchase( state, orderId, siteId ),
	};
};

export default connect(
	mapStateToProps,
)( localize( PurchaseSection ) );
