import React from 'react';
import { translate as __ } from 'lib/mixins/i18n';

const PreviewStep = () => {
	return (
		<div>
			<h4>{ __( 'Preview' ) }</h4>
			<div className="preview-placeholder"></div>
		</div>
	);
};

export default PreviewStep;
