import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import ShippingRates from './list';
import StepContainer from 'shipping-label/views/step-container';
import { sprintf } from 'sprintf-js';
import find from 'lodash/find';
import get from 'lodash/get';
import { hasNonEmptyLeaves } from 'lib/utils/tree';

const ratesSummary = ( selectedRates, availableRates, total, currencySymbol ) => {
	const packageIds = Object.keys( selectedRates );

	// Show the service name and cost when only one service/package exists
	if ( 1 === packageIds.length ) {
		const packageId = packageIds[ 0 ];
		const selectedRate = selectedRates[ packageId ];
		const rateInfo = find( availableRates[ packageId ], [ 'service_id', selectedRate ] );

		if ( rateInfo ) {
			return sprintf( __( '%(serviceName)s: %(currencySymbol)s%(rate).2f' ), {
				serviceName: rateInfo.title,
				rate: rateInfo.rate,
				currencySymbol,
			} );
		}

		return __( '' );
	}

	// Otherwise, just show the total
	return sprintf( __( 'Total rate: %(currencySymbol)s%(total).2f' ), {
		total,
		currencySymbol,
	} );
};

const hasUnselectableRate = ( selectedRates, availableRates ) => {
	let rateNotSelectable = false;

	Object.keys( selectedRates ).forEach( ( packageId ) => {
		const selectedRate = selectedRates[ packageId ];
		const packageRates = get( availableRates, [ packageId, 'rates' ], [] );

		if ( ( '' !== selectedRate ) && ( ! packageRates || ! find( packageRates, [ 'service_id', selectedRate ] ) ) ) {
			rateNotSelectable = true;
		}
	} );

	return rateNotSelectable;
};

const getRatesStatus = ( { retrievalInProgress, errors, showRateNotice } ) => {
	if ( retrievalInProgress ) {
		return { isProgress: true };
	}
	if ( hasNonEmptyLeaves( errors ) ) {
		return { isError: true };
	}
	if ( showRateNotice ) {
		return { isWarning: true };
	}
	return { isSuccess: true };
};

const RatesStep = ( props ) => {
	const {
		form,
		values,
		available,
		ratesTotal,
		storeOptions,
		labelActions,
		errors,
		expanded,
	} = props;
	const summary = ratesSummary( values, available, ratesTotal, storeOptions.currency_symbol );
	const showRateNotice = hasUnselectableRate( values, available );

	return (
		<StepContainer
			title={ __( 'Rates' ) }
			summary={ summary }
			expandedSummary={ summary }
			expanded={ expanded }
			toggleStep={ () => labelActions.toggleStep( 'rates' ) }
			{ ...getRatesStatus( { ...props, showRateNotice } ) } >
			<ShippingRates
				id="rates"
				showRateNotice={ showRateNotice }
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
	values: PropTypes.object.isRequired,
	available: PropTypes.object.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.object.isRequired,
};

export default RatesStep;
