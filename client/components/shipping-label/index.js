import React from 'react';
import Dialog from 'components/dialog';

const PrintLabelDialog = ( props ) => {
	return (
		<Dialog
			isVisible={ props.showDialog }
			onClose={ props.labelActions.exitPrintingFlow  }
		>
			Hi there!
		</Dialog>
	);
};

export default PrintLabelDialog;
