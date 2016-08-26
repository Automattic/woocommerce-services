import React from 'react';
import { translate as __ } from 'lib/mixins/i18n';

const PreviewStep = () => {
	return (
		<div>
			<span className="preview-title">{ __( 'Preview' ) }</span>
			<div className="preview-placeholder"></div>
		</div>
	);
};

export default PreviewStep;
