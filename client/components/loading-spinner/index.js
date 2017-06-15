/**
 * External dependencies
 */
import React, { PropTypes } from 'react';

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
		<div className="loading-spinner loading-spinner__container">
			{ spinner }
		</div>
	);
};

LoadingSpinner.propTypes = {
	inline: PropTypes.bool,
};

export default LoadingSpinner;
