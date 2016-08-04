import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';

const ShippingRates = ( { id, selectedRates, availableRates, packages, updateValue, dimensionUnit, weightUnit, currencySymbol, errors, layout } ) => {
	const renderTitle = ( pckg ) => {
		const dimensions = [ pckg.length, pckg.width, pckg.height ].map( dim => dim + ' ' + dimensionUnit ).join( ' x ' );
		const weight = pckg.weight + ' ' + weightUnit;
		return dimensions + ' ' + weight;
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
