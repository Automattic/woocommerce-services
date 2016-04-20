import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';

const WCCSettingsForm = ( { storeOptions, schema, layout, saveFormData } ) => {
	return (
		<div>
			{ layout.map( ( group, idx ) => (
				<WCCSettingsGroup
					key={ idx }
					group={ group }
					schema={ schema }
					storeOptions={ storeOptions }
					saveFormData={ saveFormData }
				/>
			) ) }
		</div>
	);
};

WCCSettingsForm.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

export default WCCSettingsForm;
