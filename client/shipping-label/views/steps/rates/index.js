import React, { PropTypes } from 'react';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import ShippingRates from './list';
import ExpandButton from 'shipping-label/views/expand-button';

const RatesStep = ( { form, values, available, storeOptions, labelActions, errors } ) => {
	return (
		<FoldableCard
			header={ __( 'Rates' ) }
			summary={ __( '' ) }
			expandedSummary={ __( '' ) }
			clickableHeader={ true }
			actionButton={ <ExpandButton/> }
			expanded={ false } >
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
		</FoldableCard>
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
