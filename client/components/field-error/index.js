import React, { PropTypes } from 'react';
import sanitizeHTML from 'lib/utils/sanitize-html';
import Gridicon from 'gridicons';
import classNames from 'classnames';

const FieldError = ( { text, type = 'input-validation' } ) => {
	return (
		<div className={ classNames(
			'is-error',
			[ `form-${ type }` ]
		) }>
			<Gridicon size={ 24 } icon="notice-outline" /> <span dangerouslySetInnerHTML={ sanitizeHTML( text ) } />
		</div>
	);
};

FieldError.propTypes = {
	text: PropTypes.string,
	type: PropTypes.string,
};

export default FieldError;
