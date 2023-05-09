/** @format */

/**
 * External dependencies
 */
import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { TextControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FieldError from '../field-error';
import FormSettingExplanation from 'wcs-client/components/forms/form-setting-explanation';

const TextField = ( {
	id,
	title,
	description,
	value,
	placeholder,
	updateValue,
	error,
	className,
	defaultValue
} ) => {
	const handleChangeEvent = useCallback( newValue => updateValue( newValue, id ), [updateValue]);

	const classes = classNames( 'form-text-input', {
		'is-error': Boolean( error ),
	} );

	return (
		<FormFieldset className={ className }>
			<TextControl
				label={ title }
				name={ id }
				placeholder={ placeholder }
				value={ value }
				onChange={ handleChangeEvent }
				className={ classes }
				defaultValue={ defaultValue }
			/>
			{ error && typeof error === 'string' && <FieldError text={ error } /> }
			{ ! error && description && <FormSettingExplanation>{ description }</FormSettingExplanation> }
		</FormFieldset>
	);
};

TextField.propTypes = {
	id: PropTypes.string.isRequired,
	title: PropTypes.node,
	description: PropTypes.string,
	value: PropTypes.string,
	defaultValue: PropTypes.string,
	updateValue: PropTypes.func,
	error: PropTypes.oneOfType( [ PropTypes.string, PropTypes.bool ] ),
	className: PropTypes.string,
};

export default TextField;
