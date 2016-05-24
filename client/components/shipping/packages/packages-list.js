import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './packages-list-item';
import some from 'lodash/some';

const PackagesList = ( {
	packages,
	dimensionUnit,
	removePackage,
	editPackage,
	errors,
} ) => {
	const renderPackageListItem = ( pckg, idx ) => {
		/*
		 * Errors found in array items contain the array item index in their dot-notation path
		 *
		 * e.g.: data.boxes.0.inner_dimensions
		 *
		 * At this point, the errant field's parents have been stripped from the path, so we're
		 * looking for paths that begin just with the index of this package.
		 */
		const hasError = some( errors, ( error ) => ( 0 === error.indexOf( idx + '.' ) ) );

		return (
			<PackagesListItem
				key={ idx }
				index={ idx }
				data={ pckg }
				onRemove={ () => removePackage( idx ) }
				{ ...{
					dimensionUnit,
					editPackage,
					hasError,
				} }
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
			{ 0 === packages.length
				? <div>No Packages</div>
				: packages.map( ( pckg, idx ) => renderPackageListItem( pckg, idx ) )
			}
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
