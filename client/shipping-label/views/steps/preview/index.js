import React, { PropTypes } from 'react';
import FoldableCard from 'components/foldable-card';
import { translate as __ } from 'lib/mixins/i18n';
import ExpandButton from 'shipping-label/views/expand-button';
import Dropdown from 'components/dropdown';

const PreviewStep = ( { form, labelActions } ) => {
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
				valuesMap={ { a4: 'A4', '4x6': '4"x6"' } }
				value={ form.preview.values.paper_size }
				updateValue={ labelActions.updatePaperSize } />
		</FoldableCard>
	);
};

export default PreviewStep;
