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
import FormTextarea from 'components/forms/form-textarea';
import FieldError from 'components/field-error';
import FieldDescription from 'components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

class TextArea extends Component {
	static propTypes = {
		id: PropTypes.string.isRequired,
		readonly: PropTypes.bool,
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
			readonly,
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
				<FormTextarea
					id={ id }
					name={ id }
					placeholder={ placeholder }
					readOnly={ readonly }
					value={ value }
					onChange={ this.handleChangeEvent }
				/>
				{ error ? <FieldError text={ error } /> : <FieldDescription text={ description } /> }
			</FormFieldset>
		);
	}
}

export default TextArea;
