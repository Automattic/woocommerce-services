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
import FieldError from '../field-error';
import TextControlWithAffixes from 'components/forms/text-control-with-affixes';

const WeightField = ( {
	id,
	title,
	value,
	placeholder,
	updateValue,
	error,
	className,
	weightUnit,
} ) => {
	const classes = classNames( {
		'is-error': Boolean( error ),
	} );

	return (
		<FormFieldset className={ className }>
			<FormLabel htmlFor={ id }>{ title }</FormLabel>
			<TextControlWithAffixes
				suffix={ weightUnit }
				id={ id }
				name={ id }
				type="number"
				placeholder={ placeholder || '0.0' }
				value={ value }
				onChange={ updateValue }
				className={ classes }
			/>
			{ error && typeof error === 'string' && <FieldError text={ error } /> }
		</FormFieldset>
	);
};

WeightField.propTypes = {
	weightUnit: PropTypes.string.isRequired,
	id: PropTypes.string.isRequired,
	title: PropTypes.node,
	value: PropTypes.oneOfType( [ PropTypes.string, PropTypes.number ] ).isRequired,
	placeholder: PropTypes.string,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [ PropTypes.string, PropTypes.bool ] ),
	className: PropTypes.string,
};

export default WeightField;
