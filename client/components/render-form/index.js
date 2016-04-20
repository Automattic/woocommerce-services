import React, { PropTypes } from 'react';
import SettingsGroup from './render-group';

const RenderForm = ( { wooCommerceSettings, schema, layout, saveFormData } ) => {
	return (
		<div>
			{ layout.map( ( group, idx ) => (
				<SettingsGroup
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

RenderForm.propTypes = {
	wooCommerceSettings: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
	saveFormData: PropTypes.func.isRequired,
};

export default RenderForm;
