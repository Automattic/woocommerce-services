import React from 'react';
const SectionHeader = require( 'components/section-header' );
const FormToggle = require( 'components/forms/form-toggle' );

export default React.createClass( {
	displayName: 'Settings',
	render: function() {
		return (
			<div>
				<SectionHeader label="USPS Shipping">
					<FormToggle checked={true}>Enabled</FormToggle>
				</SectionHeader>
			</div>
		);
	}
} );