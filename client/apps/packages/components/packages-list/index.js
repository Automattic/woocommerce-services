/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import PackagesListItem from './item';
import LoadingSpinner from 'components/loading-spinner';

const noPackages = () => {
	return (
		<div className="packages-list__empty">
			<div className="packages-list__empty-icon">
				<Gridicon icon="info" size={ 18 } />
			</div>
			<div className="packages-list__empty-description">{ __( 'Your packages will display here once they are added.' ) }</div>
		</div>
	);
};

const PackagesList = ( { packages, dimensionUnit, editable, selected, serviceId, removePackage, editPackage, togglePackage } ) => {
	const renderPackageListItem = ( pckg, idx ) => {
		const isSelected = selected && selected.includes( pckg.id );
		const onToggle = () => togglePackage( serviceId, pckg.id );
		const onRemove = () => removePackage( idx );

		return (
			<PackagesListItem
				key={ idx }
				index={ idx }
				data={ pckg }
				selected={ isSelected }
				{ ...{
					onToggle,
					onRemove,
					editable,
					dimensionUnit,
					editPackage,
				} }
			/>
		);
	};

	const renderList = () => {
		if ( ! packages ) {
			return <LoadingSpinner />;
		}
		if ( ! packages.length ) {
			return noPackages();
		}
		return packages.map( ( pckg, idx ) => renderPackageListItem( pckg, idx ) );
	};

	return (
		<FormFieldset className="packages-list">
			<div className="packages-list__header">
				{ editable ? null : <FormLegend className="packages-list__column-actions" /> }
				<FormLegend className="packages-list__column-type">{ __( 'Type' ) }</FormLegend>
				<FormLegend className="packages-list__column-name">{ __( 'Name' ) }</FormLegend>
				<FormLegend className="packages-list__column-dimensions">
					{ __( 'Dimensions' ) }{ ' ' }
					<span className="packages-list__column-dimensions-format">{ __( '(L x W x H)' ) }</span>
				</FormLegend>
				{ editable ? <FormLegend className="packages-list__column-actions" /> : null }
			</div>
			{ renderList() }
		</FormFieldset>
	);
};

PackagesList.propTypes = {
	packages: PropTypes.array.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	editable: PropTypes.bool.isRequired,
	selected: PropTypes.array,
	serviceId: PropTypes.string,
	groupId: PropTypes.string,
	toggleAll: PropTypes.func,
	togglePackage: PropTypes.func,
	removePackage: PropTypes.func,
	editPackage: PropTypes.func,
};

export default PackagesList;
