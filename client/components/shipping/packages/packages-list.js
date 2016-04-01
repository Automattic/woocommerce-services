import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './packages-list-item';

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

PackagesList.propTypes = {
	packages: React.PropTypes.array,
};

export default PackagesList;
