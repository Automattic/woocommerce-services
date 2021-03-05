/**
 * External dependencies
 */
import React from 'react';
import { Component } from '@wordpress/element';
import PropTypes from 'prop-types';
import { BaseControl } from '@wordpress/components';
import classnames from 'classnames';

/**
 * This component is essentially a wrapper (really a reimplementation) around the
 * TextControl component that adds support for affixes, i.e. the ability to display
 * a fixed part either at the beginning or at the end of the text input.
 */
class TextControlWithAffixes extends Component {
	constructor( props ) {
		super( props );
		this.state = {
			isFocused: false,
		};
	}

	handleFocusOutside() {
		this.setState( { isFocused: false } );
	}

	handleOnClick( event, onClick ) {
		this.setState( { isFocused: true } );
		if ( typeof onClick === 'function' ) {
			onClick( event );
		}
	}

	render() {
		const {
			label,
			value,
			help,
			className,
			instanceId,
			onChange,
			onClick,
			prefix,
			suffix,
			type,
			disabled,
			...props
		} = this.props;
		const { isFocused } = this.state;

		const id = `inspector-text-control-with-affixes-${ instanceId }`;
		const onChangeValue = ( event ) => onChange( event.target.value );
		const describedby = [];
		if ( help ) {
			describedby.push( `${ id }__help` );
		}
		if ( prefix ) {
			describedby.push( `${ id }__prefix` );
		}
		if ( suffix ) {
			describedby.push( `${ id }__suffix` );
		}

		const baseControlClasses = classnames( className, {
			'with-value': value !== '',
			empty: value === '',
			active: isFocused && ! disabled,
		} );

		const affixesClasses = classnames( 'text-control-with-affixes', 'BBTEST', {
			'text-control-with-prefix': prefix,
			'text-control-with-suffix': suffix,
			disabled,
		} );

		return (
			<BaseControl
				label={ label }
				id={ id }
				help={ help }
				className={ baseControlClasses }
				onClick={ ( event ) => this.handleOnClick( event, onClick ) }
			>
				<div className={ affixesClasses }>
					{ prefix && (
						<span
							id={ `${ id }__prefix` }
							className="text-control-with-affixes__prefix"
						>
							{ prefix }
						</span>
					) }

					<input
						// eslint-disable-next-line wpcalypso/jsx-classname-namespace
						className="components-text-control__input"
						type={ type }
						id={ id }
						value={ value }
						onChange={ onChangeValue }
						aria-describedby={ describedby.join( ' ' ) }
						disabled={ disabled }
						onFocus={ () => this.setState( { isFocused: true } ) }
						{ ...props }
					/>

					{ suffix && (
						<span
							id={ `${ id }__suffix` }
							className="text-control-with-affixes__suffix"
						>
							{ suffix }
						</span>
					) }
				</div>
			</BaseControl>
		);
	}
}

TextControlWithAffixes.defaultProps = {
	type: 'text',
};

TextControlWithAffixes.propTypes = {
	label: PropTypes.string,
	help: PropTypes.string,
	type: PropTypes.string,
	value: PropTypes.string.isRequired,
	className: PropTypes.string,
	onChange: PropTypes.func.isRequired,
	prefix: PropTypes.node,
	suffix: PropTypes.node,
	disabled: PropTypes.bool,
};

export default TextControlWithAffixes;