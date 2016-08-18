import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './packages-list-item';
import Gridicon from 'components/gridicon';
import { translate as __ } from 'lib/mixins/i18n';

const noPackages = () => {
	return (
		<div className="packages-list-empty">
			<div className="package-list-empty-icon">
				<Gridicon icon="info" size={ 18 } />
			</div>
			<div className="packages-list-empty-description">{ __( 'Your packages will display here once they are added.' ) }</div>
		</div>
	);
};

const PackagesList = ( {
	packages,
	dimensionUnit,
	removePackage,
	editPackage,
	errors,
} ) => {
	const renderPackageListItem = ( pckg, idx ) => {
		/*
		 * Errors found in array items contain the array item index in their path
		 *
		 * e.g.: { 'boxes': { '0': { 'inner_dimensions': {...} } } }
		 *
		 * At this point, the errant field's parents have been stripped from the path, so we're
		 * looking for paths that begin just with the index of this package.
		 */
		const hasError = errors[ idx ];

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
				<FormLegend className="package-type">{ __( 'Type' ) }</FormLegend>
				<FormLegend className="package-name">{ __( 'Name' ) }</FormLegend>
				<FormLegend className="package-dimensions">{ __( 'Dimensions (L x W x H)' ) }</FormLegend>
			</div>
			{ 0 === packages.length
				? noPackages()
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
