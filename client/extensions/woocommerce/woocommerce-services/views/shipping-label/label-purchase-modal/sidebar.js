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
import FormCheckbox from 'components/forms/form-checkbox';
import FormLabel from 'components/forms/form-label';
import PriceSummary from './price-summary';
import PurchaseSection from './purchase-section';
import {
	setEmailDetailsOption,
	setFulfillOrderOption,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	getFormErrors,
	shouldFulfillOrder,
	shouldEmailDetails,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const Sidebar = props => {
	const {
		orderId,
		siteId,
		translate,
		fulfillOrder,
		emailDetails,
	} = props;

	const onEmailDetailsChange = () => props.setEmailDetailsOption( orderId, siteId, ! emailDetails );
	const onFulfillOrderChange = () => props.setFulfillOrderOption( orderId, siteId, ! fulfillOrder );

	return (
		<div className="label-purchase-modal__sidebar">
			<PriceSummary siteId={ siteId } orderId={ orderId } />
			<FormLabel className="label-purchase-modal__option-email-customer">
				<FormCheckbox checked={ emailDetails } onChange={ onEmailDetailsChange } />
				<span>{ translate( 'Email shipment details to the customer' ) }</span>
			</FormLabel>
			<FormLabel className="label-purchase-modal__option-mark-order-fulfilled">
				<FormCheckbox checked={ fulfillOrder } onChange={ onFulfillOrderChange } />
				<span>{ translate( 'Mark the order as fulfilled' ) }</span>
			</FormLabel>
			<hr />
			<PurchaseSection siteId={ siteId } orderId={ orderId } />
		</div>
	);
};

Sidebar.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	errors: PropTypes.object.isRequired,
	form: PropTypes.object.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		form: shippingLabel.form,
		errors: loaded && getFormErrors( state, orderId, siteId ).sidebar,
		fulfillOrder: loaded && shouldFulfillOrder( state, orderId, siteId ),
		emailDetails: loaded && shouldEmailDetails( state, orderId, siteId ),
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
			setEmailDetailsOption,
			setFulfillOrderOption,
		},
		dispatch
	);
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( Sidebar ) );
