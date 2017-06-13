/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

/**
 * Internal dependencies
 */
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import { getPaperSizes } from 'lib/pdf-label-utils';
import * as PrintTestLabelActions from '../state/actions';
import Notice from 'components/notice';
import FormButton from 'components/forms/form-button';
import FormFieldset from 'components/forms/form-fieldset';
import FormSelect from 'components/forms/form-select';
import FormLegend from 'components/forms/form-legend';

const PrintTestLabelView = ( { paperSize, country, printingInProgress, error, updatePaperSize, print } ) => {
	const paperSizes = getPaperSizes( country );
	const onPaperSizeChange = ( event ) => updatePaperSize( event.target.value );

	return (
		<CompactCard className="settings-group-card print-test-label">
			<FormSectionHeading className="settings-group-header">{ __( 'Print' ) }</FormSectionHeading>
			<div className="settings-group-content">
				{ error && <Notice
					className="validation-message"
					status="is-error"
					showDismiss={ false }
					text={ error } /> }
				<FormLegend>{ __( 'Having trouble configuring your printer?' ) }</FormLegend>
				<p>{ __( 'You can run a test print for shipping labels by selecting the paper size, then print.' ) }</p>
				<FormFieldset>
					<FormLegend>{ __( 'Paper size' ) }</FormLegend>
					<div className="print-test-label__form-container">
						<FormSelect
							className="print-test-label__paper-size"
							value={ paperSize }
							onChange={ onPaperSizeChange } >
							{ Object.keys( paperSizes ).map( key =>
								<option key={ key } value={ key }>
									{ paperSizes[ key ] }
								</option>
							) }
						</FormSelect>
						<FormButton
							type="button"
							disabled={ Boolean( printingInProgress ) }
							onClick={ print }
							isPrimary={ false }>
							{ __( 'Print' ) }
						</FormButton>
					</div>
				</FormFieldset>
			</div>
		</CompactCard>
	);
};

PrintTestLabelView.propTypes = {
	paperSize: PropTypes.string.isRequired,
	country: PropTypes.string.isRequired,
	printingInProgress: PropTypes.bool,
	error: PropTypes.string,
	updatePaperSize: PropTypes.func.isRequired,
	print: PropTypes.func.isRequired,
};

export default connect(
	( state ) => state,
	( dispatch ) => bindActionCreators( PrintTestLabelActions, dispatch )
)( PrintTestLabelView );
