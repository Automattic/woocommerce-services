/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import Gridicon from 'gridicons';
import { translate as __ } from 'i18n-calypso';

const NuxNotice = ( { children, onDismiss } ) => {
	return (
		<div className="nux-notice">
			<Gridicon icon="info" size={ 24 } />
			<div className="nux-notice__copy">
				{ children }
			</div>
			{ onDismiss && <a href="#" onClick={ onDismiss }>{ __( 'Okay' ) }</a> }
		</div>
	);
};

NuxNotice.propTypes = {
	onDismiss: PropTypes.func,
};

export default NuxNotice;
