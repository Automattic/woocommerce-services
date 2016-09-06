import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import NumberField from 'components/number-field';
import FormLegend from 'components/forms/form-legend';
import { sprintf } from 'sprintf-js';

const renderPackageDimensions = ( pckg, dimensionUnit ) => {
	return `${pckg.length} ${dimensionUnit} x ${pckg.width} ${dimensionUnit} x ${pckg.height} ${dimensionUnit}`;
};

const OrderPackages = ( { packages, updateWeight, dimensionUnit, weightUnit, errors } ) => {
	const renderItemInfo = ( item, itemIndex ) => {
		return (
			<div key={ itemIndex } className="wcc-package-item">
				<div className="item-name">
					<p>
						<a href={ item.url } target="_blank">{ item.name }</a>
					</p>
					{ item.attributes && <p>{ item.attributes }</p> }
				</div>
				<div className="item-quantity">{ item.quantity }</div>
			</div>
		);
	};

	const renderPackageInfo = ( pckg, pckgIndex ) => {
		const pckgErrors = errors[ pckgIndex ] || {};
		return (
			<div key={ pckgIndex }>
				<p>
					{ sprintf( __( 'Package %d (of %d)' ), pckgIndex + 1, packages.length ) }
				</p>

				<p>{ renderPackageDimensions( pckg, dimensionUnit ) }</p>

				<div className="wcc-package-items-header">
					<FormLegend className="item-name">{ __( 'Package contents' ) }</FormLegend>
					<FormLegend className="item-quantity">{ __( 'Quantity' ) }</FormLegend>
				</div>
				{ pckg.items.map( renderItemInfo ) }

				<NumberField
					id={ `weight_${pckgIndex}` }
					title={ __( 'Total Weight' ) }
					value={ pckg.weight }
					updateValue={ ( value ) => updateWeight( pckgIndex, value ) }
					error={ pckgErrors.weight } />
				<span>{ weightUnit } </span>
			</div>
		);
	};

	return (
		<div>
			{ packages.map( renderPackageInfo ) }
		</div>
	);
};

OrderPackages.propTypes = {
	packages: PropTypes.array.isRequired,
	updateWeight: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	errors: PropTypes.array.isRequired,
};

export default OrderPackages;
