/** @format */

/**
 * External dependencies
 */
import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { TextControl } from '@wordpress/components';
import classNames from 'classnames';
import { omit } from 'lodash';

export default class NumberInput extends Component {
	static propTypes = {
		onChange: PropTypes.func,
	};

	static defaultProps = {
		onChange: () => {},
	};

	state = {
		focused: false,
		text: this.props.value,
	};

	UNSAFE_componentWillReceiveProps( nextProps ) {
		if ( ! this.state.focused && nextProps.value !== this.props.value ) {
			this.setState( { text: nextProps.value } );
		}
	}

	handleChange = newValue => {
		this.setState( { text: newValue } );
		this.props.onChange( newValue );
	};

	handleBlur = () => {
		this.setState( {
			focused: false,
			text: this.props.value,
		} );
		this.props.onChange( this.props.value );
	};

	handleFocus = () => {
		this.setState( { focused: true } );
	};

	render() {
		const props = omit( this.props, 'isError', 'isValid' );

		const classes = classNames( this.props.className, {
			'is-error': this.props.isError,
			'is-valid': this.props.isValid,
		} );

		return (
			<TextControl
				{ ...props }
				className={ classes }
				value={ this.state.text }
				onChange={ this.handleChange }
				onBlur={ this.handleBlur }
				onFocus={ this.handleFocus }
			/>
		);
	}
}
