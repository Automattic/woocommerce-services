import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import { getPaperSizes } from 'lib/pdf-label-utils';
import Dropdown from 'components/dropdown';

const Sidebar = ( { form, errors, labelActions, paperSize } ) => {
	return (
		<div className="wcc-shipping-label-dialog__sidebar">
			<Dropdown
				id={ 'paper_size' }
				valuesMap={ getPaperSizes( form.origin.values.country ) }
				title={ __( 'Paper size' ) }
				value={ paperSize }
				updateValue={ labelActions.updatePaperSize }
				error={ errors.paperSize } />
		</div>
	);
};

Sidebar.propTypes = {
	paperSize: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
	form: PropTypes.object.isRequired,
	labelActions: PropTypes.object.isRequired,
};

export default Sidebar;
