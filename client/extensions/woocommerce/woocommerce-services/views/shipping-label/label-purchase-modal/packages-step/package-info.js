/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { some } from 'lodash';
import { Button } from '@wordpress/components';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import FieldError from 'woocommerce/woocommerce-services/components/field-error';
import FormLabel from 'components/forms/form-label';
import FormLegend from 'components/forms/form-legend';
import FormTextInputWithAffixes from 'components/forms/form-text-input-with-affixes';
import ItemInfo from './item-info';
import PackageSelect from './package-select';
import {
	updatePackageWeight,
	openAddItem,
	setPackageType,
} from 'woocommerce/woocommerce-services/state/shipping-label/actions';
import {
	getShippingLabel,
	isLoaded,
	getFormErrors,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	getLabelSettingsUserMeta,
} from 'woocommerce/woocommerce-services/state/label-settings/selectors';

import * as PackagesActions from "../../../../state/packages/actions";

const PackageInfo = props => {
	const {
		siteId,
		orderId,
		packageId,
		selected,
		weightUnit,
		errors,
		translate,
		userMeta,
	} = props;

	const pckgErrors = errors[ packageId ] || {};
	if ( ! packageId ) {
		return null;
	}

	const pckg = selected[ packageId ];

	const isIndividualPackage = 'individual' === pckg.box_id;

	const renderItemInfo = ( item, itemIndex ) => {
		return (
			<ItemInfo
				siteId={ siteId }
				orderId={ orderId }
				key={ itemIndex }
				item={ item }
				itemIndex={ itemIndex }
				packageId={ packageId }
				showRemove
				isIndividualPackage={ isIndividualPackage }
			/>
		);
	};


	const onAddItem = () => props.openAddItem( orderId, siteId );

	const renderAddItemButton = () => {
		if ( isIndividualPackage ) {
			return null;
		}

		return (
			<Button
				className={ classNames( 'button', 'packages-step__add-item-btn' ) }
				onClick={ onAddItem }
			>
				{ translate( 'Add items' ) }
			</Button>
		);
	};


	const renderItems = () => {
		const canAddItems = some( selected, ( sel, selId ) => packageId !== selId && sel.items.length );

		if ( ! pckg.items.length ) {
			return (
				<div className="packages-step__add-item-row">
					<div className="packages-step__no-items-message">
						{ translate( 'There are no items in this package.' ) }
						{ canAddItems ? renderAddItemButton() : null }
					</div>
				</div>
			);
		}

		const elements = pckg.items.map( renderItemInfo );
		if ( canAddItems ) {
			elements.push(
				<div key={ elements.length } className="packages-step__add-item-row">
					{ renderAddItemButton() }
				</div>
			);
		}

		return elements;
	};

	const onWeightChange = event => {
		props.updatePackageWeight( orderId, siteId, packageId, event.target.value );
	};

	const packageWeight = isNaN( pckg.weight ) ? '' : pckg.weight;

	return (
		<div className="packages-step__package">
			<div>
				<div className="packages-step__package-items-header-name">
					<FormLegend>{ translate( 'Items to fulfill' ) }</FormLegend>
				</div>
				<div className="packages-step__package-items-header-weight">
					<FormLegend>{ translate( 'Weight' ) }</FormLegend>
				</div>
				<div className="packages-step__package-items-header-qty">
					<FormLegend>{ translate( 'QTY' ) }</FormLegend>
				</div>
				<div className="packages-step__package-items-header-move">
				</div>
				{ renderItems() }
			</div>

			<PackageSelect
				siteId={ siteId }
				orderId={ orderId }
				isIndividualPackage={ isIndividualPackage }
				pckgErrors={ pckgErrors }
				pckg={ pckg }
				packageId={ packageId }
				lastBoxId={ userMeta.last_box_id }
			/>

			<div className="packages-step__package-weight">
				<FormLabel htmlFor={ `weight_${ packageId }` }>{ translate( 'Total Weight (with package)' ) }</FormLabel>
				<FormTextInputWithAffixes
					id={ `weight_${ packageId }` }
					placeholder={ translate( '0' ) }
					value={ packageWeight }
					onChange={ onWeightChange }
					isError={ Boolean( pckgErrors.weight ) }
					type="number"
					noWrap
					suffix={ weightUnit }
				/>
				{ pckgErrors.weight && <FieldError text={ pckgErrors.weight } /> }
			</div>
		</div>
	);
};

PackageInfo.propTypes = {
	siteId: PropTypes.number.isRequired,
	orderId: PropTypes.number.isRequired,
	packageId: PropTypes.string.isRequired,
	selected: PropTypes.object.isRequired,
	updatePackageWeight: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
	openAddItem: PropTypes.func.isRequired,
	userMeta: PropTypes.object.isRequired,
};

const mapStateToProps = ( state, { orderId, siteId } ) => {
	const loaded = isLoaded( state, orderId, siteId );
	const shippingLabel = getShippingLabel( state, orderId, siteId );
	const storeOptions = loaded ? shippingLabel.storeOptions : {};
	const errors = loaded && getFormErrors( state, orderId, siteId ).packages;
	const userMeta = loaded ? getLabelSettingsUserMeta( state, siteId ) : {};

	return {
		siteId,
		errors,
		packageId: shippingLabel.openedPackageId,
		selected: shippingLabel.form.packages.selected,
		dimensionUnit: storeOptions.dimension_unit,
		weightUnit: storeOptions.weight_unit,
		userMeta: userMeta,
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
			updatePackageWeight,
			openAddItem,
			setPackageType,
			... PackagesActions,
		},
		dispatch
	);
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( PackageInfo ) );
