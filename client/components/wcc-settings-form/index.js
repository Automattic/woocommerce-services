import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';

const WCCSettingsForm = ( { storeOptions, schema, layout, saveFormData } ) => {
	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
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
