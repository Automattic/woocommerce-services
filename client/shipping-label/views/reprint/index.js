import React, { PropTypes } from 'react';
import Dialog from 'components/dialog';
import { translate as __ } from 'lib/mixins/i18n';
import ActionButtons from 'components/action-buttons';
import Dropdown from 'components/dropdown';
import { PAPER_SIZES } from 'lib/pdf-label-generator';
import _ from 'lodash';

const ReprintDialog = ( { reprintDialog, labelActions, paperSize } ) => {
	return (
		<Dialog
			isVisible={ Boolean( reprintDialog ) }
			onClose={ labelActions.closeReprintDialog }
			additionalClassNames="wcc-modal wcc-shipping-label-reprint">
			<div className="wcc-shipping-label-reprint__content">
				<h3 className="form-section-heading">
					{ __( 'Print shipping label' ) }
				</h3>
				<p>
					{ __( 'If there was a printing error when you purchased the label, you can print it again.' ) }
				</p>
				<p className="reprint-notice">
					{ __( 'NOTE: If you already used the label in a package, printing and using it again is a violation of our terms of service and may result in criminal charges.' ) }
				</p>
				<Dropdown
					id={ 'paper_size' }
					valuesMap={ _.mapValues( PAPER_SIZES, 'name' ) }
					title={ __( 'Paper size' ) }
					value={ paperSize }
					updateValue={ labelActions.updatePaperSize } />
				<ActionButtons buttons={ [
					{
						onClick: labelActions.confirmReprint,
						isPrimary: true,
						isDisabled: reprintDialog && reprintDialog.isFetching,
						label: __( 'Print' ),
					},
					{
						onClick: labelActions.closeReprintDialog,
						label: __( 'Cancel' ),
					},
				] } />
			</div>
		</Dialog>
	);
};

ReprintDialog.propTypes = {
	reprintDialog: PropTypes.object,
	labelActions: PropTypes.object.isRequired,
	paperSize: PropTypes.string.isRequired,
};

export default ReprintDialog;
