/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Notice from 'components/notice';

const ErrorNotice = ( { children, isWarning } ) => {
	return (
		<Notice
			status={ isWarning ? 'is-warning' : 'is-error' }
			showDismiss={ false }>
			{ children }
		</Notice>
	);
};

ErrorNotice.propTypes = {
	isWarning: PropTypes.bool,
};

export default ErrorNotice;
