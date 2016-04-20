import React, { PropTypes } from 'react';
import RenderForm from 'components/render-form';

const Settings = ( { wooCommerceSettings, schema, layout, saveFormData } ) => {
	return (
		<RenderForm
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
