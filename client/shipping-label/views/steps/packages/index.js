import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import OrderPackages from './list';
import StepContainer from 'shipping-label/views/step-container';
import FormButton from 'components/forms/form-button';
import { hasNonEmptyLeaves } from 'lib/utils/tree';

const PackagesStep = ( { values, storeOptions, labelActions, errors, expanded } ) => {
	const isValid = 0 < values[ 0 ].weight;
	const renderSummary = () => {
		if ( ! isValid ) {
			return __( 'Weight not entered' );
		}
		return values[ 0 ].weight + storeOptions.weight_unit;
	};

	return (
		<StepContainer
			title={ __( 'Packages' ) }
			isSuccess={ isValid }
			isError={ ! isValid }
			summary={ renderSummary() }
			expanded={ expanded }
			toggleStep={ () => labelActions.toggleStep( 'packages' ) } >
			<OrderPackages
				packages={ values }
				updateWeight={ labelActions.updatePackageWeight }
				dimensionUnit={ storeOptions.dimension_unit }
				weightUnit={ storeOptions.weight_unit }
				errors={ errors } />
			<FormButton
				type="button"
				className="packages__confirmation"
				disabled={ hasNonEmptyLeaves( errors ) }
				onClick={ () => labelActions.submitStep( 'packages' ) }
				isPrimary >
				{ __( 'Confirm' ) }
			</FormButton>
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
