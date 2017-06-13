/**
 * External dependencies
 */
import React from 'react';
import closest from 'component-closest';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import Dialog from 'components/dialog';

const Modal = ( props ) => {
	// Only close the modal if the click was in the dialog backdrop, *not* in any other element like global notices
	const shouldStayOpen = ( event ) => ! closest( event.target, '.dialog__backdrop', true );

	return (
		<Dialog
			{ ...props }
			isFullScreen={ true }
			additionalClassNames={ classnames( props.additionalClassNames, 'wcc-root' ) }
			onClickOutside={ shouldStayOpen } />
	);
};

export default Modal;
