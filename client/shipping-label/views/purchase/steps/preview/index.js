import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dropdown from 'components/dropdown';
import { getPaperSizes } from 'lib/pdf-label-utils';

const PreviewStep = ( { labelPreviewURL, canPurchase, paperSize, labelActions, errors, form } ) => {
	return (
		<div>
			<div className="preview-container">
				<span className="preview-title">{ __( 'Preview' ) }</span>
				<div className="preview-placeholder">
					{ canPurchase && labelPreviewURL && <iframe src={ labelPreviewURL } /> }
				</div>
			</div>
			<Dropdown
				id={ 'paper_size' }
				valuesMap={ getPaperSizes( form.origin.values.country ) }
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
