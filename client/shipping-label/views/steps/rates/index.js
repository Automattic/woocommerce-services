import React, { PropTypes } from 'react';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import ShippingRates from './list';
import ExpandButton from 'shipping-label/views/expand-button';

const RatesStep = ( { form, storeOptions, labelActions } ) => {
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
				selectedRates={ form.rates.values }
				availableRates={ form.rates.available }
				updateRate={ labelActions.updateRate }
				dimensionUnit={ storeOptions.dimension_unit }
				weightUnit={ storeOptions.weight_unit }
				currencySymbol={ storeOptions.currency_symbol }
				errors={ {} } />
		</FoldableCard>
	);
};

export default RatesStep;
