/* eslint-disable react/no-danger */
/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';
import FormFieldset from 'components/forms/form-fieldset';
import FormLabel from 'components/forms/form-label';
import { FormToggle } from '@wordpress/components';
import FieldDescription from '../../extensions/woocommerce/woocommerce-services/components/field-description';
import sanitizeHTML from 'lib/utils/sanitize-html';

class Toggle extends Component {
	static propTypes = {
		id: PropTypes.string.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		trueText: PropTypes.string.isRequired,
		falseText: PropTypes.string.isRequired,
		checked: PropTypes.bool,
		onUpdate: PropTypes.func,
		className: PropTypes.string,
	};

	static defaultProps = {
		checked: false,
	};

	renderToggleText = ( text ) => (
		text ? <span className="toggle__text" dangerouslySetInnerHTML={ sanitizeHTML( text ) } /> : null
	)

	handleChangeEvent = () => {
		const {
			onUpdate,
			checked,
		} = this.props;

		onUpdate( ! checked );
	};

	render() {
		const {
			id,
			title,
			description,
			trueText,
			falseText,
			checked,
			placeholder,
			className,
		} = this.props;

		return (
			<FormFieldset className={ className }>
				<FormLabel htmlFor={ id } dangerouslySetInnerHTML={ sanitizeHTML( title ) } />
				<FormToggle
					id={ id }
					name={ id }
					placeholder={ placeholder }
					checked={ checked }
					onChange={ this.handleChangeEvent }
				/>
				{ this.renderToggleText( checked ? trueText : falseText ) }
				<FieldDescription text={ description } />
			</FormFieldset>
		);
	}
}

export default Toggle;
