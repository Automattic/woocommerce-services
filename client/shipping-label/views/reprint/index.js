import React, { PropTypes } from 'react';
import Dialog from 'components/dialog';
import { translate as __ } from 'lib/mixins/i18n';
import ActionButtons from 'components/action-buttons';

const ReprintDialog = ( { reprintDialog, labelActions } ) => {
	return (
		<Dialog
			isVisible={ Boolean( reprintDialog ) }
			onClose={ labelActions.closeReprintDialog }
			additionalClassNames="wcc-modal" >
			<h3 className="form-section-heading">
				{ __( 'Print shipping label' ) }
			</h3>
			{ __( 'If there was a printing error when you purchased the label, you can print it again. NOTE: If you already used the label in a package, printing it and using it again will be considered a felony.' ) }
			<ActionButtons buttons={ [
				{
					onClick: labelActions.confirmReprint,
					isPrimary: true,
					label: __( 'Print' ),
				},
				{
					onClick: labelActions.closeReprintDialog,
					label: __( 'Cancel' ),
				},
			] } />
		</Dialog>
	);
};

export default ReprintDialog;
