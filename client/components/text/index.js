import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import { sanitize } from 'dompurify';

const renderTitle = ( title ) => {
	if ( ! title ) {
		return null;
	}

	return (
		<FormLegend>{ title }</FormLegend>
	);
};

const renderText = ( text ) => {
	return (
		<span dangerouslySetInnerHTML={ { __html: sanitize( text, { ADD_ATTR: [ 'target' ] } ) } }>
		</span>
	);
};

const Text = ( { id, layout, value } ) => {
	return (
		<FormFieldset id={ id + '_container' }>
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
