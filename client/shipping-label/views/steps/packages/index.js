import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import OrderPackages from './list';
import Card from 'shipping-label/views/card';

const PackagesStep = ( { values, storeOptions, labelActions, errors } ) => {
	return (
		<Card
			title={ __( 'Packages' ) }
			summary={ __( '' ) } >
			<OrderPackages
				packages={ values }
				updateWeight={ labelActions.updatePackageWeight }
				dimensionUnit={ storeOptions.dimension_unit }
				weightUnit={ storeOptions.weight_unit }
				errors={ errors } />
		</Card>
	);
};

PackagesStep.propTypes = {
	values: PropTypes.array.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.array.isRequired,
};

export default PackagesStep;
