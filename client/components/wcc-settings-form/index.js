import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';

const WCCSettingsForm = ( { wooCommerceSettings, schema, layout, saveFormData } ) => {
	return (
		<div>
			{ layout.map( ( group, idx ) => (
				<WCCSettingsGroup
					key={ idx }
					group={ group }
					schema={ schema }
					wooCommerceSettings={ wooCommerceSettings }
					saveFormData={ saveFormData }
				/>
			) ) }
		</div>
	);
};

WCCSettingsForm.propTypes = {
	wooCommerceSettings: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

export default WCCSettingsForm;
