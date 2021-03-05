/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import formatCurrency from '@automattic/format-currency';

/**
 * Internal dependencies
 */
import ButtonModal from 'components/button-modal'
import {
	closeRefundDialog,
	confirmRefund,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	isLoaded,
	getShippingLabel,
	getLabelById,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';

const RefundDialog = props => {
	const {
		orderId,
		siteId,
		refundDialog,
		createdDate,
		refundableAmount,
		currency,
		labelId,
		translate,
		moment,
		label = {},
	} = props;

	const getRefundableAmount = () => {
		return formatCurrency( refundableAmount, currency );
	};

	const onClose = () => props.closeRefundDialog( orderId, siteId );
	const onConfirm = () => props.confirmRefund( orderId, siteId );

	const buttons = [
		{ action: 'cancel', label: translate( 'Cancel' ), onClick: onClose },
		{
			action: 'confirm',
			onClick: onConfirm,
			isPrimary: true,
			disabled: refundDialog && refundDialog.isSubmitting,
			additionalClassNames: refundDialog && refundDialog.isSubmitting ? 'is-busy' : '',
			label: translate( 'Refund label (-%(amount)s)', { args: { amount: getRefundableAmount() } } ),
		},
	];

	return (
		<ButtonModal
			additionalClassNames="label-refund-modal woocommerce wcc-root"
			isVisible={ Boolean( refundDialog && refundDialog.labelId === labelId ) }
			onClose={ onClose }
			shouldCloseOnClickOutside={ true }
			buttons={ buttons }
			title={ translate( 'Request a refund' ) }
		>
			<p>
				{ translate(
					'You can request a refund for a shipping label that has not been used to ship a package. It will take at least %(days)s days to process.',
					{ args: { days: label.carrier_id === 'dhlexpress' ? '31' : '14' } }
				) }
			</p>
			<dl>
				<dt>{ translate( 'Purchase date' ) }</dt>
				<dd>{ moment( createdDate ).format( 'LLL' ) }</dd>

				<dt>{ translate( 'Amount eligible for refund' ) }</dt>
				<dd>{ getRefundableAmount() }</dd>
			</dl>
		</ButtonModal>
	);
};

RefundDialog.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	refundDialog: PropTypes.object,
	createdDate: PropTypes.number,
	refundableAmount: PropTypes.number,
	currency: PropTypes.string,
	labelId: PropTypes.number,
	closeRefundDialog: PropTypes.func.isRequired,
	confirmRefund: PropTypes.func.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId, labelId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	return {
		refundDialog: loaded ? shippingLabel.refundDialog : {},
		label: getLabelById(state, orderId, siteId, labelId),
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators( { closeRefundDialog, confirmRefund }, dispatch );
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( RefundDialog ) );
