/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { Modal, Button } from '@wordpress/components';

const buildButton = ( b ) => {
	const {
		label,
		additionalClassNames,
		...moreProps
	} = b;

	return ( 
		<Button
			onClick={ b.onClick }
			className={ 'button form-button ' + additionalClassNames }
			{ ...moreProps }
		>
			{ label }
		</Button>
	);
}

const ButtonModal = ( props ) => {

	const {
		buttons,
		isVisible,
		additionalClassNames,
		onClose,
		children,
		...moreProps
	} = props;

	return (
		isVisible ? (
			<Modal 
				className={ 'dialog card ' + additionalClassNames }
				isDismissible={ false }
				shouldCloseOnClickOutside={ false }
				onRequestClose={ onClose }
				{ ...moreProps } 
			>
				{ children }
				{ buttons.map( b => buildButton( b ) ) }
			</Modal>
		) : null
	);
};

ButtonModal.propTypes = {
	buttons: PropTypes.array,
	isVisible: PropTypes.bool,
	additionalClassNames: PropTypes.string,
	onClose: PropTypes.func,
};


export default ButtonModal;