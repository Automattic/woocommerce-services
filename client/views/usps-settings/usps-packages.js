import React from 'react';
import Packages from 'components/shipping/packages';
import FormSectionHeading from 'components/forms/form-section-heading';
import CompactCard from 'components/card/compact';

const getPackages = () => ( [
	{
		id: 'padded-envelope',
		type: 'mail',
		name: 'Large padded envelope',
		dimensions: '14 x 7 x .25 in',
	},
	{
		id: 'bike-box',
		type: 'flip-horizontal',
		name: 'Bike box',
		dimensions: '34 x 12 x 9.75 in',
	},
] );

const getPackageTypes = () => ( {
	user: {
		label: 'Saved packages',
		packages: [
			{
				id: 'padded-envelope',
				type: 'envelope',
				name: 'Large padded envelope',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'bike-box',
				type: 'box',
				name: 'Bike box',
				dimensions: '34 x 12 x 9.75 in',
			},
		],
	},
	service: {
		label: 'USPS Flat Rate Boxes and Envelopes',
		packages: [
			{
				id: 'small-box',
				type: 'box',
				name: 'Priority Mail Small Flat Rate Box',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'medium-box',
				type: 'box',
				name: 'Priority Mail Medium Flat Rate Box',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'large-box',
				type: 'box',
				name: 'Priority Mail Large Flat Rate Box',
				dimensions: '14 x 7 x .25 in',
			},
			{
				id: 'legal-envelope',
				type: 'box',
				name: 'Priority Mail Legal Flat Rate Envelope',
				dimensions: '14 x 7 x .25 in',
			},
		],
	},
} );

const UspsPackages = () => {
	return (
		<CompactCard>
			<FormSectionHeading>Packages</FormSectionHeading>
			<Packages packages={ getPackages() } packageTypes={ getPackageTypes() } />
		</CompactCard>
	);
};

export default UspsPackages;
