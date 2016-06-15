import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';

const Text = ( { id, layout, value } ) => {
	return (
		<FormFieldset>
			<p id={ id } className={ layout.class } >
				{ value }
			</p>
		</FormFieldset>
	);
};

Text.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.shape( {
		class: PropTypes.string,
	} ),
	value: PropTypes.string.isRequired,
};

export default Text;
