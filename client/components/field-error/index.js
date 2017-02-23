import React, { PropTypes } from 'react';
import sanitizeHTML from 'lib/utils/sanitize-html';
import Gridicon from 'gridicons';

const FieldError = ( { text } ) => {
	return (
		<div className="form-input-validation is-error">
			<Gridicon size={ 24 } icon="notice-outline" /> <span dangerouslySetInnerHTML={ sanitizeHTML( text ) } />
		</div>
	);
};

FieldError.propTypes = {
	text: PropTypes.string,
};

export default FieldError;
