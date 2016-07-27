import React, { PropTypes } from 'react';
import WCCSettingsForm from 'components/wcc-settings-form';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';

const Settings = ( props ) => {
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			<WCCSettingsForm
				{ ...props }
			/>
		</div>
	);
};

Settings.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

export default Settings;
