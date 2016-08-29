import React, { PropTypes } from 'react';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import OrderPackages from './list';
import ExpandButton from 'shipping-label/views/expand-button';

const PackagesStep = ( { values, storeOptions, labelActions, errors } ) => {
	return (
		<FoldableCard
			header={ __( 'Packages' ) }
			summary={ __( '' ) }
			expandedSummary={ __( '' ) }
			clickableHeader={ true }
			actionButton={ <ExpandButton/> }
			expanded={ false } >
			<OrderPackages
				packages={ values }
				updateWeight={ labelActions.updatePackageWeight }
				dimensionUnit={ storeOptions.dimension_unit }
				weightUnit={ storeOptions.weight_unit }
				errors={ errors } />
		</FoldableCard>
	);
};

PackagesStep.propTypes = {
	values: PropTypes.array.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.array.isRequired,
};

export default PackagesStep;
