import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';

const PreviewStep = ( { labelPreviewURL } ) => {
	return (
		<div>
			<span className="preview-title">{ __( 'Preview' ) }</span>
			<img
				src={ labelPreviewURL }
				className="preview-placeholder" />
		</div>
	);
};

PreviewStep.propTypes = {
	labelPreviewURL: PropTypes.string.isRequired,
};

export default PreviewStep;
