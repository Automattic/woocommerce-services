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
import CreditCardButton from './credit-card-button';
import { getOrigin } from 'woocommerce/lib/nav-utils';
import {
	getSelectedPaymentMethodId,
	getPaymentMethods,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';

/**
 * Style dependencies
 */
import './style.scss';

/**
 * Super important comment!
 */
export const PurchaseSection = props => {
	const {
		orderId,
		siteId,
		hasLabelsPaymentMethod,
		paymentMethods,
		form,
		disablePurchase,
		translate,
	} = props;
	const purchaseBusy = form.isSubmitting && ! form.needsPrintConfirmation;
	const hasSelectedRate = hasSelectedRates( form.rates );
	const labelRequiresPaymentMethod = hasSelectedRate && Object.values( form.rates.values ).some( ( label ) => {
		return 'ups' !== label.carrierId;
	} );
	const canPurchaseLabel = ( hasLabelsPaymentMethod && labelRequiresPaymentMethod ) || ! labelRequiresPaymentMethod;
	
	const addCardButtonDescription = ( onAddCard ) =>
		/* eslint-disable jsx-a11y/anchor-is-valid */
		translate( 'To print this shipping label, {{a}}add a credit card to your account{{/a}}.', {
			components: { a: <a onClick={ onAddCard } href="#" role="button" /> },
		} );
		/* eslint-enable jsx-a11y/anchor-is-valid */
	const chooseCardButtonDescription = ( onChooseCard ) =>
		/* eslint-disable jsx-a11y/anchor-is-valid */
		translate( 'To print this shipping label, {{a}}choose a credit card to add to your account{{/a}}.', {
			components: { a: <a onClick={ onChooseCard } href="#" role="button" /> },
		} );
		/* eslint-enable jsx-a11y/anchor-is-valid */

	/* eslint-disable no-nested-ternary */
	return (
		<div className="purchase-section">
			{ ( canPurchaseLabel || ! hasSelectedRate ) ? (
				<PurchaseButton
					siteId={ siteId }
					orderId={ orderId }
					disabled={ disablePurchase }
					busy={ purchaseBusy }
				/>
			) : ( ! paymentMethods.length ) ? (
				<CreditCardButton
					disabled={ disablePurchase }
					url={ getOrigin() + '/me/purchases/add-credit-card' }
					buttonLabel={ translate( 'Add credit card' ) }
					buttonDescription={ addCardButtonDescription }

				/>
			) : (
				<CreditCardButton
					disabled={ disablePurchase }
					url={ 'admin.php?page=wc-settings&tab=shipping&section=woocommerce-services-settings' }
					buttonLabel={ translate( 'Choose credit card' ) }
					buttonDescription={ chooseCardButtonDescription }
				/>
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
