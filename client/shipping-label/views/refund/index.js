import React, { PropTypes } from 'react';
import Dialog from 'components/dialog';
import { translate as __ } from 'lib/mixins/i18n';
import ActionButtons from 'components/action-buttons';
import { sprintf } from 'sprintf-js';

const RefundDialog = ( { refundDialog, labelActions, storeOptions, created, refundable_amount } ) => {
	const getRefundableAmount = () => {
		return storeOptions.currency_symbol + refundable_amount.toFixed( 2 );
	};

	return (
		<Dialog
			isVisible={ Boolean( refundDialog ) }
			onClose={ labelActions.closeRefundDialog }
			additionalClassNames="wcc-modal" >
			<h3 className="form-section-heading">
				{ __( 'Request a refund' ) }
			</h3>
			{ __( 'You can request a refund for a shipping label that has not been used to ship a package. Note that it may take up to 14 days to process.' ) }
			<hr/>
			<dl>
				<dt>{ __( 'Purchase date' ) }</dt>
				<dd>{
					new Date( created ).toLocaleDateString( document.documentElement.lang, {
						day: 'numeric',
						month: 'long',
						year: 'numeric',
					} )
				}</dd>

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
		</Dialog>
	);
};

export default RefundDialog;
