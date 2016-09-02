import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';

const ShippingRates = ( {
		id,
		selectedRates,
		availableRates,
		packages,
		updateRate,
		currencySymbol,
		errors,
	} ) => {
	const renderTitle = ( pckg, idx ) => {
		if ( 1 === packages.length ) {
			return __( 'Choose rate' );
		}
		return sprintf( __( 'Choose rate: Package %(index)d' ), { index: idx + 1 } );
	};

	const renderSinglePackage = ( pckg, index ) => {
		const selectedRate = selectedRates[ index ] || '';

		const valuesMap = { '': __( 'Select one...' ) };
		Object.keys( availableRates[ index ] ).forEach( ( serviceId ) => {
			const rateObject = availableRates[ index ][ serviceId ];
			valuesMap[ serviceId ] = rateObject.name + ' (' + currencySymbol + rateObject.rate.toFixed( 2 ) + ')';
		} );

		return (
			<div key={ index }>
				<Dropdown
					id={ id + '_' + index }
					valuesMap={ valuesMap }
					title={ renderTitle( pckg, index ) }
					value={ selectedRate }
					updateValue={ ( value ) => updateRate( index, value ) }
					error={ errors[ index ] } />
			</div>
		);
	};

	return (
		<div>
			{ packages.map( renderSinglePackage ) }
		</div>
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
	errors: PropTypes.array.isRequired,
};

export default ShippingRates;
