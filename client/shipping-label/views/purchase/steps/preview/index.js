import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import Dropdown from 'components/dropdown';
import Spinner from 'components/spinner';
import { PAPER_SIZES } from 'lib/pdf-label-generator';
import _ from 'lodash';

const PreviewStep = ( { labelPreviewURL, showPreview, paperSize, labelActions, errors } ) => {
	let preview = null;
	if ( showPreview ) {
		if ( labelPreviewURL ) {
			preview = <iframe src={ labelPreviewURL } />;
		} else {
			preview = <Spinner size={ 24 } />;
		}
	}

	return (
		<div>
			<span className="preview-title">{ __( 'Preview' ) }</span>
			<div className="preview-placeholder">
				{ preview }
			</div>
			<Dropdown
				id={ 'paper_size' }
				valuesMap={ { '': __( 'Select one...' ), ..._.mapValues( PAPER_SIZES, 'name' ) } }
				title={ __( 'Paper size' ) }
				value={ paperSize }
				updateValue={ labelActions.updatePaperSize }
				error={ errors.paperSize } />
		</div>
	);
};

PreviewStep.propTypes = {
	labelPreviewURL: PropTypes.string,
	showPreview: PropTypes.bool.isRequired,
};

export default PreviewStep;
