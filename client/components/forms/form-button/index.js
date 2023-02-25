/** @format */

/**
 * External dependencies
 */

import classNames from 'classnames';
import { localize } from 'i18n-calypso';
import { omit } from 'lodash';
import React, { Children } from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { Button } from '@wordpress/components';

class FormButton extends React.Component {
	static defaultProps = {
		isSubmitting: false,
		isPrimary: true,
		type: 'submit',
		borderless: false,
	};

	getDefaultButtonAction = () => {
		return this.props.isSubmitting
			? this.props.translate( 'Saving…' )
			: this.props.translate( 'Save Settings' );
	};

	render() {
		const { children, className, isPrimary, ...props } = this.props,
			buttonClasses = classNames( className, 'form-button', 'button', {
				'is-borderless': this.props.borderless
			 } );

		return (
			<Button
				{ ...omit( props, [ 'isSubmitting', 'moment', 'numberFormat', 'translate', 'borderless' ] ) }
				isPrimary={ isPrimary }
				className={ buttonClasses }
			>
				{ Children.count( children ) ? children : this.getDefaultButtonAction() }
			</Button>
		);
	}
}

FormButton.propTypes = {
	isSubmitting: PropTypes.bool,
	isPrimary: PropTypes.bool,
	type: PropTypes.string,
	borderless: PropTypes.bool,
};

export default localize( FormButton );
