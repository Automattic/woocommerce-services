import React, { PropTypes } from 'react';
import Dialog from 'components/dialog';

const ReprintDialog = ( { reprintDialog, labelActions } ) => {
	return (
		<Dialog
			isVisible={ Boolean( reprintDialog ) }
			onClose={ labelActions.closeReprintDialog }
			additionalClassNames="wcc-modal" >
			Not implemented yet
		</Dialog>
	);
};

export default ReprintDialog;
