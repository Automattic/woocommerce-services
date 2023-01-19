/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import TextControlWithAffixes from 'components/forms/text-control-with-affixes';
import FieldError from '../field-error';

const PriceField = ( { id, title, value, placeholder, updateValue, error, className } ) => {
	const classes = classNames( {
		'is-error': Boolean( error ),
	} );

	return (
		<FormFieldset className={ className }>
			<FormLabel htmlFor={ id }>{ title }</FormLabel>
			{ error && typeof error === 'string' && <FieldError text={ error } /> }
			<TextControlWithAffixes
				prefix={ '$' }
				id={ id }
				name={ id }
				type="number"
				placeholder={ placeholder || '0.00' }
				value={ value }
				onChange={ updateValue }
				className={ classes }
			/>
			{ error && typeof error === 'string' && <FieldError text={ error } /> }
		</FormFieldset>
	);
};

PriceField.propTypes = {
	id: PropTypes.string.isRequired,
	title: PropTypes.node,
	value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ).isRequired,
	placeholder: PropTypes.string,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [ PropTypes.string, PropTypes.bool ] ),
	className: PropTypes.string,
};

export default PriceField;
