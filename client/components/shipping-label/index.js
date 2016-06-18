import React from 'react';
import Dialog from 'components/dialog';
import WCCSettingsForm from 'components/wcc-settings-form';

const PrintLabelDialog = ( props ) => {
	return (
		<Dialog
			isVisible={ props.showDialog }
			onClose={ props.labelActions.exitPrintingFlow }
			additionalClassNames="wcc-modal"
		>
			<WCCSettingsForm
				{ ...props }
			/>
		</Dialog>
	);
};

export default PrintLabelDialog;
