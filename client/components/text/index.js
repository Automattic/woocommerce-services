import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import sanitizeHTML from 'lib/utils/sanitize-html';

const renderTitle = ( title ) => {
	if ( ! title ) {
		return null;
	}

	return (
		<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
	);
};

const renderText = ( text ) => {
	return (
		<span dangerouslySetInnerHTML={ sanitizeHTML( text ) } />
	);
};

const Text = ( { id, layout, value } ) => {
	return (
		<FormFieldset>
			{ renderTitle( layout.title ) }
			<p id={ id } className={ layout.class } >
				{ renderText( value ) }
			</p>
		</FormFieldset>
	);
};

Text.propTypes = {
	id: PropTypes.string.isRequired,
	layout: PropTypes.shape( {
		class: PropTypes.string,
		title: PropTypes.string,
	} ),
	value: PropTypes.string.isRequired,
};

export default Text;
