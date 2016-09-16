import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import NumberField from 'components/number-field';
import FormLegend from 'components/forms/form-legend';
import { sprintf } from 'sprintf-js';
import mapValues from 'lodash/mapValues';

const renderPackageDimensions = ( pckg, dimensionUnit ) => {
	return `${pckg.length} ${dimensionUnit} x ${pckg.width} ${dimensionUnit} x ${pckg.height} ${dimensionUnit}`;
};

const OrderPackages = ( { packages, updateWeight, dimensionUnit, weightUnit, errors } ) => {
	const renderItemInfo = ( item, itemIndex ) => {
		return (
			<div key={ itemIndex } className="wcc-package-item">
				<div className="wcc-package-item__name">
					<span className="wcc-package-item__title">
						<a href={ item.url } target="_blank">{ item.name }</a>
					</span>
					{ item.attributes && <p>{ item.attributes }</p> }
				</div>
				<div className="wcc-package-item__quantity">{ item.quantity }</div>
			</div>
		);
	};

	const numPackages = Object.keys( packages ).length;
	let pckgIndex = 1;

	const renderPackageInfo = ( pckg, pckgId ) => {
		const pckgErrors = errors[ pckgId ] || {};
		return (
			<div key={ pckgId }>
				<div className="wcc-package-package-number">
					{ sprintf( __( 'Package %d (of %d)' ), pckgIndex++, numPackages ) }
				</div>

				<div>
					<div className="wcc-package-items-header">
						<FormLegend className="wcc-package-item__name">{ __( 'Package contents' ) }</FormLegend>
						<FormLegend className="wcc-package-item__quantity">{ __( 'Quantity' ) }</FormLegend>
					</div>
					{ pckg.items.map( renderItemInfo ) }
				</div>

				<NumberField
					id={ `weight_${pckgIndex}` }
					className="wcc-package-weight"
					title={ __( 'Total Weight' ) }
					value={ pckg.weight }
					updateValue={ ( value ) => updateWeight( pckgId, value ) }
					error={ pckgErrors.weight } />
				<span className="wcc-package-weight-unit">{ weightUnit }</span>

				<div className="wcc-package-package-dimension">
					<FormLegend>{ __( 'Package dimensions' ) }</FormLegend>
					<span className="wcc-package-package-dimension__unit">{ renderPackageDimensions( pckg, dimensionUnit ) }</span>
				</div>
			</div>
		);
	};

	return (
		<div>
			{ Object.values( mapValues( packages, renderPackageInfo ) ) }
		</div>
	);
};

OrderPackages.propTypes = {
	packages: PropTypes.object.isRequired,
	updateWeight: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
};

export default OrderPackages;
