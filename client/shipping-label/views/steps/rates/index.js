import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import ShippingRates from './list';
import StepContainer from 'shipping-label/views/step-container';

const RatesStep = ( { form, values, available, storeOptions, labelActions, errors, expanded } ) => {
	return (
		<StepContainer
			title={ __( 'Rates' ) }
			summary={ __( '' ) }
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
