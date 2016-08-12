import React from 'react';
import Dialog from 'components/dialog';

const PrintLabelDialog = ( props ) => {
	return (
		<Dialog
			isVisible={ props.showDialog }
			onClose={ props.labelActions.exitPrintingFlow }
			additionalClassNames="wcc-modal wcc-shipping-label-dialog"
		>
			<span>Not implemented yet</span>
		</Dialog>
	);
};

export default PrintLabelDialog;
