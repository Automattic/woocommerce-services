import React, { PropTypes } from 'react';
import FormFieldset from 'components/forms/form-fieldset';
import FormLegend from 'components/forms/form-legend';
import FormCheckbox from 'components/forms/form-checkbox';
import PackagesListItem from './packages-list-item';
import Gridicon from 'components/gridicon';
import { translate as __ } from 'lib/mixins/i18n';
import _ from 'lodash';

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

const PackagesList = ( { packages, dimensionUnit, editable, selected, serviceId, groupId, removePackage, editPackage, toggleAll, togglePackage } ) => {
	const renderSelectAll = () => {
		if ( ! selected ) {
			return null;
		}

		const allPackageIds = packages.map( ( def ) => def.id );
		const selectedAll = 0 === _.difference( allPackageIds, selected ).length;
		return <FormLegend className="package-actions"><FormCheckbox checked={ selectedAll } onChange={ () => toggleAll( serviceId, groupId ) }/></FormLegend>;
	};

	const renderPackageListItem = ( pckg, idx ) => {
		const isSelected = selected && selected.includes( pckg.id );

		return (
			<PackagesListItem
				key={ idx }
				index={ idx }
				data={ pckg }
				editable={ editable }
				selected={ isSelected }
				onToggle={ () => togglePackage( serviceId, pckg.id ) }
				onRemove={ () => removePackage( idx ) }
				{ ...{
					dimensionUnit,
					editPackage,
				} }
			/>
		);
	};

	return (
		<FormFieldset className="wcc-shipping-packages-list">
			<div className="wcc-shipping-packages-list-header">
				{ renderSelectAll() }
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
