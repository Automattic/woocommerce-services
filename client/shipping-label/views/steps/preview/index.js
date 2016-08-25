import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';

const PreviewStep = () => {
	return (
		<div>
			<h4>{ __( 'Preview' ) }</h4>
			<div></div>
		</div>
	);
};

export default PreviewStep;
