import React, { PropTypes } from 'react';
import FormButton from 'components/forms/form-button';
import FormButtonsBar from 'components/forms/form-buttons-bar';

const ActionButtons = ( { buttons } ) => {
	return (
		<FormButtonsBar>
			{ buttons.map( ( button, idx ) => (
				<FormButton
					type="button"
					key={ idx }
					disabled={ button.isDisabled }
					onClick={ button.onClick }
					isPrimary={ !! button.isPrimary }>
					{ button.label }
				</FormButton>
			) ) }
		</FormButtonsBar>
	);
};

ActionButtons.propTypes = {
	buttons: PropTypes.arrayOf(
		PropTypes.shape( {
			label: PropTypes.string.isRequired,
			onClick: PropTypes.func.isRequired,
			isPrimary: PropTypes.bool,
			isDisabled: PropTypes.bool,
		} )
	).isRequired,
};

export default ActionButtons;
