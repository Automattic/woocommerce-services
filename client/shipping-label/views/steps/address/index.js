import React, { PropTypes } from 'react';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import AddressFields from './fields';
import ExpandButton from 'shipping-label/views/expand-button';

const Origin = ( props ) => {
	return (
		<FoldableCard
			header={ __( 'Origin address' ) }
			summary={ __( '' ) }
			expandedSummary={ __( '' ) }
			clickableHeader={ true }
			actionButton={ <ExpandButton/> }
			expanded={ false } >
			<AddressFields
				{ ...props }
				group="origin" />
		</FoldableCard>
	);
};

const Destination = ( props ) => {
	return (
		<FoldableCard
			header={ __( 'Destination address' ) }
			summary={ __( '' ) }
			expandedSummary={ __( '' ) }
			clickableHeader={ true }
			actionButton={ <ExpandButton/> }
			expanded={ false } >
			<AddressFields
				{ ...props }
				group="destination" />
		</FoldableCard>
	);
};

Origin.propTypes = Destination.propTypes = {
	values: PropTypes.object.isRequired,
	allowChangeCountry: PropTypes.bool.isRequired,
	group: PropTypes.string.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.object.isRequired,
};

export default {
	Origin,
	Destination,
};
