import React, { PropTypes } from 'react';
import WCCSettingsForm from 'components/wcc-settings-form';

const Settings = ( { storeOptions, schema, layout, saveForm } ) => {
	return (
		<WCCSettingsForm
			storeOptions={ storeOptions }
			schema={ schema }
			layout={ layout }
			saveForm={ saveForm }
		/>
	);
};

Settings.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveForm: PropTypes.func.isRequired,
};

export default Settings;
