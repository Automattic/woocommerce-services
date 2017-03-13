import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dropdown from 'components/dropdown';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import ActionButtons from 'components/action-buttons';
import { getPaperSizes } from 'lib/pdf-label-utils';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import * as PrintTestLabelActions from '../state/actions';

const PrintTestLabelView = ( { paperSize, country, printingInProgress, error, updatePaperSize, print } ) => {
	return (
		<CompactCard className="settings-group-card">
			<FormSectionHeading className="settings-group-header">{ __( 'Print' ) }</FormSectionHeading>
			<div className="settings-group-content">
				<Dropdown
					id={ 'paper_size' }
					valuesMap={ getPaperSizes( country ) }
					title={ __( 'Paper size' ) }
					value={ paperSize }
					updateValue={ updatePaperSize } />
				<ActionButtons buttons={ [ {
					label: __( 'Print' ),
					onClick: print,
					isDisabled: Boolean( printingInProgress ),
				} ] } />
				{ error && <p>{ error }</p> }
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
