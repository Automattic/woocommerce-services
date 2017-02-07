import React, { PropTypes } from 'react';
import Gridicon from 'components/gridicon';

const BulkCheckbox = ( { selectedCount, allCount, onToggle } ) => {
	const allSelected = selectedCount === allCount;
	const someSelected = 0 < selectedCount && allCount > selectedCount;

	const onClick = ( event ) => {
		const checked = ! ( someSelected || allSelected );
		onToggle( event, checked );
	};

	return (
		<span className="wcc-bulk-checkbox-container" onClick={ onClick }>
			<input type="checkbox" checked={ allSelected } readOnly />
			{ someSelected ? <Gridicon className="bulk-select__some-checked-icon" icon="minus-small" size={ 18 }/> : null }
		</span>
	);
};

BulkCheckbox.propTypes = {
	selectedCount: PropTypes.number.isRequired,
	allCount: PropTypes.number.isRequired,
	onToggle: PropTypes.func,
};

export default BulkCheckbox;
