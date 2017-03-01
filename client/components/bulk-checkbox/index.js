import React, { PropTypes } from 'react';
import Gridicon from 'gridicons';
import Checkbox from 'components/checkbox';

const BulkCheckbox = ( { selectedCount, allCount, onToggle } ) => {
	const allSelected = selectedCount === allCount;
	const someSelected = 0 < selectedCount && allCount > selectedCount;

	const onClick = ( event ) => {
		const checked = ! ( someSelected || allSelected );
		onToggle( event, checked );
	};

	return (
		<span className="form-checkbox bulk-checkbox" onClick={ onClick }>
			<Checkbox checked={ allSelected } readOnly />
			{ someSelected && <Gridicon icon="minus-small" size={ 16 } /> }
		</span>
	);
};

BulkCheckbox.propTypes = {
	selectedCount: PropTypes.number.isRequired,
	allCount: PropTypes.number.isRequired,
	onToggle: PropTypes.func,
};

export default BulkCheckbox;
