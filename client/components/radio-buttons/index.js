/* eslint-disable react/no-danger */
/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormRadio from 'components/forms/form-radio';
import sanitizeHTML from 'lib/utils/sanitize-html';
import FieldDescription from 'components/field-description';

class RadioButton extends Component {
	onChange = () => this.props.setValue( this.props.value );

	render() {
		const {
			value,
			currentValue,
			description,
		} = this.props;

		return (
			<FormLabel>
				<FormRadio value={ value } checked={ value === currentValue } onChange={ this.onChange } />
				<span dangerouslySetInnerHTML={ sanitizeHTML( description ) } />
			</FormLabel>
		);
	}
}

class RadioButtons extends Component {
	static propTypes = {
		valuesMap: PropTypes.object.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		value: PropTypes.string.isRequired,
		setValue: PropTypes.func.isRequired,
		className: PropTypes.string,
	};

	render() {
		const {
			valuesMap,
			title,
			description,
			value,
			setValue,
			className,
		} = this.props;

		return (
			<FormFieldset className={ className }>
				<FormLegend dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
				<FieldDescription text={ description } />
				{ Object.keys( valuesMap ).map( ( key ) => {
					return (
						<RadioButton
							key={ key }
							value={ key }
							currentValue={ value }
							setValue={ setValue }
							description={ valuesMap[ key ] }
						/>
					);
				} ) }
			</FormFieldset>
		);
	}
}

export default RadioButtons;
