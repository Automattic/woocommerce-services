import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import ShippingRates from './list';
import StepContainer from 'shipping-label/views/step-container';
import { sprintf } from 'sprintf-js';
import get from 'lodash/get';

const ratesSummary = ( selectedRates, availableRates, currencySymbol ) => {
	// Show the service name and cost when only one service/package exists
	if ( 1 === selectedRates.length ) {
		const rateInfo = get( availableRates[ 0 ], selectedRates[ 0 ] );

		if ( rateInfo ) {
			return sprintf( __( '%(serviceName)s: %(currencySymbol)s%(rate).2f' ), {
				serviceName: rateInfo.name,
				rate: rateInfo.rate,
				currencySymbol,
			} );
		}

		return __( '' );
	}

	let total = 0;

	// Otherwise, just show the total
	selectedRates.forEach( ( selectedRate, idx ) => {
		const selectedInfo = get( availableRates, [ idx, selectedRate ] );

		if ( selectedInfo ) {
			total += selectedInfo.rate;
		}
	} );

	return sprintf( __( 'Total rate: %(currencySymbol)s%(total).2f' ), {
		total,
		currencySymbol,
	} );
};

const RatesStep = ( { form, values, available, storeOptions, labelActions, errors, expanded } ) => {
	const summary = ratesSummary( values, available, storeOptions.currency_symbol );

	return (
		<StepContainer
			title={ __( 'Rates' ) }
			summary={ summary }
			expandedSummary={ summary }
			expanded={ expanded }
			toggleStep={ () => labelActions.toggleStep( 'rates' ) } >
			<ShippingRates
				id="rates"
				packages={ form.packages.values }
				selectedRates={ values }
				availableRates={ available }
				updateRate={ labelActions.updateRate }
				dimensionUnit={ storeOptions.dimension_unit }
				weightUnit={ storeOptions.weight_unit }
				currencySymbol={ storeOptions.currency_symbol }
				errors={ errors } />
		</StepContainer>
	);
};

RatesStep.propTypes = {
	form: PropTypes.object.isRequired,
	values: PropTypes.array.isRequired,
	available: PropTypes.array.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.array.isRequired,
};

export default RatesStep;
