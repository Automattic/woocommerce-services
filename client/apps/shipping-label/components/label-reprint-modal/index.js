/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { translate as __ } from 'i18n-calypso';

/**
 * Internal dependencies
 */
import Modal from 'components/modal';
import ActionButtons from 'components/action-buttons';
import Dropdown from 'components/dropdown';
import { getPaperSizes } from 'lib/pdf-label-utils';
import FormSectionHeading from 'components/forms/form-section-heading';
import { closeReprintDialog, confirmReprint, updatePaperSize } from '../../state/actions';

const ReprintDialog = ( { reprintDialog, labelActions, paperSize, storeOptions } ) => {
	return (
		<Modal
			isVisible={ Boolean( reprintDialog ) }
			onClose={ labelActions.closeReprintDialog }
			additionalClassNames="label-reprint-modal">
			<FormSectionHeading>
				{ __( 'Reprint shipping label' ) }
			</FormSectionHeading>
			<p>
				{ __( 'If there was a printing error when you purchased the label, you can print it again.' ) }
			</p>
			<p className="label-reprint-modal__notice">
				{ __( 'NOTE: If you already used the label in a package, printing and using it again ' +
					'is a violation of our terms of service and may result in criminal charges.' ) }
			</p>
			<Dropdown
				id={ 'paper_size' }
				valuesMap={ getPaperSizes( storeOptions.origin_country ) }
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
		</Modal>
	);
};

ReprintDialog.propTypes = {
	reprintDialog: PropTypes.object,
	labelActions: PropTypes.object.isRequired,
	paperSize: PropTypes.string.isRequired,
	storeOptions: PropTypes.object.isRequired,
};

function mapStateToProps( state ) {
	const shippingLabel = state.shippingLabel;
	const loaded = shippingLabel.loaded;
	return {
		reprintDialog: loaded ? shippingLabel.reprintDialog : {},
		paperSize: shippingLabel.paperSize,
		storeOptions: loaded ? shippingLabel.storeOptions : {},
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		labelActions: bindActionCreators( { closeReprintDialog, confirmReprint, updatePaperSize }, dispatch ),
	};
}

export default connect( mapStateToProps, mapDispatchToProps )( ReprintDialog );
