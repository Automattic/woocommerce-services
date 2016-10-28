import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dropdown from 'components/dropdown';
import { PAPER_SIZES } from 'lib/pdf-label-utils';

const PreviewStep = ( { labelPreviewURL, canPurchase, paperSize, labelActions, errors } ) => {
	return (
		<div>
			<span className="preview-title">{ __( 'Preview' ) }</span>
			<div className="preview-placeholder">
				{ canPurchase && labelPreviewURL && <iframe src={ labelPreviewURL } /> }
			</div>
			<Dropdown
				id={ 'paper_size' }
				valuesMap={ { '': __( 'Select one...' ), ...PAPER_SIZES } }
				title={ __( 'Paper size' ) }
				value={ paperSize }
				updateValue={ labelActions.updatePaperSize }
				error={ errors.paperSize } />
		</div>
	);
};

PreviewStep.propTypes = {
	labelPreviewURL: PropTypes.string,
	canPurchase: PropTypes.bool.isRequired,
};

export default PreviewStep;
