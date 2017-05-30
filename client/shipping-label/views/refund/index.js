import React, { PropTypes } from 'react';
import Modal from 'components/modal';
import { translate as __ } from 'i18n-calypso';
import ActionButtons from 'components/action-buttons';
import { sprintf } from 'sprintf-js';
import formatDate from 'lib/utils/format-date';

const RefundDialog = ( { refundDialog, labelActions, storeOptions, created, refundable_amount, label_id } ) => {
	const getRefundableAmount = () => {
		return storeOptions.currency_symbol + refundable_amount.toFixed( 2 );
	};

	return (
		<Modal
			isVisible={ refundDialog && refundDialog.labelId === label_id }
			onClose={ labelActions.closeRefundDialog }
			additionalClassNames="wcc-shipping-label-refund">
			<div className="wcc-shipping-label-refund__content">
				<h3 className="form-section-heading">
					{ __( 'Request a refund' ) }
				</h3>
				<p>
					{ __( 'You can request a refund for a shipping label that has not been used to ship a package. It will take at least 14 days to process.' ) }
				</p>
				<hr/>
				<dl>
					<dt>{ __( 'Purchase date' ) }</dt>
					<dd>{ formatDate( created ) }</dd>

					<dt>{ __( 'Amount eligible for refund' ) }</dt>
					<dd>{ getRefundableAmount() }</dd>
				</dl>
				<ActionButtons buttons={ [
					{
						onClick: labelActions.confirmRefund,
						isPrimary: true,
						isDisabled: refundDialog && refundDialog.isSubmitting,
						label: sprintf( __( 'Refund label (-%s)' ), getRefundableAmount() ),
					},
					{
						onClick: labelActions.closeRefundDialog,
						label: __( 'Cancel' ),
					},
				] } />
			</div>
		</Modal>
	);
};

RefundDialog.propTypes = {
	refundDialog: PropTypes.object,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	created: PropTypes.number,
	refundable_amount: PropTypes.number,
	label_id: PropTypes.number,
};

export default RefundDialog;
