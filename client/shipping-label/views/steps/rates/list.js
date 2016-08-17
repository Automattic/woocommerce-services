import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import { translate as __ } from 'lib/mixins/i18n';

const ShippingRates = ( { id, selectedRates, availableRates, packages, updateRate, dimensionUnit, weightUnit, currencySymbol, errors } ) => {
	const renderTitle = ( pckg ) => {
		return `${pckg.length} ${dimensionUnit} x ${pckg.width} ${dimensionUnit} x ${pckg.height} ${dimensionUnit} (${pckg.weight} ${weightUnit})`;
	};

	const renderSinglePackage = ( pckg, index ) => {
		const selectedRate = selectedRates[ index ] || '';
		const errorObject = ( errors[ index ] || {} )[ '' ];
		const error = errorObject ? errorObject.value : null;

		const valuesMap = { '': __( 'Select one...' ) };
		Object.keys( availableRates[ index ] ).forEach( ( serviceId ) => {
			const rateObject = availableRates[ index ][ serviceId ];
			valuesMap[ serviceId ] = rateObject.name + ' (' + currencySymbol + rateObject.rate.toFixed( 2 ) + ')';
		} );

		return (
			<li key={ index }>
				<Dropdown
					id={ id + '_' + index }
					valuesMap={ valuesMap }
					title={ renderTitle( pckg ) }
					value={ selectedRate }
					updateValue={ ( value ) => updateRate( index, value ) }
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
	updateRate: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	errors: PropTypes.object,
};

export default ShippingRates;
