import React from 'react';
import Dialog from 'components/dialog';
import closest from 'component-closest';
import classnames from 'classnames';

const Modal = ( props ) => {
	// Only close the modal if the click was in the dialog backdrop, *not* in any other element like global notices
	const shouldStayOpen = ( event ) => ! closest( event.target, '.dialog__backdrop', true );

	return (
		<Dialog
			{ ...props }
			isFullScreen={ true }
			additionalClassNames={ classnames( props.additionalClassNames, 'wcc-modal' ) }
			onClickOutside={ shouldStayOpen } />
	);
};

export default Modal;
