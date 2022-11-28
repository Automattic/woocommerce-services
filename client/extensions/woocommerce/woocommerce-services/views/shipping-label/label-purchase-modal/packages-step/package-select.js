/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import classNames from 'classnames';
import { isEmpty, map, reduce } from 'lodash';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import FormSelect from 'wcs-client/components/forms/form-select';
import getBoxDimensions from 'woocommerce/woocommerce-services/lib/utils/get-box-dimensions';
import PackageDialog from '../../../packages/package-dialog.js';
import FormLegend from 'wcs-client/components/forms/form-legend';
import FieldError from 'woocommerce/woocommerce-services/components/field-error';
import {
	getPackageGroupsForLabelPurchase,
	getPackagesForm,
} from "../../../../state/packages/selectors";
import { addPackage } from "../../../../state/packages/actions";
import {
	setPackageType,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
const renderPackageDimensions = ( dimensions, dimensionUnit ) => {
	return [ dimensions.length, dimensions.width, dimensions.height ]
		.map( dimension => `${ dimension } ${ dimensionUnit }` )
		.join( ' x ' );
};

const PackageSelect = props => {
	const {
		siteId,
		orderId,
		packageId,
		pckg,
		dimensionUnit,
		packageGroups,
		translate,
		pckgErrors,
		isIndividualPackage,
	} = props;

	const packageOptionChange = e => {
		props.setPackageType( orderId, siteId, packageId, e.target.value );
	};

	const onPackageDialogSave = packageName => {
		props.setPackageType( orderId, siteId, packageId, packageName );
	};

	const renderPackageOption = box => {
		const dimensions = getBoxDimensions( box );
		const boxId = box.id || box.name;
		return (
			<option value={ boxId } key={ boxId }>
				{ box.name } - { renderPackageDimensions( dimensions, dimensionUnit ) }
			</option>
		);
	};

	if ( ! packageId ) {
		return null;
	}

	if ( isIndividualPackage ) {
		const dimensionsClass = classNames( { 'is-error': pckgErrors.dimensions } );
		return (
			<div>
				<div className="packages-step__package-items-header">
					<FormLegend>{ translate( 'Individually Shipped Item' ) }</FormLegend>
				</div>
				<span className="packages-step__package-item-description">
					{ translate( 'Item Dimensions' ) } -{' '}
				</span>
				<span className={ dimensionsClass }>
					{ renderPackageDimensions( pckg, dimensionUnit ) }
				</span>
				{ pckgErrors.dimensions && <FieldError text={ pckgErrors.dimensions } /> }
			</div>
		);
	}

	const totalPackagesCount = reduce( packageGroups, ( acc, cur ) => {
		return acc + cur.definitions.length;
	}, 0 );

	return (
		<div>
			<div className="packages-step__package-items-header packages-step__package-details-header">
				<FormLegend>{ translate( 'Package details' ) }</FormLegend>
				{ /* eslint-disable jsx-a11y/anchor-is-valid */ }
				{ 0 !== totalPackagesCount ? (
					<a href="#" onClick={ () => props.addPackage( siteId ) }>
						{ translate( 'Add package' ) }
					</a>
				) : null }
				{ /* eslint-enable jsx-a11y/anchor-is-valid */ }
			</div>
			{ 0 === totalPackagesCount ? (
				<div className="packages-step__no-packages">
					<Gridicon icon="product" size={18} />
					{ /* eslint-disable jsx-a11y/anchor-is-valid */ }
					<a href="#" onClick={ () => props.addPackage( siteId ) }>
						{ translate( 'Select a package type' ) }
					</a>
					{ /* eslint-enable jsx-a11y/anchor-is-valid */ }
				</div>
			) : (
				<div className="packages-step__with-packages">
					<FormSelect
						onChange={ packageOptionChange }
						value={ pckg.box_id }
						isError={ pckgErrors.box_id || pckgErrors.dimensions }
					>
						<option value={ 'not_selected' } key={ 'not_selected' }>
							{ translate( 'Please select a package' ) }
						</option>{' '}
						{ map( packageGroups, ( group, groupId ) => {
							if ( isEmpty( group.definitions ) ) {
								return null;
							}

							return (
								<optgroup label={ group.title } key={ groupId }>
									{ map( group.definitions, renderPackageOption ) }
								</optgroup>
							);
						} ) }
					</FormSelect>
				</div>
			) }
			<PackageDialog
				persistOnSave={ true }
				{ ... props }
				onSaveSuccess={ onPackageDialogSave }
			/>
		</div>
	);
}

PackageSelect.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	packageId: PropTypes.string.isRequired,
	pckgErrors: PropTypes.object.isRequired,
	pckg: PropTypes.object.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	const storeOptions = loaded ? shippingLabel.storeOptions : {};
	const form = getPackagesForm( state, siteId ) || {};
	return {
		dimensionUnit: storeOptions.dimension_unit,
		packageGroups: getPackageGroupsForLabelPurchase( state, orderId, siteId ),
		form,
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
			setPackageType,
			addPackage,
		},
		dispatch
	);
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( PackageSelect ) );
