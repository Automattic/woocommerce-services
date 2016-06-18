import React, { PropTypes } from 'react';
import FormTextInput from 'components/forms/form-text-input';

export default React.createClass( {
	displayName: 'NumberInput',

	propTypes: {
		onChange: PropTypes.func,
	},

	getDefaultProps() {
		return {
			onChange: () => {},
		}
	},

	getInitialState() {
		return {
			focused: false,
			text: this.props.value,
		};
	},

	componentWillReceiveProps( nextProps ) {
		if ( ! this.state.focused && nextProps.value !== this.props.value ) {
			this.setState( { text: nextProps.value } );
		}
	},

	handleChange( event ) {
		this.setState( { text: event.target.value } );
		this.props.onChange( event );
	},

	handleBlur( event ) {
		this.setState( {
			focused: false,
			text: this.props.value,
		} );
		this.props.onChange( event );
	},

	render() {
		return (
			<FormTextInput
				{ ...this.props }
				value={ this.state.text }
				onChange={ this.handleChange }
				onBlur={ this.handleBlur }
				onFocus={ () => this.setState( { focused: true } ) }
			/>
		);
	},
} );
