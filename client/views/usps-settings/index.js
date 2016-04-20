import React, { PropTypes } from 'react';
import WCCSettingsForm from 'components/wcc-settings-form';

const Settings = ( { wooCommerceSettings, schema, layout, saveFormData } ) => {
	return (
		<WCCSettingsForm
			wooCommerceSettings={ wooCommerceSettings }
			schema={ schema }
			layout={ layout }
			saveFormData={ saveFormData }
		/>
	);
};

Settings.propTypes = {
	wooCommerceSettings: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

export default Settings;
