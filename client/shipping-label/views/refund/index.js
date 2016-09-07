import React, { PropTypes } from 'react';
import Dialog from 'components/dialog';

const RefundDialog = ( { refundDialog, labelActions } ) => {
	return (
		<Dialog
			isVisible={ Boolean( refundDialog ) }
			onClose={ labelActions.closeRefundDialog }
			additionalClassNames="wcc-modal" >
			Not implemented yet
		</Dialog>
	);
};

export default RefundDialog;
