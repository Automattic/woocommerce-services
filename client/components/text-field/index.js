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
import FormTextInput from 'components/forms/form-text-input';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

class TextField extends Component {
	static propTypes = {
		id: PropTypes.string.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		value: PropTypes.string.isRequired,
		updateValue: PropTypes.func,
		error: PropTypes.oneOfType( [
			PropTypes.string,
			PropTypes.bool,
		] ),
		className: PropTypes.string,
	};

	handleChangeEvent = ( event ) => this.props.updateValue( event.target.value );

	render() {
		const {
			id,
			title,
			description,
			value,
			placeholder,
			error,
			className,
		} = this.props;

		return (
			<FormFieldset className={ className }>
				<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
				<FormTextInput
					id={ id }
					name={ id }
					placeholder={ placeholder }
					value={ value }
					onChange={ this.handleChangeEvent }
					isError={ Boolean( error ) }
				/>
				{ error ? <FieldError text={ error } /> : <FieldDescription text={ description } /> }
			</FormFieldset>
		);
	}
}

export default TextField;
