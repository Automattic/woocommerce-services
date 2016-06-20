import React, { PropTypes } from 'react';
import WCCSettingsForm from 'components/wcc-settings-form';

const Settings = ( { storeOptions, schema, layout } ) => {
	return (
		<WCCSettingsForm
			storeOptions={ storeOptions }
			schema={ schema }
			layout={ layout }
		/>
	);
};

Settings.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

export default Settings;
