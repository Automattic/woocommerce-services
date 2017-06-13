/* eslint-disable react/no-danger */
/**
 * External dependencies
 */
import React, { PropTypes } from 'react';

/**
 * Internal dependencies
 */
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

const Toggle = ( {
		id,
		title,
		description,
		trueText,
		falseText,
		saveOnToggle,
		checked,
		placeholder,
		saveForm,
		updateValue,
		className,
	} ) => {
	const handleChangeEvent = () => {
		updateValue( ! checked );
		if ( saveOnToggle && saveForm ) {
			saveForm();
		}
	};

	return (
		<FormFieldset className={ className }>
			<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
			<FormToggle
				id={ id }
				name={ id }
				placeholder={ placeholder }
				checked={ checked }
				onChange={ handleChangeEvent }
			/>
			{ renderToggleText( checked ? trueText : falseText ) }
			<FieldDescription text={ description } />
		</FormFieldset>
	);
};

Toggle.propTypes = {
	id: PropTypes.string.isRequired,
	title: PropTypes.string,
	description: PropTypes.string,
	trueText: PropTypes.string.isRequired,
	falseText: PropTypes.string.isRequired,
	saveOnToggle: PropTypes.bool,
	checked: PropTypes.bool,
	saveForm: PropTypes.func,
	updateValue: PropTypes.func,
	className: PropTypes.string,
};

Toggle.defaultProps = {
	checked: false,
};

export default Toggle;
