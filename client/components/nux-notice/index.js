/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import Gridicon from 'gridicons';
import { translate as __ } from 'i18n-calypso';

const NuxNotice = ( { children, onDismiss } ) => {
	return (
		<div className="nux-notice">
			<Gridicon icon="info" size={ 24 } />
			<div className="nux-notice__copy">
				{ children }
			</div>
			{ onDismiss && <button onClick={ onDismiss }>{ __( 'Okay' ) }</button> }
		</div>
	);
};

NuxNotice.propTypes = {
	onDismiss: PropTypes.func,
};

export default NuxNotice;
