/**
 * External dependencies
 */
import React, { PropTypes } from 'react';

/**
 * Internal dependencies
 */
import FormButton from 'components/forms/form-button';

const SetpConfirmationButton = ( { disabled, onClick, children } ) => {
	return (
		<div className="step-confirmation-button">
			<FormButton
				type="button"
				onClick={ onClick }
				disabled={ Boolean( disabled ) }
				isPrimary >
				{ children }
			</FormButton>
		</div>
	);
};

SetpConfirmationButton.propTypes = {
	disabled: PropTypes.bool,
	onClick: PropTypes.func.isRequired,
};

export default SetpConfirmationButton;
