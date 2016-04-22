import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './packages-list-item';

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

const PackagesList = ( { packages } ) => (
	<FormFieldset className="wcc-shipping-packages-list">
		<div className="wcc-shipping-packages-list-header">
			<FormLegend className="package-type">Type</FormLegend>
			<FormLegend className="package-name">Name</FormLegend>
			<FormLegend className="package-dimensions">Dimensions (L x W x H)</FormLegend>
		</div>
		{ packages.map( packageProps => (
			<PackagesListItem key={ packageProps.id } { ...packageProps } />
		) ) }
	</FormFieldset>
);

PackagesList.defaultProps = {
	packages: getPackages(),
};

PackagesList.propTypes = {
	packages: React.PropTypes.array.isRequired,
};

export default PackagesList;
