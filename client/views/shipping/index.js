import React, { PropTypes } from 'react';
import WCCSettingsForm from 'components/wcc-settings-form';

const Settings = ( { storeOptions, schema, layout, saveFormData } ) => {
	return (
		<WCCSettingsForm
			storeOptions={ storeOptions }
			schema={ schema }
			layout={ layout }
			saveFormData={ saveFormData }
		/>
	);
};

Settings.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

export default Settings;
