import React, { PropTypes } from 'react';
import WCCSettingsForm from 'components/wcc-settings-form';

const Settings = ( props ) => {
	return (
		<WCCSettingsForm
			{ ...props }
		/>
	);
};

Settings.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

export default Settings;
