/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import Spinner from 'components/spinner';

const LoadingSpinner = ( { inline } ) => {
	const spinner = <Spinner size={ 24 } />;
	if ( inline ) {
		return spinner;
	}
	return (
		<div className="loading-spinner">
			{ spinner }
		</div>
	);
};

LoadingSpinner.propTypes = {
	inline: PropTypes.bool,
};

export default LoadingSpinner;
