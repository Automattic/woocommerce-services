import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import OrderPackages from './list';
import StepContainer from 'shipping-label/views/step-container';

const PackagesStep = ( { values, storeOptions, labelActions, errors, expanded } ) => {
	console.dir( labelActions );
	return (
		<StepContainer
			title={ __( 'Packages' ) }
			isSuccess={ true }
			summary={ __( '' ) }
			expanded={ expanded }
			toggleStep={ () => labelActions.toggleStep( 'packages' ) } >
			<OrderPackages
				packages={ values }
				updateWeight={ labelActions.updatePackageWeight }
				dimensionUnit={ storeOptions.dimension_unit }
				weightUnit={ storeOptions.weight_unit }
				errors={ errors } />
		</StepContainer>
	);
};

PackagesStep.propTypes = {
	values: PropTypes.array.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.array.isRequired,
};

export default PackagesStep;
