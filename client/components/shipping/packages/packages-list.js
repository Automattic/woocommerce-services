import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './packages-list-item';

const PackagesList = ( {
	packages,
	dimensionUnit,
	removePackage,
	editPackage,
	errors,
} ) => {
	const renderPackageListItem = ( pckg, idx ) => {
		const itemErrors = errors.length ? errors.filter( ( error ) => {
			return ( 0 === error.indexOf( idx + '.' ) );
		} ).map( ( error ) => error.substr( 2 ) ) : false;

		return (
			<PackagesListItem
				key={ idx }
				index={ idx }
				data={ pckg }
				dimensionUnit={ dimensionUnit }
				onRemove={ () => removePackage( idx ) }
				editPackage={ editPackage }
				errors={ itemErrors }
			/>
		);
	};

	return (
		<FormFieldset className="wcc-shipping-packages-list">
			<div className="wcc-shipping-packages-list-header">
				<FormLegend className="package-type">Type</FormLegend>
				<FormLegend className="package-name">Name</FormLegend>
				<FormLegend className="package-dimensions">Dimensions (L x W x H)</FormLegend>
			</div>
			{ packages.map( ( pckg, idx ) => renderPackageListItem( pckg, idx ) ) }
		</FormFieldset>
	);
};

PackagesList.propTypes = {
	packages: PropTypes.array.isRequired,
	removePackage: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	editPackage: PropTypes.func.isRequired,
};

export default PackagesList;
