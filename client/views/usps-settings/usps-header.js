import React from 'react';
import SectionHeader from 'components/section-header';
import FormToggle from 'components/forms/form-toggle';
import FormLabel from 'components/forms/form-label';

const UspsHeader = () => {
	return (
		<SectionHeader label="USPS Shipping">
			<FormToggle id="enabled" name="enabled" checked={ true } readOnly={ true }>
				<FormLabel htmlFor="enabled" style={ { float: 'left' } }>Enable</FormLabel>
			</FormToggle>
		</SectionHeader>
	);
};

export default UspsHeader;
