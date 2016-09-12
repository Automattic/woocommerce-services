import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';

const PreviewStep = ( { labelPreviewURL, showPreview } ) => {
	return (
		<div>
			<span className="preview-title">{ __( 'Preview' ) }</span>
			<div className="preview-placeholder">
				{ showPreview && <img src={ labelPreviewURL } /> }
			</div>
		</div>
	);
};

PreviewStep.propTypes = {
	labelPreviewURL: PropTypes.string.isRequired,
	showPreview: PropTypes.bool.isRequired,
};

export default PreviewStep;
