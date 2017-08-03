/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import ActionButtons from 'components/action-buttons';
import CompactCard from 'components/card/compact';

export default function ActionButtonsCard( { buttons } ) {
	return (
		<CompactCard className="action-buttons-card">
			<ActionButtons buttons={ buttons } />
		</CompactCard>
	);
}
