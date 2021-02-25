/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { Modal, Button } from '@wordpress/components';

const buildButton = ( button, index ) => {
	const key = 'dialog-button-' + index;
	if ( React.isValidElement( button ) ) {
		return React.cloneElement( button, { key } );
	}

	const {
		label,
		additionalClassNames,
		action,
		...moreProps
	} = button;

	return ( 
		<Button
			key={ action }
			onClick={ button.onClick }
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
				overlayClassName='button-modal__overlay'
				{ ...moreProps } 
			>
				{ children }
				{ buttons && buttons.map( ( b, index ) => buildButton( b, index ) ) }
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