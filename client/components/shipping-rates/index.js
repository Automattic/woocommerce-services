import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import sum from 'lodash/sum';

const ShippingRates = ( { id, selectedRates, availableRates, packages, updateValue, dimensionUnit, weightUnit, currencySymbol, errors, layout } ) => {
	const renderTitle = ( pckg ) => {
		return `${pckg.length} ${dimensionUnit} x ${pckg.width} ${dimensionUnit} x ${pckg.height} ${dimensionUnit} (${pckg.weight} ${weightUnit})`;
	};

	const renderSinglePackage = ( pckg, index ) => {
		const selectedRate = selectedRates[ index ] || '';
		const errorObject = ( errors[ index ] || {} )[ '' ];
		const error = errorObject ? ( errorObject.value || layout.validation_hint ) : null;

		const titleMap = {};
		Object.keys( availableRates[ index ] ).forEach( ( serviceId ) => {
			const rateObject = availableRates[ index ][ serviceId ];
			titleMap[ serviceId ] = rateObject.name + ' (' + currencySymbol + rateObject.rate.toFixed( 2 ) + ')';
		} );

		return (
			<li key={ index }>
				<Dropdown
					id={ id + '_' + index }
					layout={ { titleMap: titleMap } }
					schema={ { title: renderTitle( pckg ) } }
					value={ selectedRate }
					updateValue={ ( value ) => updateValue( [ index ], value ) }
					error={ error } />
			</li>
		);
	};

	return (
		<ul>
			{ packages.map( renderSinglePackage ) }
		</ul>
	);
};

ShippingRates.propTypes = {
	id: PropTypes.string.isRequired,
	selectedRates: PropTypes.array.isRequired,
	availableRates: PropTypes.array.isRequired,
	packages: PropTypes.array.isRequired,
	updateValue: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	errors: PropTypes.object,
	layout: PropTypes.object.isRequired,
};

export default ShippingRates;

export const Summary = ( { selectedRates, availableRates, packages, currencySymbol } ) => {
	const rates = [];

	const renderSinglePackage = ( pckg, index ) => {
		const selectedRateId = selectedRates[ index ] || '';
		const selectedRateObject = availableRates[ index ][ selectedRateId ];
		if ( ! selectedRateObject ) {
			return null;
		}
		rates.push( selectedRateObject.rate );

		return (
			<li key={ index }>
				{ selectedRateObject.name + ' (' + currencySymbol + selectedRateObject.rate.toFixed( 2 ) + ')' }
			</li>
		);
	};

	return (
		<div>
			<ul>
				{ packages.map( renderSinglePackage ) }
			</ul>
			<span>Total: { currencySymbol + sum( rates ).toFixed( 2 ) }</span>
		</div>
	);
};

Summary.propTypes = {
	selectedRates: PropTypes.array.isRequired,
	availableRates: PropTypes.array.isRequired,
	packages: PropTypes.array.isRequired,
	currencySymbol: PropTypes.string.isRequired,
};
