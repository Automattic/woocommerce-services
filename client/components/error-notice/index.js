/**
 * External dependencies
 */
import React, { PropTypes } from 'react';

/**
 * Internal dependencies
 */
import Notice from 'components/notice';

const ErrorNotice = ( { children, isWarning } ) => {
	return (
		<Notice
			className="error-notice"
			status={ isWarning ? 'is-warning' : 'is-error' }
			showDismiss={ false }>
			{ children }
		</Notice>
	);
};

ErrorNotice.propTypes = {
	isWarning: PropTypes.boolean,
};

export default ErrorNotice;
