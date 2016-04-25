import React from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './packages-list-item';

const PackagesList = ( {
	packages,
	dimensionUnit,
	removePackage,
} ) => (
	<FormFieldset className="wcc-shipping-packages-list">
		<div className="wcc-shipping-packages-list-header">
			<FormLegend className="package-type">Type</FormLegend>
			<FormLegend className="package-name">Name</FormLegend>
			<FormLegend className="package-dimensions">Dimensions (L x W x H)</FormLegend>
		</div>
		{ packages.map( ( packageProps, idx ) => (
			<PackagesListItem key={ idx } { ...packageProps } dimensionUnit={ dimensionUnit } onRemove={ () => removePackage( idx ) } />
		) ) }
	</FormFieldset>
);

PackagesList.propTypes = {
	packages: React.PropTypes.array.isRequired,
	removePackage: React.PropTypes.func.isRequired,
};

export default PackagesList;
