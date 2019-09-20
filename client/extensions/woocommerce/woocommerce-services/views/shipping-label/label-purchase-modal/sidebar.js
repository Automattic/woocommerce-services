/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import { getPaperSizes } from 'woocommerce/woocommerce-services/lib/pdf-label-utils';
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown';
import FormCheckbox from 'components/forms/form-checkbox';
import FormLabel from 'components/forms/form-label';
import PriceSummary from './price-summary';
import {
	setEmailDetailsOption,
	setFulfillOrderOption,
	updatePaperSize,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	getFormErrors,
	shouldFulfillOrder,
	shouldEmailDetails,
	canPurchase,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	getSelectedPaymentMethodId,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';
import PurchaseButton from './purchase-button';
import AddCreditCardButton from './add-credit-card-button';

const Sidebar = props => {
	const {
		orderId,
		siteId,
		form,
		errors,
		paperSize,
		translate,
		fulfillOrder,
		emailDetails,
		hasLabelsPaymentMethod,
		disablePurchase,
		purchaseBusy,
		purchaseReady,
	} = props;

	const onEmailDetailsChange = () => props.setEmailDetailsOption( orderId, siteId, ! emailDetails );
	const onFulfillOrderChange = () => props.setFulfillOrderOption( orderId, siteId, ! fulfillOrder );
	const onPaperSizeChange = value => props.updatePaperSize( orderId, siteId, value );

	return (
		<div className="label-purchase-modal__sidebar">
			<PriceSummary siteId={ siteId } orderId={ orderId } />
			<Dropdown
				id={ 'paper_size' }
				valuesMap={ getPaperSizes( form.origin.values.country ) }
				title={ translate( 'Paper size' ) }
				value={ paperSize }
				updateValue={ onPaperSizeChange }
				error={ errors.paperSize }
			/>
			<FormLabel className="label-purchase-modal__option-email-customer">
				<FormCheckbox checked={ emailDetails } onChange={ onEmailDetailsChange } />
				<span>{ translate( 'Email shipment details to the customer' ) }</span>
			</FormLabel>
			<FormLabel className="label-purchase-modal__option-mark-order-fulfilled">
				<FormCheckbox checked={ fulfillOrder } onChange={ onFulfillOrderChange } />
				<span>{ translate( 'Mark the order as fulfilled' ) }</span>
			</FormLabel>
			<hr />
			<div className="label-purchase-modal__purchase-section">
				{ hasLabelsPaymentMethod ? (
					<PurchaseButton
						key="purchase"
						siteId={ props.siteId }
						orderId={ props.orderId }
						canPurchase={ purchaseReady }
						disabled={ disablePurchase }
						busy={ purchaseBusy }
					/>
				) : (
					<AddCreditCardButton disabled={ disablePurchase } />
				) }
			</div>
		</div>
	);
};

Sidebar.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	paperSize: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
	form: PropTypes.object.isRequired,
	updatePaperSize: PropTypes.func.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	const purchaseReady = loaded && canPurchase( state, orderId, siteId );
	const form = shippingLabel.form;
	return {
		paperSize: shippingLabel.paperSize,
		form,
		errors: loaded && getFormErrors( state, orderId, siteId ).sidebar,
		fulfillOrder: loaded && shouldFulfillOrder( state, orderId, siteId ),
		emailDetails: loaded && shouldEmailDetails( state, orderId, siteId ),
		hasLabelsPaymentMethod: Boolean( getSelectedPaymentMethodId( state, siteId ) ),
		disablePurchase: ! form.needsPrintConfirmation && ( ! purchaseReady || form.isSubmitting ),
		purchaseBusy: form.isSubmitting && ! form.needsPrintConfirmation,
		purchaseReady,
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
			setEmailDetailsOption,
			setFulfillOrderOption,
			updatePaperSize,
		},
		dispatch
	);
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( Sidebar ) );
