import React, { PropTypes } from 'react';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import ExpandButton from 'shipping-label/views/expand-button';
import Dropdown from 'components/dropdown';

const PreviewStep = ( { values, labelActions } ) => {
	return (
		<FoldableCard
			header={ __( 'Preview' ) }
			summary={ __( '' ) }
			expandedSummary={ __( '' ) }
			clickableHeader={ true }
			actionButton={ <ExpandButton/> }
			expanded={ false } >
			<Dropdown
				id="paper_size"
				title={ __( 'Paper size' ) }
				valuesMap={ { letter: 'Letter', '4x6': '4"x6"' } }
				value={ values.paper_size }
				updateValue={ labelActions.updatePaperSize } />
		</FoldableCard>
	);
};

PreviewStep.propTypes = {
	values: PropTypes.object.isRequired,
	labelActions: PropTypes.object.isRequired,
	errors: PropTypes.object.isRequired,
};

export default PreviewStep;
