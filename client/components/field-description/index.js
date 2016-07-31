import React, { PropTypes } from 'react';
import FormSettingExplanation from 'components/forms/form-setting-explanation';
import sanitizeHTML from 'lib/utils/sanitize-html';

const FieldDescription = ( { text } ) => {
	return (
		text ? <FormSettingExplanation dangerouslySetInnerHTML={ sanitizeHTML( text ) } /> : null
	);
};

FieldDescription.propTypes = {
	text: PropTypes.string,
};

export default FieldDescription;
