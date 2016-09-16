import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import OrderPackages from './list';
import FormButton from 'components/forms/form-button';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import { sprintf } from 'sprintf-js';
import StepContainer from '../../step-container';

const PackagesStep = ( { values, storeOptions, labelActions, errors, expanded } ) => {
	const packageIds = Object.keys( values );
	const firstPackageId = packageIds[ 0 ];
	const firstPackage = values[ firstPackageId ];
	const isValid = 0 < firstPackage.weight;
	const renderSummary = () => {
		if ( ! isValid ) {
			return __( 'Weight not entered' );
		}
		if ( 1 < values.length ) {
			return sprintf( __( '%d packages' ), packageIds.length );
		}
		return firstPackage.weight + ' ' + storeOptions.weight_unit;
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
			<div className="step__confirmation-container">
				<FormButton
					type="button"
					className="packages__confirmation step__confirmation"
					disabled={ hasNonEmptyLeaves( errors ) }
					onClick={ labelActions.confirmPackages }
					isPrimary >
					{ __( 'Use this package' ) }
				</FormButton>
			</div>
		</StepContainer>
	);
};

PackagesStep.propTypes = {
	values: PropTypes.object.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.object.isRequired,
};

export default PackagesStep;
