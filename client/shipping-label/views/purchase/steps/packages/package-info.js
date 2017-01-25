import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import ItemInfo from './item-info';
import NumberField from 'components/number-field';
import FormLegend from 'components/forms/form-legend';
import FormSelect from 'components/forms/form-select';
import Button from 'components/button';
import Gridicon from 'components/gridicon';
import getBoxDimensions from 'lib/utils/get-box-dimensions';
import _ from 'lodash';

const renderPackageDimensions = ( dimensions, dimensionUnit ) => {
	return `${dimensions.length} ${dimensionUnit} x ${dimensions.width} ${dimensionUnit} x ${dimensions.height} ${dimensionUnit}`;
};

const PackageInfo = ( { packageId, selected, all, flatRateGroups, unpacked, dimensionUnit, weightUnit, errors, updateWeight, openItemMove, removeItem, removePackage, setPackageType, openAddItem } ) => {
	if ( ! packageId ) {
		return null;
	}

	const renderItemInfo = ( item, itemIndex ) => {
		return (
			<ItemInfo key={ itemIndex } item={ item } itemIndex={ itemIndex } packageId={ packageId } showRemove={ true } openItemMove={ openItemMove } removeItem={ removeItem } />
		);
	};

	const renderPackageOption = ( box, boxId ) => {
		const dimensions = getBoxDimensions( box );
		return ( <option value={ boxId } key={ boxId }>{ box.name } - { renderPackageDimensions( dimensions, dimensionUnit ) }</option> );
	};

	const renderAddItemButton = () => {
		return ( <Button className="wcc-package__add-item-btn" compact onClick={ () => ( openAddItem() ) }>{ __( 'Add item' ) }</Button> );
	};

	const packageOptionChange = ( e ) => {
		setPackageType( packageId, e.target.value );
	};

	const pckg = selected[ packageId ];

	const renderItems = () => {
		const canAddItems = unpacked.length || _.some( selected, ( sel, selId ) => ( packageId !== selId && sel.items.length ) );

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
		if ( ! all[ pckg.box_id ] ) {
			return ( <div className="wcc-package__desc"><span className="wcc-package__desc-name">Package - </span><span className="wcc-package__desc-dimensions">{ renderPackageDimensions( pckg, dimensionUnit ) }</span></div> );
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
			<FormSelect onChange={ packageOptionChange } value={ pckg.box_id }>
				{ _.map( groups, ( group, groupId ) => {
					if ( _.isEmpty( group.definitions ) ) {
						return null;
					}

					return <optgroup label={ group.title } key={ groupId }>
						{ _.map( group.definitions, renderPackageOption ) }
					</optgroup>;
				} ) }
			</FormSelect>
		);
	};

	const pckgErrors = errors[ packageId ] || {};

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
			<div>
				<Button className="wcc-package__remove" borderless compact onClick={ () => ( removePackage( packageId ) ) }>
					<Gridicon icon="trash" />
					<span className="wcc-package__remove-label">{ __( 'Remove this package' ) }</span>
				</Button>
			</div>
		</div>
	);
};

PackageInfo.propTypes = {
	packageId: PropTypes.string.isRequired,
	selected: PropTypes.object.isRequired,
	all: PropTypes.object.isRequired,
	flatRateGroups: PropTypes.object.isRequired,
	unpacked: PropTypes.array.isRequired,
	updateWeight: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
};

export default PackageInfo;
