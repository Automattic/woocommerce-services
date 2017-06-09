import React, { PropTypes } from 'react';
import { translate as __ } from 'i18n-calypso';
import ItemInfo from './item-info';
import NumberField from 'components/number-field';
import FormLegend from 'components/forms/form-legend';
import FormSelect from 'components/forms/form-select';
import Button from 'components/button';
import Gridicon from 'gridicons';
import getBoxDimensions from 'lib/utils/get-box-dimensions';
import _ from 'lodash';

const renderPackageDimensions = ( dimensions, dimensionUnit ) => {
	return `${dimensions.length} ${dimensionUnit} x ${dimensions.width} ${dimensionUnit} x ${dimensions.height} ${dimensionUnit}`;
};

const PackageInfo = ( { packageId, selected, all, flatRateGroups, dimensionUnit, weightUnit, errors, updateWeight, openItemMove, removeItem, removePackage, setPackageType, openAddItem } ) => {
	const pckgErrors = errors[ packageId ] || {};
	const numPackages = _.values( selected ).filter( p => 'individual' !== p.box_id ).length;

	if ( ! packageId ) {
		return null;
	}

	const pckg = selected[ packageId ];
	const isIndividualPackage = ! ( 'not_selected' === pckg.box_id || all[ pckg.box_id ] );

	const renderItemInfo = ( item, itemIndex ) => {
		return (
			<ItemInfo key={ itemIndex } item={ item } itemIndex={ itemIndex } packageId={ packageId } showRemove={ true } openItemMove={ openItemMove } removeItem={ removeItem } isIndividualPackage={ isIndividualPackage } />
		);
	};

	const renderPackageOption = ( box, boxId ) => {
		const dimensions = getBoxDimensions( box );
		return ( <option value={ boxId } key={ boxId }>{ box.name } - { renderPackageDimensions( dimensions, dimensionUnit ) }</option> );
	};

	const renderAddItemButton = () => {
		if ( isIndividualPackage ) {
			return null;
		}

		return ( <Button className="wcc-package__add-item-btn" compact onClick={ () => ( openAddItem() ) }>{ __( 'Add items' ) }</Button> );
	};

	const packageOptionChange = ( e ) => {
		setPackageType( packageId, e.target.value );
	};

	const renderItems = () => {
		const canAddItems = _.some( selected, ( sel, selId ) => ( packageId !== selId && sel.items.length ) );

		if ( ! pckg.items.length ) {
			return (
				<div className="wcc-package__add-item-row">
					<div className="wcc-package__no-items-message">
						{ __( 'There are no items in this package.' ) }
						{ canAddItems ? renderAddItemButton() : null }
					</div>
				</div>
			);
		}

		const elements = pckg.items.map( renderItemInfo );
		if ( canAddItems ) {
			elements.push( <div key={ elements.length } className="wcc-package__add-item-row">
				{ renderAddItemButton() }
			</div> );
		}

		return elements;
	};

	const renderPackageSelect = () => {
		if ( isIndividualPackage ) {
			return ( <div className="wcc-package__desc">
				<div className="wcc-package-items-header">
					<FormLegend className="wcc-package-item__name">{ __( 'Individually Shipped Item' ) }</FormLegend>
				</div>
				<span className="wcc-package__desc-name">{ __( 'Item Dimensions' ) } - </span><span className="wcc-package__desc-dimensions">{ renderPackageDimensions( pckg, dimensionUnit ) }</span>
			</div> );
		}

		const groups = _.reduce( flatRateGroups, ( result, groupTitle, groupId ) => {
			const definitions = _.pickBy( all, { group_id: groupId } );
			if ( _.isEmpty( definitions ) ) {
				return result;
			}

			result[ groupId ] = { title: groupTitle, definitions };
			return result;
		}, {
			custom: { title: __( 'Custom Packages' ), definitions: _.pickBy( all, p => ! p.group_id ) },
		} );

		return (
			<div>
				<div className="wcc-package-items-header">
					<FormLegend className="wcc-package-item__name">{ __( 'Shipping Package of Multiple Items' ) }</FormLegend>
				</div>
				<FormSelect onChange={ packageOptionChange } value={ pckg.box_id } isError={ pckgErrors.box_id }>
					<option value={ 'not_selected' } key={ 'not_selected' }>{ __( 'Please select a package' ) }</option> )
					{ _.map( groups, ( group, groupId ) => {
						if ( _.isEmpty( group.definitions ) ) {
							return null;
						}

						return <optgroup label={ group.title } key={ groupId }>
							{ _.map( group.definitions, renderPackageOption ) }
						</optgroup>;
					} ) }
				</FormSelect>
			</div>
		);
	};

	const renderRemoveButton = () => {
		if ( 'individual' !== pckg.box_id && 1 === numPackages ) {
			return null;
		}

		return (
			<div>
				<Button className="wcc-package__remove" borderless compact onClick={ () => ( removePackage( packageId ) ) }>
					<Gridicon icon="trash" />
					<span className="wcc-package__remove-label">{ __( 'Remove this package' ) }</span>
				</Button>
			</div>
		);
	};

	return (
		<div className="wcc-package">
			{ renderPackageSelect() }

			<div>
				<div className="wcc-package-items-header">
					<FormLegend className="wcc-package-item__name">{ __( 'Contents' ) }</FormLegend>
				</div>
				{ renderItems() }
			</div>

			<div>
				<NumberField
					id={ `weight_${packageId}` }
					className="wcc-package__weight"
					title={ __( 'Total Weight' ) }
					value={ pckg.weight }
					updateValue={ ( value ) => updateWeight( packageId, value ) }
					error={ pckgErrors.weight } />
				<span className="wcc-package__weight-unit">{ weightUnit }</span>
			</div>
			{ renderRemoveButton() }
		</div>
	);
};

PackageInfo.propTypes = {
	packageId: PropTypes.string.isRequired,
	selected: PropTypes.object.isRequired,
	all: PropTypes.object.isRequired,
	flatRateGroups: PropTypes.object.isRequired,
	updateWeight: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
};

export default PackageInfo;
