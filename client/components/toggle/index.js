import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormToggle from 'components/forms/form-toggle/compact';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

const renderToggleText = ( text ) => {
	return (
		text ? <span className="toggle__text" dangerouslySetInnerHTML={ sanitizeHTML( text ) } /> : null
	);
};

const Toggle = ( { id, schema, checked, placeholder, saveForm, updateValue } ) => {
	const handleChangeEvent = () => {
		updateValue( ! checked );
		if ( schema.saveOnToggle && saveForm ) {
			saveForm();
		}
	};

	return (
		<FormFieldset>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( schema.title ) } />
			<FormToggle
				id={ id }
				name={ id }
				placeholder={ placeholder }
				checked={ checked }
				onChange={ handleChangeEvent }
			/>
			{ renderToggleText( checked ? schema.trueText : schema.falseText ) }
			<FieldDescription text={ schema.description } />
		</FormFieldset>
	);
};

Toggle.propTypes = {
	id: PropTypes.string.isRequired,
	schema: PropTypes.shape( {
		type: PropTypes.string.valueOf( 'string' ),
		title: PropTypes.string,
		trueText: PropTypes.string,
		falseText: PropTypes.string,
		saveOnToggle: PropTypes.bool,
		description: PropTypes.string,
	} ).isRequired,
	checked: PropTypes.bool,
	saveForm: PropTypes.func,
	updateValue: PropTypes.func,
};

Toggle.defaultProps = {
	checked: false,
};

export default Toggle;
